import { isSchemaType } from "./schema-types";
import type { SchemaType } from "./schema-types";

export interface SiteIdentity {
  readonly type: "Organization" | "Person";
  readonly name: string;
  readonly logo: number;
  readonly same_as: readonly string[];
}

export interface PostTypeChoice {
  readonly name: string;
  readonly label: string;
}

export interface SettingsConfig {
  readonly postTypes: readonly PostTypeChoice[];
  readonly showSetup: boolean;
  readonly logoUrl: string;
  readonly siteName: string;
}

export interface PluginSettings {
  readonly frontendUrl: string;
  readonly identity: SiteIdentity;
  readonly defaults: Readonly<Record<string, SchemaType>>;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

export function readSettingsConfig(value: unknown): SettingsConfig | null {
  if (!isRecord(value) || !Array.isArray(value.postTypes)) return null;
  if (
    typeof value.showSetup !== "boolean" ||
    typeof value.logoUrl !== "string" ||
    typeof value.siteName !== "string"
  ) return null;

  const postTypes: PostTypeChoice[] = [];
  for (const item of value.postTypes) {
    if (!isRecord(item) || typeof item.name !== "string" || typeof item.label !== "string") {
      return null;
    }
    postTypes.push({ name: item.name, label: item.label });
  }

  return {
    postTypes,
    showSetup: value.showSetup,
    logoUrl: value.logoUrl,
    siteName: value.siteName,
  };
}

export function readPluginSettings(value: unknown, config: SettingsConfig): PluginSettings | null {
  if (!isRecord(value) || !isRecord(value.better_schema_identity)) return null;

  if (typeof value.better_schema_frontend_url !== "string") return null;
  const identity = value.better_schema_identity;
  if (
    (identity.type !== "Organization" && identity.type !== "Person") ||
    typeof identity.name !== "string" ||
    !Number.isSafeInteger(identity.logo) ||
    (identity.logo as number) < 0 ||
    !Array.isArray(identity.same_as) ||
    !identity.same_as.every((url: unknown) => typeof url === "string")
  ) return null;

  const rawDefaults = value.better_schema_type_defaults;
  if (!isRecord(rawDefaults)) return null;

  const defaults: Record<string, SchemaType> = {};
  for (const postType of config.postTypes) {
    const schemaType = rawDefaults[postType.name];
    defaults[postType.name] = isSchemaType(schemaType)
      ? schemaType
      : postType.name === "post" ? "Article" : "WebPage";
  }

  return {
    frontendUrl: value.better_schema_frontend_url,
    identity: {
      type: identity.type,
      name: identity.name,
      logo: identity.logo as number,
      same_as: identity.same_as,
    },
    defaults,
  };
}

export function parseSocialProfiles(value: string): string[] {
  return value.split(/\r?\n/).map((url) => url.trim()).filter(Boolean);
}

export function invalidSocialProfiles(urls: readonly string[]): string[] {
  return urls.filter((url) => {
    try {
      const parsed = new URL(url);
      return !["http:", "https:"].includes(parsed.protocol) || !parsed.hostname;
    } catch {
      return true;
    }
  });
}

export function isFrontendUrl(value: string): boolean {
  if (!value.trim()) return true;
  try {
    const url = new URL(value);
    return ["http:", "https:"].includes(url.protocol) && !!url.hostname &&
      !url.username && !url.password && !value.includes("?") && !value.includes("#");
  } catch {
    return false;
  }
}
