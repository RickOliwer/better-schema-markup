import { registerPlugin } from "@wordpress/plugins";

import { SchemaPanel } from "./schema-panel";

registerPlugin("better-schema", { render: SchemaPanel });
