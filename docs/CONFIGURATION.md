# Configuration reference

## Rule precedence

For each cart product and delivery destination, the plugin evaluates the settings in this order:

1. If the product has one or more only-ship rules, the destination must match at least one of them.
2. If the product and destination match any do-not-ship rule, the product is restricted even when an only-ship rule allowed it.
3. If restricted and automatic replacement is enabled, the plugin looks for an eligible replacement.
4. Otherwise, the cart displays a restriction warning.

## Zone definitions

The number after the colon is a Zen Cart zone-definition ID, not a state/province zone ID. Create and review zone definitions under **Locations / Taxes > Zone Definitions**.

The plugin matches both common forms of zone-definition detail:

- A country plus a specific state or province.
- An entire country, represented by zone ID 0 within that country.

## Category rules

Use an uppercase or lowercase `C` immediately before a numeric category ID. Zen Cart's category-product list is used, so products assigned within subcategories are included according to Zen Cart's standard category traversal.

## Testing checklist

- When using One Page Checkout, use OPC 2.6.3 for the tested compatible configuration. Future OPC versions have not been verified and should be tested at your own risk.
- Test an address in every allowed zone.
- Test an address in every prohibited zone.
- Test an address outside all named zones.
- Test a product with several only-ship rules.
- Test a product present in both rule fields to confirm do-not-ship precedence.
- If replacements are enabled, test quantity and every attribute combination used by the restricted product.
