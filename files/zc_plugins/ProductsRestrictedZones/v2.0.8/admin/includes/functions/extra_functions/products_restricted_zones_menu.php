<?php

declare(strict_types=1);

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

// Menu text must exist before Zen Cart constructs the admin menus. The
// language file is primary; this fallback covers different bootstrap orders.
if (!defined('BOX_CONFIGURATION_PRODUCTS_RESTRICTED_ZONE')) {
    define('BOX_CONFIGURATION_PRODUCTS_RESTRICTED_ZONE', 'Products Restricted Zones');
}

$productsRestrictedZonesInstalled = defined('PRODUCTS_RESTRICTED_ZONE_VERSION');
$productsRestrictedZonesGroupId = 0;
if (isset($db)) {
    $productsRestrictedZonesVersion = $db->Execute(
        "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'PRODUCTS_RESTRICTED_ZONE_VERSION' LIMIT 1"
    );
    if (!$productsRestrictedZonesVersion->EOF) {
        $productsRestrictedZonesInstalled = true;
        $productsRestrictedZonesGroupId = (int)$productsRestrictedZonesVersion->fields['configuration_group_id'];
    }
}

// Repair a missing Configuration registration without changing an existing
// registration or the permissions Zen Cart has already assigned to it.
if (
    function_exists('zen_register_admin_page')
    && $productsRestrictedZonesInstalled
    && $productsRestrictedZonesGroupId > 0
    && !zen_page_key_exists('configProductsRestrictedZone')
) {
    zen_register_admin_page(
        'configProductsRestrictedZone',
        'BOX_CONFIGURATION_PRODUCTS_RESTRICTED_ZONE',
        'FILENAME_CONFIGURATION',
        'gID=' . $productsRestrictedZonesGroupId,
        'configuration',
        'Y',
        $productsRestrictedZonesGroupId
    );
}

unset(
    $productsRestrictedZonesInstalled,
    $productsRestrictedZonesGroupId,
    $productsRestrictedZonesVersion
);
