import { defineConfig, type Plugin } from "vite-plus";

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

// Writes build/index.asset.php so PHP knows which WordPress scripts to load first.
function wordpressAssetFile(): Plugin {
  return {
    name: "wordpress-asset-file",
    generateBundle(_, bundle) {
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
        fileName: "index.asset.php",
        source: `<?php return array( 'dependencies' => array( ${list} ) );\n`,
      });
    },
  };
}

export default defineConfig({
  plugins: [wordpressAssetFile()],
  lint: {
    ignorePatterns: ["build/**"],
    options: {
      typeAware: true,
      typeCheck: true,
    },
  },
  build: {
    outDir: "build",
    emptyOutDir: true,
    lib: {
      entry: "src/index.tsx",
      formats: ["iife"],
      name: "betterSchema",
      fileName: () => "index.js",
    },
    rolldownOptions: {
      external: isExternal,
      output: { globals: toGlobal },
    },
  },
});
