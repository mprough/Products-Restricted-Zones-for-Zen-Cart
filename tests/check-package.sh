#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
plugin="$root/files/zc_plugins/ProductsRestrictedZones/v2.0.1"

test -f "$plugin/manifest.php"
test -f "$plugin/Installer/ScriptedInstaller.php"
test -f "$plugin/catalog/includes/classes/observers/class.products_restricted_zones.php"
test -f "$plugin/catalog/includes/functions/extra_functions/products_restricted_zones.php"

if find "$root/files" -type f -name '*.php' -print0 | xargs -0 -n1 php -l; then
    echo 'Package structure and PHP syntax checks passed.'
fi
