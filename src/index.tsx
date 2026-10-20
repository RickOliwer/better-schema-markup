import { registerPlugin } from "@wordpress/plugins";
import { createRoot } from "@wordpress/element";

import { SchemaPanel } from "./schema-panel";
import { readSettingsConfig } from "./settings-data";
import { SettingsScreen } from "./settings-screen";
import { ClassicEditor } from "./classic-editor";
import { readClassicEditorConfig } from "./classic-editor-data";

declare global {
  interface Window {
    betterSchemaSettings?: unknown;
    betterSchemaClassic?: unknown;
  }
}

const settingsRoot = document.getElementById("better-schema-settings");
const classicRoot = document.getElementById("better-schema-classic-editor");
if (settingsRoot) {
  const config = readSettingsConfig(window.betterSchemaSettings);
  if (config) createRoot(settingsRoot).render(<SettingsScreen config={config} />);
} else if (classicRoot) {
  const config = readClassicEditorConfig(window.betterSchemaClassic);
  if (config) createRoot(classicRoot).render(<ClassicEditor config={config} />);
} else {
  registerPlugin("better-schema", { render: SchemaPanel });
}
