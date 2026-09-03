# Change history

## v2.0.8 - 2026-09-03

- Adds native One Page Checkout enforcement with no OPC file changes or extra configuration.
- Blocks restricted orders at OPC's pre-order validation notification after the actual guest delivery address is available.
- Adds `checkout_one` and `checkout_one_confirmation` to the direct request guard.
- Adds a regression test proving an OPC guest order for OH cannot be created when `C15:1` permits only GA.

## v2.0.7 - 2026-09-03

- Enforces restrictions after Zen Cart constructs the actual delivery address on shipping, payment, and confirmation pages.
- Adds a complete regression test proving that `C15:1` allows GA and redirects an OH checkout to the shopping cart.
- Keeps the direct request guard and final order processing guard as independent enforcement layers.

## v2.0.6 - 2026-09-03

- Adds a direct checkout request guard that does not depend solely on page notifications.
- Uses the customer's default address before Zen Cart initializes the selected shipping address.
- Retains notification checks as a second enforcement layer through final order processing.

## v2.0.5 - 2026-09-03

- Enforces product restrictions at shipping, payment, confirmation, and final order processing.
- Prevents a shopper from bypassing a shopping-cart warning and completing a restricted order.
- Adds a runtime regression check for every required checkout enforcement notification.

## v2.0.4 - 2026-09-03

- Resolved the selected checkout shipping address from the customer-owned `sendto` address-book entry when Zen Cart fires its checkout notifier before creating the order object.
- Prevented checkout restrictions from incorrectly falling back to the customer's default address when another shipping address is selected.
- Added a runtime test proving that the selected shipping address takes priority over the default customer address.

## v2.0.3 - 2026-09-03

- Corrected the storefront extra-definition filename to the Zen Cart 2.x `lang.*.php` format so restriction and replacement messages load.
- Added defensive storefront message fallbacks in the observer so a missing or delayed language load cannot cause a fatal cart error.
- Added package checks for both storefront message constants and the required language filename.

## v2.0.2 - 2026-09-03

- Added logged-out shopping-cart shipping-estimator support by reading the submitted or saved estimator country and state before Zen Cart builds the estimator order object.
- Retained checkout delivery-address and logged-in customer-address fallbacks.
- Replaced both zone-rule setting labels and descriptions with plain-language instructions and complete examples.
- Added automated coverage for multiple allowed zones, empty prohibited-zone rules, submitted estimator addresses, saved estimator addresses, and checkout delivery addresses.

## v2.0.1 - 2026-09-03

- Added an admin-runtime registration safeguard that repairs a missing **Configuration > Products Restricted Zones** menu entry after installation or upgrade.
- Added the menu language definition to the installed admin files so the label is available whenever Zen Cart constructs the admin menu.
- Retained an inline menu-label fallback for supported Zen Cart versions with a different admin bootstrap sequence.

## v2.0.0 - 2026-09-03

- Converted the loose-file package to an encapsulated Zen Cart Plugin Manager package.
- Added support declarations for Zen Cart 2.0.x, 2.1.x, and 2.2.x.
- Corrected the empty do-not-ship setting, which could cause unrestricted products to fail the restriction check.
- Corrected multiple only-ship rules so a product can be allowed in any configured zone regardless of rule order.
- Corrected multiple do-not-ship rules so an unrelated later entry cannot undo a matching restriction.
- Added country-wide zone-definition matching alongside state- and province-specific matching.
- Added safe parsing for spaces, missing separators, invalid product IDs, invalid category IDs, and invalid zone-definition IDs.
- Initialized all zone and replacement results to eliminate undefined-variable warnings on PHP 8.x.
- Parameterized the replacement-product model lookup.
- Limited automatic replacement to active, different products with a non-empty model suffix.
- Prevented an automatic replacement that is also restricted for the destination.
- Removed the storefront debug-session value written by the legacy observer.
- Reported all restricted products in a cart rather than returning after the first match.
- Redirected checkout attempts back to the cart when a restriction or automatic replacement requires review, preventing checkout from continuing with a restricted or newly changed cart.
- Added Plugin Manager installation, upgrade, and clean-uninstall handling while preserving legacy configuration values.
- Made the displayed version read-only through a fixed selection.
- Replaced obsolete installation and support links and expanded the project documentation.
- Added automated PHP linting for PHP 8.0 through 8.5.

## v1.1.1

- Corrected the legacy uninstall SQL and updated the self-installer.

## v1.1.0

- Added optional replacement-product lookup by model suffix.

## v1.0.0

- Initial release with product and category restrictions by zone definition.
