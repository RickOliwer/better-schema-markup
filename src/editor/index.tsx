import { registerPlugin } from "@wordpress/plugins";
import { createRoot } from "@wordpress/element";

import "../styles/admin.css";

import { SchemaPanel } from "./schema-panel";
import { ClassicEditor } from "./classic-editor";
import { readClassicEditorConfig } from "./classic-editor-data";

declare global {
  interface Window {
    betterSchemaClassic?: unknown;
  }
}

const classicRoot = document.getElementById("better-schema-classic-editor");
if (classicRoot) {
  const config = readClassicEditorConfig(window.betterSchemaClassic);
  if (config) createRoot(classicRoot).render(<ClassicEditor config={config} />);
} else {
  registerPlugin("better-schema", { render: SchemaPanel });
}
