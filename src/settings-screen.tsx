import { __, _n, sprintf } from "@wordpress/i18n";
import { useEffect, useState } from "react";

import { schemaApiFetch } from "./admin-api";
import { BusinessFields } from "./business-fields";
import { Alert } from "./components/ui/alert";
import { Button, Spinner } from "./components/ui/button";
import { FieldDescription, FieldGroup, FieldRow, FieldSeparator, FieldSet, FormRoot } from "./components/ui/field";
import { SCHEMA_TYPES, isSchemaType } from "./schema-types";
import type { SchemaType } from "./schema-types";
import { SeoSettings } from "./seo-settings";
import { EMPTY_BUSINESS, isCoordinate, isFrontendUrl, invalidSocialProfileLines, invalidSocialProfiles, parseSocialProfiles, readBusinessSettings, readPluginSettings } from "./settings-data";
import type { BusinessSettings, PluginSettings, SettingsConfig, SiteIdentity } from "./settings-data";
import { SelectField, TextareaField, TextField } from "./settings-fields";

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
    <div role="group" aria-labelledby="bsm-logo-label" className="bsm:grid bsm:gap-2">
      <span id="bsm-logo-label" className="bsm:text-sm bsm:leading-snug bsm:font-medium bsm:text-foreground">
        {__("Logo", "better-schema-markup")}
      </span>
      <div className="bsm:flex bsm:items-center bsm:gap-4">
        {id > 0 && url ? (
          <img src={url} alt={__("Selected logo", "better-schema-markup")} className="bsm:size-16 bsm:rounded-md bsm:border bsm:border-solid bsm:border-border bsm:object-contain" />
        ) : (
          <div className="bsm:flex bsm:size-16 bsm:items-center bsm:justify-center bsm:rounded-md bsm:border bsm:border-dashed bsm:border-input bsm:text-xs bsm:text-muted-foreground">
            {__("None", "better-schema-markup")}
          </div>
        )}
        <div className="bsm:flex bsm:gap-2">
          <Button variant="outline" size="sm" onClick={openLibrary}>
            {id > 0 ? __("Change logo", "better-schema-markup") : __("Choose logo", "better-schema-markup")}
          </Button>
          {id > 0 ? (
            <Button variant="ghost" size="sm" onClick={() => onChange(0, "")}>
              {__("Remove", "better-schema-markup")}
            </Button>
          ) : null}
        </div>
      </div>
      <FieldDescription>{__("Leave empty to use the site icon.", "better-schema-markup")}</FieldDescription>
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
  const [business, setBusiness] = useState<BusinessSettings>(EMPTY_BUSINESS);
  const [logoUrl, setLogoUrl] = useState(config.logoUrl);
  const [frontendUrl, setFrontendUrl] = useState("");
  const [socialText, setSocialText] = useState("");
  const [showSetup, setShowSetup] = useState(config.showSetup);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  useEffect(() => {
    let active = true;
    void schemaApiFetch({ path: "/wp/v2/settings" })
      .then((response: unknown) => {
        if (!active) return;
        const parsed = readPluginSettings(response, config);
        if (!parsed) throw new Error(__("Could not read schema settings.", "better-schema-markup"));
        setSettings(parsed);
        setFrontendUrl(parsed.frontendUrl);
        setIdentity(parsed.identity);
        setDefaults(parsed.defaults);
        setSocialText(parsed.identity.same_as.join("\n"));
        setBusiness(readBusinessSettings(response));
      })
      .catch((cause: unknown) => { if (active) setError(errorText(cause)); });
    return () => { active = false; };
  }, [config]);

  async function save(setupOnly = false) {
    if (!identity) return;
    const profiles = parseSocialProfiles(socialText);
    if (!setupOnly && invalidSocialProfiles(profiles).length > 0) {
      setError(__("Fix the highlighted social profile lines. Each must be a full URL starting with https://.", "better-schema-markup"));
      return;
    }

    if (!setupOnly && !isFrontendUrl(frontendUrl)) {
      setError(__("Enter a full http or https frontend URL without credentials, a query, or a fragment.", "better-schema-markup"));
      return;
    }
    if (!setupOnly && (!isCoordinate(business.geo.lat, 90) || !isCoordinate(business.geo.lng, 180))) {
      setError(__("Latitude must be between -90 and 90, and longitude between -180 and 180.", "better-schema-markup"));
      return;
    }
    setSaving(true);
    setError("");
    setSuccess("");
    try {
      const nextIdentity = { ...identity, same_as: setupOnly ? identity.same_as : profiles };
      const response: unknown = await schemaApiFetch({
        path: "/wp/v2/settings",
        method: "POST",
        data: {
          better_schema_identity: nextIdentity,
          // Setup only asks for identity, so it keeps the saved business settings.
          ...(setupOnly ? {} : { better_schema_business: business }),
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
      setBusiness(readBusinessSettings(response));
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
      await schemaApiFetch({
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

  const socialProblems = invalidSocialProfileLines(socialText);

  if (!settings || !identity) {
    return error ? <Alert tone="error">{error}</Alert> : <Spinner className="bsm:text-muted-foreground" />;
  }

  const postTypeOptions = SCHEMA_TYPES.map(({ label, value }) => ({ label, value }));

  return (
    <FormRoot className="bsm:mt-4 bsm:max-w-2xl bsm:rounded-xl bsm:border bsm:border-solid bsm:border-border bsm:bg-card bsm:p-6 bsm:shadow-xs bsm:sm:p-8">
      <div className="bsm:grid bsm:gap-8">
        <p className="bsm:m-0 bsm:text-sm bsm:text-muted-foreground">
          {showSetup
            ? __("Answer three questions to set up your site identity. You can change these settings later.", "better-schema-markup")
            : __("Set who publishes this site and choose the schema used for posts without an individual choice.", "better-schema-markup")}
        </p>
        {error ? <Alert tone="error">{error}</Alert> : null}
        {success ? <Alert tone="success" onDismiss={() => setSuccess("")}>{success}</Alert> : null}

        <FieldSet
          title={__("Site identity", "better-schema-markup")}
          description={__("Who is behind this site. Search engines show this as the publisher.", "better-schema-markup")}
        >
          <FieldGroup>
            <SelectField
              label={__("Who publishes this site?", "better-schema-markup")}
              tip={__("Choose Organization for a company, club or brand. Choose Person if the site belongs to you as an individual, such as a personal blog or portfolio. This sets the publisher in your schema.", "better-schema-markup")}
              value={identity.type}
              options={[
                { label: __("Organization", "better-schema-markup"), value: "Organization" },
                { label: __("Person", "better-schema-markup"), value: "Person" },
              ]}
              onChange={(type) => setIdentity({ ...identity, type })}
            />
            <TextField
              label={__("Name", "better-schema-markup")}
              help={__("Leave empty to use the site title.", "better-schema-markup")}
              placeholder={config.siteName}
              value={identity.name}
              onChange={(name) => setIdentity({ ...identity, name })}
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
              <TextareaField
                label={__("Social profiles", "better-schema-markup")}
                tip={__("Links to your official profiles, such as Facebook, LinkedIn, Instagram, YouTube or Wikipedia. Search engines use them to connect these accounts to your site. Optional.", "better-schema-markup")}
                help={__("One full URL per line, starting with https://.", "better-schema-markup")}
                error={
                  socialProblems.length > 0
                    ? sprintf(
                        /* translators: %s: comma-separated line numbers. */
                        _n("Line %s is not a full URL. Start it with https://.", "Lines %s are not full URLs. Start them with https://.", socialProblems.length, "better-schema-markup"),
                        socialProblems.join(", "),
                      )
                    : undefined
                }
                placeholder={"https://www.facebook.com/yourpage\nhttps://www.linkedin.com/company/yourcompany\nhttps://www.instagram.com/yourhandle"}
                rows={4}
                value={socialText}
                onChange={setSocialText}
              />
            ) : null}
          </FieldGroup>
        </FieldSet>

        {!showSetup ? (
          <>
            <FieldSeparator />
            <FieldSet
              title={__("Headless sites", "better-schema-markup")}
              description={__("Only needed if visitors see a different address than WordPress.", "better-schema-markup")}
            >
              <TextField
                label={__("Frontend URL", "better-schema-markup")}
                tip={__("Enter the public address, for example https://example.com. Schema URLs, including media, then use it instead of the WordPress address.", "better-schema-markup")}
                help={__("Leave empty if visitors use the WordPress address.", "better-schema-markup")}
                error={isFrontendUrl(frontendUrl) ? undefined : __("Enter a full URL starting with https://, without a query or #fragment.", "better-schema-markup")}
                type="url"
                placeholder="https://example.com"
                value={frontendUrl}
                onChange={setFrontendUrl}
              />
            </FieldSet>

            {identity.type === "Organization" ? (
              <>
                <FieldSeparator />
                <BusinessFields value={business} onChange={setBusiness} />
              </>
            ) : null}

            <FieldSeparator />
            <SeoSettings />

            <FieldSeparator />
            <FieldSet
              title={__("Default schema by post type", "better-schema-markup")}
              description={__("An individual post’s schema choice takes precedence over its post type default.", "better-schema-markup")}
            >
              <FieldRow>
                {config.postTypes.map((postType) => (
                  <SelectField
                    key={postType.name}
                    label={postType.label}
                    value={defaults[postType.name] ?? (postType.name === "post" ? "Article" : "WebPage")}
                    options={postTypeOptions}
                    onChange={(value) => {
                      if (isSchemaType(value)) setDefaults({ ...defaults, [postType.name]: value });
                    }}
                  />
                ))}
              </FieldRow>
            </FieldSet>
          </>
        ) : null}

        <FieldSeparator />
        <div className="bsm:flex bsm:gap-2">
          <Button isBusy={saving} onClick={() => { void save(showSetup); }}>
            {showSetup ? __("Finish setup", "better-schema-markup") : __("Save settings", "better-schema-markup")}
          </Button>
          {showSetup ? (
            <Button variant="ghost" disabled={saving} onClick={() => { void skipSetup(); }}>
              {__("Skip setup", "better-schema-markup")}
            </Button>
          ) : null}
        </div>
      </div>
    </FormRoot>
  );
}
