import { createRoot } from "@wordpress/element";

import "../styles/admin.css";
import { readSettingsConfig } from "./settings-data";
import { SettingsScreen } from "./settings-screen";

declare global {
  interface Window {
    betterSchemaSettings?: unknown;
  }
}

const root = document.getElementById("better-schema-settings");
const config = root ? readSettingsConfig(window.betterSchemaSettings) : null;

if (root && config) {
  createRoot(root).render(<SettingsScreen config={config} />);
}
