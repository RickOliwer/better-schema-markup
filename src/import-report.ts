export interface ImportReport {
  readonly imported: number;
  readonly skipped: number;
  readonly unsupported: number;
  readonly failed: number;
  readonly cursor: number;
  readonly done: boolean;
  readonly details: readonly { readonly post: number; readonly message: string }[];
}

export function isImportReport(value: unknown): value is ImportReport {
  if (typeof value !== "object" || value === null) return false;
  const data = value as Record<string, unknown>;
  return ["imported", "skipped", "unsupported", "failed", "cursor"].every((key) =>
    typeof data[key] === "number" && Number.isSafeInteger(data[key]) && data[key] >= 0,
  ) && typeof data.done === "boolean" && Array.isArray(data.details) && data.details.every((item: unknown) =>
    typeof item === "object" && item !== null && "post" in item && typeof item.post === "number" &&
    "message" in item && typeof item.message === "string",
  );
}

