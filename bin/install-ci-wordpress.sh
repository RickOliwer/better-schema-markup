#!/usr/bin/env bash
# Downloads the WordPress core that matches the installed wp-phpunit test library.
# Used by CI to run PHPUnit natively (no Docker). Local runs use wp-env instead.
set -euo pipefail

core_dir="${WP_CORE_DIR:-/tmp/wordpress}"
version="$(composer show wp-phpunit/wp-phpunit --format=json | php -r 'echo json_decode( stream_get_contents( STDIN ) )->versions[0];')"

if [ -f "$core_dir/wp-includes/version.php" ]; then
	echo "WordPress already present in $core_dir"
	exit 0
fi

echo "Downloading WordPress $version to $core_dir"
mkdir -p "$core_dir"
curl -fsSL "https://wordpress.org/wordpress-${version}.tar.gz" | tar -xz --strip-components=1 -C "$core_dir"
