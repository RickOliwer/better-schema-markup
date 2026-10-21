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
  )
    return null;

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
  )
    return null;

  const rawDefaults = value.better_schema_type_defaults;
  if (!isRecord(rawDefaults)) return null;

  const defaults: Record<string, SchemaType> = {};
  for (const postType of config.postTypes) {
    const schemaType = rawDefaults[postType.name];
    defaults[postType.name] = isSchemaType(schemaType)
      ? schemaType
      : postType.name === "post"
        ? "Article"
        : "WebPage";
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
  return value
    .split(/\r?\n/)
    .map((url) => url.trim())
    .filter(Boolean);
}

/** The 1-based numbers of the lines that are filled in but are not full http or https URLs. */
export function invalidSocialProfileLines(value: string): number[] {
  return value
    .split(/\r?\n/)
    .flatMap((line, index) =>
      line.trim() !== "" && invalidSocialProfiles([line.trim()]).length > 0 ? [index + 1] : [],
    );
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
    return (
      ["http:", "https:"].includes(url.protocol) &&
      !!url.hostname &&
      !url.username &&
      !url.password &&
      !value.includes("?") &&
      !value.includes("#")
    );
  } catch {
    return false;
  }
}

export const WEEKDAYS = [
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
  "Sunday",
] as const;

export type Weekday = (typeof WEEKDAYS)[number];

export interface OpeningHours {
  readonly days: readonly Weekday[];
  readonly opens: string;
  readonly closes: string;
}

export interface BusinessSettings {
  readonly enabled: boolean;
  readonly telephone: string;
  readonly price_range: string;
  readonly address: {
    readonly street: string;
    readonly locality: string;
    readonly region: string;
    readonly postal_code: string;
    readonly country: string;
  };
  readonly geo: { readonly lat: string; readonly lng: string };
  readonly hours: readonly OpeningHours[];
}

export const EMPTY_BUSINESS: BusinessSettings = {
  enabled: false,
  telephone: "",
  price_range: "",
  address: { street: "", locality: "", region: "", postal_code: "", country: "" },
  geo: { lat: "", lng: "" },
  hours: [],
};

function text(value: unknown): string {
  return typeof value === "string" ? value : "";
}

function isWeekday(value: unknown): value is Weekday {
  return WEEKDAYS.some((day) => day === value);
}

/** Reads the local business setting from the REST settings response, falling back to empty. */
export function readBusinessSettings(value: unknown): BusinessSettings {
  if (!isRecord(value) || !isRecord(value.better_schema_business)) return EMPTY_BUSINESS;

  const business = value.better_schema_business;
  const address = isRecord(business.address) ? business.address : {};
  const geo = isRecord(business.geo) ? business.geo : {};
  const hours = Array.isArray(business.hours) ? business.hours : [];

  return {
    enabled: business.enabled === true,
    telephone: text(business.telephone),
    price_range: text(business.price_range),
    address: {
      street: text(address.street),
      locality: text(address.locality),
      region: text(address.region),
      postal_code: text(address.postal_code),
      country: text(address.country),
    },
    geo: { lat: text(geo.lat), lng: text(geo.lng) },
    hours: hours.flatMap((row: unknown) =>
      isRecord(row)
        ? [
            {
              days: Array.isArray(row.days) ? row.days.filter(isWeekday) : [],
              opens: text(row.opens),
              closes: text(row.closes),
            },
          ]
        : [],
    ),
  };
}

/** Matches the server: a coordinate within range, or empty. */
export function isCoordinate(value: string, limit: number): boolean {
  if (value.trim() === "") return true;
  const parsed = Number(value);
  return Number.isFinite(parsed) && Math.abs(parsed) <= limit;
}
