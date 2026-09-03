# Products Restricted Zones for Zen Cart

Products Restricted Zones prevents selected products or entire categories from shipping to specified Zen Cart zone definitions. It can also limit a product to approved zones and optionally replace a restricted item with an active product whose model uses a configured suffix.

Version 2.0.8 is a complete modernization of the legacy v1.1.1 release. It is encapsulated for Zen Cart Plugin Manager, makes no core-file edits, corrects the original multi-rule evaluation, recognizes both state-specific and country-wide zone-definition entries, and blocks restricted orders in standard Zen Cart checkout and One Page Checkout.

## Compatibility

- Zen Cart 2.0.x.
- Zen Cart 2.1.x.
- Zen Cart 2.2.x.
- PHP 8.0 through 8.5, within the limits supported by the installed Zen Cart version.
- One Page Checkout 2.6.3.

One Page Checkout 2.6.3 is the tested compatible OPC release. Future OPC versions have not been verified. Test future versions at your own risk.

## Features

- Installation and removal through Zen Cart Plugin Manager.
- No Zen Cart core-file or template-file edits.
- Restrict one product by product ID.
- Restrict every product in a category by prefixing the category ID with `C`.
- Define products that can ship only to selected zones.
- Define products that cannot ship to selected zones.
- Apply several allowed zones to the same product without order-dependent results.
- Do-not-ship rules take priority over only-ship rules.
- Recognize zone definitions covering an entire country as well as a specific state or province.
- Ignore malformed configuration entries safely.
- Optionally replace a restricted item by matching a product-model suffix.
- Only active, different products qualify as automatic replacements.
- Display every restricted product found in the cart instead of stopping after the first one.
- Return checkout attempts to the cart until restrictions and automatic replacements have been reviewed.
- Evaluate destinations submitted through the logged-out shopping-cart shipping estimator.
- Apply checkout restrictions to the selected shipping address instead of the customer's default address.
- Preserve existing v1.1.1 configuration values during migration.
- Repair an absent Configuration menu registration automatically on the next admin page load.
- Clean uninstall removes only this plugin's configuration and admin-page registration.

## Installation

1. Make a complete backup of the store files and database.
2. Download or clone this repository.
3. Upload the complete `files/zc_plugins/ProductsRestrictedZones` directory into the store's `zc_plugins` directory.
4. Sign in to Zen Cart Admin.
5. Open **Modules > Plugin Manager**.
6. Find **Products Restricted Zones v2.0.8** and select **Install**.
7. Open **Configuration > Products Restricted Zones**.
8. Add and test the required rules before enabling the plugin.

Do not rename the `ProductsRestrictedZones` or `v2.0.8` directories.

## Upgrading from v1.1.1

Version 2.0.8 replaces the former loose admin and storefront files with a Plugin Manager package. The installer locates the legacy configuration group and preserves its enabled setting, rules, replacement setting, and replacement suffix.

1. Back up the store files and database.
2. Record the current settings under **Configuration > Products Restricted Zone**.
3. Upload `files/zc_plugins/ProductsRestrictedZones` and install v2.0.8 through Plugin Manager.
4. Confirm the retained settings under **Configuration > Products Restricted Zones**.
5. Test allowed and restricted delivery addresses.
6. Remove the obsolete loose files listed in [the installation guide](docs/INSTALLATION.md).

Do not run the old `uninstall.sql` before installing v2.0.8; it would delete the settings that the new installer is designed to retain.

## Configuration

First create the required geographic areas under **Locations / Taxes > Zone Definitions**. Use each zone definition's numeric ID in the rules below.

Rules use this format:

```text
ProductOrCategory:ZoneDefinition
```

Separate several rules with commas:

```text
145:1,145:2,C27:3
```

- `145:1` applies zone definition 1 to product ID 145.
- `C27:3` applies zone definition 3 to every product in category ID 27, including products in its subcategories as returned by Zen Cart.
- Spaces around entries are ignored.
- Invalid or incomplete entries are ignored.

### Products limited to specific zones

A product named in this field can ship only when the delivery address belongs to at least one zone listed for that product. Products with no matching rule are unaffected.

To allow product 145 in either zone 1 or zone 2:

```text
145:1,145:2
```

### Products prohibited from specific zones

A product named in this field cannot ship when the delivery address belongs to a listed zone. These rules take priority if the same product and address are also permitted by an only-ship rule.

### Automatic replacement

When enabled, the plugin reads the restricted product model, appends the configured suffix, and searches for an active replacement product with that resulting model.

For example, product model `ABC123` and suffix `-ALT` produce replacement model `ABC123-ALT`. A replacement is used only if it is a different active product and is not itself restricted for the destination. Product attributes are carried to the replacement; therefore, automatic replacement should be used only when the two products share compatible option-value IDs.

## Database changes

Installation creates or reuses one configuration group, stores six configuration keys, and registers one Configuration menu page. It does not alter product, category, order, address, zone, or zone-definition tables.

## Uninstall

1. Open **Modules > Plugin Manager**.
2. Select **Uninstall** for Products Restricted Zones.
3. Delete the `zc_plugins/ProductsRestrictedZones` directory if the plugin will not be reinstalled.

Uninstall removes the plugin settings and menu registration. It does not change products, categories, orders, addresses, or zone definitions.

## Support and license

Products Restricted Zones is free software released under the GNU General Public License version 2 or later. It is provided without warranty. Bugs can be reported through the [PRO-Webs support desk](https://prowebsinc.zohodesk.com/portal/en/newticket); installation, configuration, and customization are not included.

- [Zen Cart plugin page](https://www.zen-cart.com/plugins/products-restricted-zones-vb1981)
- [GitHub repository](https://github.com/mprough/Products-Restricted-Zones-for-Zen-Cart)
- [PRO-Webs.net](https://pro-webs.net/)

## Credits

Maintained and modernized by Melanie Prough, PRO-Webs, Inc. The legacy observer was derived from earlier Zen Cart observer examples by Andrew Berezin and the Zen Cart Development Team; their notices are preserved in the archived v1.1.1 documentation and repository history.
