# Installation and migration

## New installation

Upload `files/zc_plugins/ProductsRestrictedZones` to the store's `zc_plugins` directory, then install **Products Restricted Zones v2.0.6** under **Modules > Plugin Manager**. Configure the rules under **Configuration > Products Restricted Zones** and test them before enabling the plugin.

## Migrating from v1.1.1

Install v2.0.6 before removing the legacy files. The new installer reuses the configuration group containing `PRODUCTS_RESTRICTED_ZONE_VERSION`, updates its version, retains the other five configuration values, and refreshes the Configuration menu registration.

After testing v2.0.6, remove these obsolete loose files from the store:

```text
YOUR_ADMIN/includes/auto_loaders/config.products_restricted_zones.php
YOUR_ADMIN/includes/extra_datafiles/products_restricted_zones.php
YOUR_ADMIN/includes/init_includes/init_products_restricted_zones.php
YOUR_ADMIN/includes/installers/products_restricted_zones/1_0_0.php
YOUR_ADMIN/includes/installers/products_restricted_zones/1_1_0.php
YOUR_ADMIN/includes/installers/products_restricted_zones/1_1_1.php
includes/auto_loaders/config.products_restricted_zones.php
includes/classes/observers/class.products_restricted_zones.php
includes/functions/extra_functions/products_restricted_zones.php
includes/languages/english/extra_definitions/products_restricted_zones.php
```

Replace `YOUR_ADMIN` with the store's actual renamed admin directory. Do not remove similarly named files from `zc_plugins/ProductsRestrictedZones`.

The former `uninstall.sql` is obsolete and is preserved only in [the archived v1.1.1 documentation](archive/readme-v1.1.1.txt). Use Plugin Manager to uninstall v2.0.6.
