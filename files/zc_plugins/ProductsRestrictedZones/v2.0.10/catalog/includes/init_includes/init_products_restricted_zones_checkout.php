<?php

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$productsRestrictedZonesCheckoutPages = [
    'checkout_shipping',
    'checkout_shipping_address',
    'checkout_payment',
    'checkout_confirmation',
    'checkout_process',
    'checkout_one',
    'checkout_one_confirmation',
];

if (
    isset($productsRestrictedZones)
    && $productsRestrictedZones instanceof productsRestrictedZones
    && in_array((string)($_GET['main_page'] ?? ''), $productsRestrictedZonesCheckoutPages, true)
) {
    $productsRestrictedZones->update(
        $zco_notifier,
        'PRODUCTS_RESTRICTED_ZONES_CHECKOUT_GUARD'
    );
}

unset($productsRestrictedZonesCheckoutPages);
