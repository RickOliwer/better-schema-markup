#!/usr/bin/env python3
"""Create the distributable from an explicit allowlist, never from the Git working tree."""
import json
import re
import zipfile
from pathlib import Path

root = Path(__file__).resolve().parent.parent
package = json.loads((root / 'package.json').read_text())
version = package['version']
header = (root / 'better-schema-markup.php').read_text()
readme = (root / 'readme.txt').read_text()
assert re.search(r'Version:\s+' + re.escape(version) + r'\s', header), 'Plugin version differs'
assert 'Stable tag: ' + version in readme, 'Readme version differs'
required = ['better-schema-markup.php', 'uninstall.php', 'readme.txt', 'LICENSE',
            'build/index.js', 'build/index.asset.php', 'languages/better-schema-markup.pot']
files = [root / name for name in required]
files += sorted((root / 'includes').rglob('*.php'))
# Include original JavaScript/TypeScript sources and build inputs for GPL reproducibility.
files += [path for path in sorted((root / 'src').glob('*')) if path.suffix in ('.ts', '.tsx') and '.test.' not in path.name]
files += [root / name for name in ['package.json', 'bun.lock', 'vite.config.ts', 'tsconfig.json', 'composer.json']]
assert all(path.is_file() for path in files), 'Build and generate the translation template first'
out = root / 'dist'
out.mkdir(exist_ok=True)
archive = out / ('better-schema-markup-' + version + '.zip')
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as target:
    for path in sorted(set(files)):
        info = zipfile.ZipInfo('better-schema-markup/' + path.relative_to(root).as_posix(), (2026, 1, 1, 0, 0, 0))
        info.external_attr = 0o100644 << 16
        info.compress_type = zipfile.ZIP_DEFLATED
        target.writestr(info, path.read_bytes())
print(archive)
