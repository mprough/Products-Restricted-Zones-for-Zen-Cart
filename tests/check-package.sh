#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
plugin="$root/files/zc_plugins/ProductsRestrictedZones/v2.0.10"

test -f "$plugin/manifest.php"
test -f "$plugin/Installer/ScriptedInstaller.php"
test -f "$plugin/catalog/includes/classes/observers/class.products_restricted_zones.php"
test -f "$plugin/catalog/includes/functions/extra_functions/products_restricted_zones.php"
test -f "$plugin/catalog/includes/languages/english/extra_definitions/lang.products_restricted_zones.php"

find "$root/files" -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php "$root/tests/runtime-test.php"
echo 'Package structure, PHP syntax, and runtime checks passed.'

php -r 'define("IS_ADMIN_FLAG", true); $m = require $argv[1]; if ($m["pluginId"] !== 1981 || $m["pluginVersion"] !== "v2.0.10") { fwrite(STDERR, "Manifest ID or version mismatch\n"); exit(1); }' "$plugin/manifest.php"
