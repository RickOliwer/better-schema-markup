import tailwindcss from "@tailwindcss/vite";
import { defineConfig, type BuildEnvironmentOptions, type Plugin } from "vite-plus";

// WordPress ships React and the @wordpress/* packages as global scripts, so
// they must be referenced as globals instead of bundled.
const GLOBALS: Record<string, string> = {
  react: "React",
  "react-dom": "ReactDOM",
  "react/jsx-runtime": "ReactJSXRuntime",
};

const HANDLES: Record<string, string> = {
  react: "react",
  "react-dom": "react-dom",
  "react/jsx-runtime": "react-jsx-runtime",
};

const camelCase = (name: string) => name.replace(/-([a-z])/g, (_, c: string) => c.toUpperCase());

const isExternal = (id: string) => id in GLOBALS || id.startsWith("@wordpress/");

const toGlobal = (id: string) => GLOBALS[id] ?? `wp.${camelCase(id.slice("@wordpress/".length))}`;

const toHandle = (id: string) => HANDLES[id] ?? `wp-${id.slice("@wordpress/".length)}`;

// Writes build/<entry>.asset.php so PHP knows which WordPress scripts to load first.
function wordpressAssetFile(): Plugin {
  return {
    name: "wordpress-asset-file",
    generateBundle(_, bundle) {
      const entry = this.environment.name === "settings" ? "settings" : "index";
      const deps = new Set<string>();
      for (const chunk of Object.values(bundle)) {
        if (chunk.type === "chunk") {
          chunk.imports.filter(isExternal).forEach((id) => deps.add(toHandle(id)));
        }
      }
      const list = [...deps]
        .sort()
        .map((h) => `'${h}'`)
        .join(", ");
      this.emitFile({
        type: "asset",
        fileName: `${entry}.asset.php`,
        source: `<?php return array( 'dependencies' => array( ${list} ) );\n`,
      });
    },
  };
}

// CommonJS dependencies (such as use-sync-external-store, used by Base UI) keep
// `require("react")` for external packages, which browsers cannot run. Point those
// calls at the same WordPress globals that ES imports use.
function requireExternalsAsGlobals(): Plugin {
  return {
    name: "require-externals-as-globals",
    renderChunk(code) {
      const replaced = code.replace(/\brequire\((["'])([^"']+)\1\)/g, (call: string, _quote: string, id: string) =>
        // Read the global through `window`: the call is often `var React = require("react")`,
        // where a bare `React` would be the local variable, not the global.
        isExternal(id) ? `window.${toGlobal(id)}` : call,
      );
      return replaced === code ? null : { code: replaced, map: null };
    },
  };
}

// Each admin screen gets its own script, so the block editor never loads the
// settings page's Base UI and Tailwind code. IIFE builds allow one entry each.
function adminScript(entry: string, fileName: string, emptyOutDir: boolean): BuildEnvironmentOptions {
  return {
    outDir: "build",
    minify: true,
    emptyOutDir,
    lib: {
      entry,
      formats: ["iife"],
      name: "betterSchema",
      fileName: () => `${fileName}.js`,
      cssFileName: fileName,
    },
    rolldownOptions: {
      external: isExternal,
      output: { globals: toGlobal },
    },
  };
}

export default defineConfig({
  plugins: [wordpressAssetFile(), requireExternalsAsGlobals(), tailwindcss()],
  // Library builds leave `process.env.NODE_ENV` from dependencies untouched, and
  // browsers have no `process`. Replace it so React libraries use production code.
  define: { "process.env.NODE_ENV": JSON.stringify("production") },
  lint: {
    ignorePatterns: ["build/**"],
    options: {
      typeAware: true,
      typeCheck: true,
    },
  },
  environments: {
    client: { build: adminScript("src/index.tsx", "index", true) },
    settings: { consumer: "client", build: adminScript("src/settings.tsx", "settings", false) },
  },
  builder: {
    async buildApp(builder) {
      // The editor build empties the folder first, so it must run before the settings build.
      for (const name of ["client", "settings"]) {
        const environment = builder.environments[name];
        if (!environment) throw new Error(`Missing build environment: ${name}`);
        await builder.build(environment);
      }
    },
  },
});
