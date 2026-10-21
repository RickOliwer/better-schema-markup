import { isSchemaType } from "./schema-types";
import type { SchemaType } from "./schema-types";

export interface SchemaMeta {
  readonly _better_schema_type: SchemaType;
}

/**
 * Post meta comes from the REST API, so it is only trusted once the schema
 * type is known to be valid. Post types without the meta fail this check.
 */
export function isSchemaMeta(value: unknown): value is SchemaMeta {
  return (
    typeof value === "object" &&
    value !== null &&
    "_better_schema_type" in value &&
    isSchemaType(value._better_schema_type)
  );
}
