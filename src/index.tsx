import { registerPlugin } from "@wordpress/plugins";
import { createRoot } from "@wordpress/element";

import { SchemaPanel } from "./schema-panel";
import { readSettingsConfig } from "./settings-data";
import { SettingsScreen } from "./settings-screen";

declare global {
  interface Window {
    betterSchemaSettings?: unknown;
  }
}

const settingsRoot = document.getElementById("better-schema-settings");
if (settingsRoot) {
  const config = readSettingsConfig(window.betterSchemaSettings);
  if (config) createRoot(settingsRoot).render(<SettingsScreen config={config} />);
} else {
  registerPlugin("better-schema", { render: SchemaPanel });
}
