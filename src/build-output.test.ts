import { readFileSync } from "node:fs";

import { describe, expect, it } from "vite-plus/test";

// The admin scripts run directly in the browser, where Node's `process` and
// `require` do not exist. Library builds can leave both behind from dependencies
// such as Base UI, and one reference stops the whole script.
describe("built admin scripts", () => {
  for (const file of ["build/index.js", "build/settings.js"]) {
    const code = readFileSync(file, "utf8");

    it(`${file} does not reference Node's process object`, () => {
      expect(code).not.toMatch(/\bprocess\.env\b/);
    });

    it(`${file} does not call require()`, () => {
      expect(code).not.toMatch(/\brequire\(/);
    });
  }
});
