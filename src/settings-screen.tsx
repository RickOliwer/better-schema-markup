import apiFetch from "@wordpress/api-fetch";
import {
  Button,
  Notice,
  SelectControl,
  Spinner,
  TextareaControl,
  TextControl,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "react";

import { SCHEMA_TYPES, isSchemaType } from "./schema-types";
import type { SchemaType } from "./schema-types";
import { isFrontendUrl, invalidSocialProfiles, parseSocialProfiles, readPluginSettings } from "./settings-data";
import type { PluginSettings, SettingsConfig, SiteIdentity } from "./settings-data";

interface MediaFrame {
  on(event: "select", callback: () => void): void;
  open(): void;
  state(): { get(key: "selection"): { first(): { toJSON(): unknown } | undefined } };
}

declare global {
  interface Window {
    wp?: {
      media?: (options: {
        title: string;
        button: { text: string };
        multiple: false;
        library: { type: "image" };
      }) => MediaFrame;
    };
  }
}

function errorText(error: unknown): string {
  if (typeof error === "object" && error !== null && "message" in error && typeof error.message === "string") {
    return error.message;
  }
  return __("Something went wrong. Please try again.", "better-schema-markup");
}

interface LogoPickerProps {
  readonly id: number;
  readonly url: string;
  readonly onChange: (id: number, url: string) => void;
}

function LogoPicker({ id, url, onChange }: LogoPickerProps) {
  function openLibrary() {
    const media = window.wp?.media;
    if (!media) return;

    const frame = media({
      title: __("Choose a logo", "better-schema-markup"),
      button: { text: __("Use this image", "better-schema-markup") },
      multiple: false,
      library: { type: "image" },
    });
    frame.on("select", () => {
      const selected: unknown = frame.state().get("selection").first()?.toJSON();
      if (
        typeof selected === "object" && selected !== null &&
        "id" in selected && Number.isSafeInteger(selected.id) &&
        "url" in selected && typeof selected.url === "string"
      ) {
        onChange(selected.id as number, selected.url);
      }
    });
    frame.open();
  }

  return (
    <div style={{ marginBottom: "24px" }}>
      <p><strong>{__("Logo", "better-schema-markup")}</strong></p>
      {id > 0 && url ? <img src={url} alt={__("Selected logo", "better-schema-markup")} style={{ display: "block", maxWidth: "180px", maxHeight: "120px", marginBottom: "12px" }} /> : null}
      <Button variant="secondary" onClick={openLibrary}>
        {id > 0 ? __("Change logo", "better-schema-markup") : __("Choose logo", "better-schema-markup")}
      </Button>
      {id > 0 ? <Button variant="tertiary" onClick={() => onChange(0, "")}>{__("Remove", "better-schema-markup")}</Button> : null}
      <p className="description">{__("Leave empty to use the site icon.", "better-schema-markup")}</p>
    </div>
  );
}

interface SettingsScreenProps {
  readonly config: SettingsConfig;
}

export function SettingsScreen({ config }: SettingsScreenProps) {
  const [settings, setSettings] = useState<PluginSettings | null>(null);
  const [identity, setIdentity] = useState<SiteIdentity | null>(null);
  const [defaults, setDefaults] = useState<Readonly<Record<string, SchemaType>>>({});
  const [logoUrl, setLogoUrl] = useState(config.logoUrl);
  const [frontendUrl, setFrontendUrl] = useState("");
  const [socialText, setSocialText] = useState("");
  const [showSetup, setShowSetup] = useState(config.showSetup);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  useEffect(() => {
    let active = true;
    void apiFetch({ path: "/wp/v2/settings" })
      .then((response: unknown) => {
        if (!active) return;
        const parsed = readPluginSettings(response, config);
        if (!parsed) throw new Error(__("Could not read schema settings.", "better-schema-markup"));
        setSettings(parsed);
        setFrontendUrl(parsed.frontendUrl);
        setIdentity(parsed.identity);
        setDefaults(parsed.defaults);
        setSocialText(parsed.identity.same_as.join("\n"));
      })
      .catch((cause: unknown) => { if (active) setError(errorText(cause)); });
    return () => { active = false; };
  }, [config]);

  async function save(setupOnly = false) {
    if (!identity) return;
    const profiles = parseSocialProfiles(socialText);
    if (!setupOnly && invalidSocialProfiles(profiles).length > 0) {
      setError(__("Social profiles must be full http or https URLs, one per line.", "better-schema-markup"));
      return;
    }

    if (!setupOnly && !isFrontendUrl(frontendUrl)) {
      setError(__("Enter a full http or https frontend URL without credentials, a query, or a fragment.", "better-schema-markup"));
      return;
    }
    setSaving(true);
    setError("");
    setSuccess("");
    try {
      const nextIdentity = { ...identity, same_as: setupOnly ? identity.same_as : profiles };
      const response: unknown = await apiFetch({
        path: "/wp/v2/settings",
        method: "POST",
        data: {
          better_schema_identity: nextIdentity,
          better_schema_frontend_url: frontendUrl.trim(),
          better_schema_type_defaults: defaults,
          better_schema_setup_complete: true,
        },
      });
      const parsed = readPluginSettings(response, config);
      if (!parsed) throw new Error(__("The saved settings could not be read.", "better-schema-markup"));
      setSettings(parsed);
      setFrontendUrl(parsed.frontendUrl);
      setIdentity(parsed.identity);
      setDefaults(parsed.defaults);
      setSocialText(parsed.identity.same_as.join("\n"));
      setShowSetup(false);
      setSuccess(__("Settings saved.", "better-schema-markup"));
    } catch (cause) {
      setError(errorText(cause));
    } finally {
      setSaving(false);
    }
  }

  async function skipSetup() {
    setSaving(true);
    setError("");
    try {
      await apiFetch({
        path: "/wp/v2/settings",
        method: "POST",
        data: { better_schema_setup_complete: true },
      });
      setShowSetup(false);
    } catch (cause) {
      setError(errorText(cause));
    } finally {
      setSaving(false);
    }
  }

  if (!settings || !identity) {
    return error ? <Notice status="error" isDismissible={false}>{error}</Notice> : <Spinner />;
  }

  return (
    <div style={{ maxWidth: "680px" }}>
      {showSetup ? (
        <p>{__("Answer three questions to set up your site identity. You can change these settings later.", "better-schema-markup")}</p>
      ) : (
        <p>{__("Set who publishes this site and choose the schema used for posts without an individual choice.", "better-schema-markup")}</p>
      )}
      {error ? <Notice status="error" isDismissible={false}>{error}</Notice> : null}
      {success ? <Notice status="success" onRemove={() => setSuccess("")}>{success}</Notice> : null}

      <h2>{__("Site identity", "better-schema-markup")}</h2>
      <SelectControl
        label={__("Who publishes this site?", "better-schema-markup")}
        value={identity.type}
        options={[
          { label: __("Organization", "better-schema-markup"), value: "Organization" },
          { label: __("Person", "better-schema-markup"), value: "Person" },
        ]}
        onChange={(type) => {
          if (type === "Organization" || type === "Person") setIdentity({ ...identity, type });
        }}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <TextControl
        label={__("Name", "better-schema-markup")}
        help={__("Leave empty to use the site title.", "better-schema-markup")}
        placeholder={config.siteName}
        value={identity.name}
        onChange={(name) => setIdentity({ ...identity, name })}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <LogoPicker
        id={identity.logo}
        url={logoUrl}
        onChange={(logo, url) => {
          setIdentity({ ...identity, logo });
          setLogoUrl(url);
        }}
      />

      {!showSetup ? (
        <>
          <TextControl
            label={__("Frontend URL", "better-schema-markup")}
            help={__("For headless sites, enter the public site URL. Local schema URLs, including media, use this base. Leave empty to use WordPress URLs.", "better-schema-markup")}
            value={frontendUrl}
            onChange={setFrontendUrl}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextareaControl
            label={__("Social profiles", "better-schema-markup")}
            help={__("Enter one full URL per line.", "better-schema-markup")}
            value={socialText}
            onChange={setSocialText}
            __nextHasNoMarginBottom
          />
          <h2>{__("Default schema by post type", "better-schema-markup")}</h2>
          <p>{__("An individual post’s schema choice takes precedence over its post type default.", "better-schema-markup")}</p>
          {config.postTypes.map((postType) => (
            <SelectControl
              key={postType.name}
              label={postType.label}
              value={defaults[postType.name] ?? (postType.name === "post" ? "Article" : "WebPage")}
              options={SCHEMA_TYPES.map(({ label, value }) => ({ label, value }))}
              onChange={(value) => {
                if (isSchemaType(value)) setDefaults({ ...defaults, [postType.name]: value });
              }}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          ))}
        </>
      ) : null}

      <p style={{ marginTop: "24px" }}>
        <Button variant="primary" isBusy={saving} disabled={saving} onClick={() => { void save(showSetup); }}>
          {showSetup ? __("Finish setup", "better-schema-markup") : __("Save settings", "better-schema-markup")}
        </Button>
        {showSetup ? <Button variant="tertiary" disabled={saving} onClick={() => { void skipSetup(); }}>{__("Skip setup", "better-schema-markup")}</Button> : null}
      </p>
    </div>
  );
}
