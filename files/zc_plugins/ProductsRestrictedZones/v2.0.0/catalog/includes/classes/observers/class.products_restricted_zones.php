<?php

class productsRestrictedZones extends base
{
    public function __construct()
    {
        global $zco_notifier;

        $zco_notifier->attach($this, [
            'NOTIFY_HEADER_START_CHECKOUT',
            'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
            'NOTIFY_HEADER_START_SHOPPING_CART',
        ]);
    }

    public function update(&$class, $eventID, $paramsArray = []): void
    {
        global $messageStack, $order;

        if (
            !defined('PRODUCTS_RESTRICTED_ZONE_ENABLED')
            || PRODUCTS_RESTRICTED_ZONE_ENABLED !== 'true'
            || empty($_SESSION['cart'])
            || $_SESSION['cart']->count_contents() < 1
        ) {
            return;
        }

        $zoneId = (int)($order->delivery['zone_id'] ?? $_SESSION['customer_zone_id'] ?? 0);
        $countryId = (int)($order->delivery['country']['id'] ?? $_SESSION['customer_country_id'] ?? 0);
        if ($zoneId < 1 && $countryId < 1) {
            return;
        }

        $cartRequiresReview = false;
        foreach ($_SESSION['cart']->get_products() as $product) {
            $cartProductId = (string)($product['id'] ?? '');
            $productId = (int)explode(':', $cartProductId, 2)[0];
            if ($productId < 1) {
                continue;
            }

            $allowed = product_restricted_zone_only($productId, $zoneId, $countryId)
                && product_restricted_zone_cant($productId, $zoneId, $countryId);
            if ($allowed) {
                continue;
            }

            $cartRequiresReview = true;

            $replacementId = product_restricted_replace($productId);
            if (
                $replacementId > 0
                && product_restricted_zone_only($replacementId, $zoneId, $countryId)
                && product_restricted_zone_cant($replacementId, $zoneId, $countryId)
            ) {
                $_SESSION['cart']->remove($cartProductId);
                $_SESSION['cart']->add_cart(
                    $replacementId,
                    (float)($product['quantity'] ?? $product['qty'] ?? 1),
                    is_array($product['attributes'] ?? null) ? $product['attributes'] : [],
                    false
                );
                $messageStack->add(
                    'shopping_cart',
                    sprintf(
                        TEXT_PRODUCTS_RESTRICTED_REPLACEMENT,
                        zen_get_products_name($productId),
                        zen_get_products_name($replacementId)
                    ),
                    'caution'
                );
                continue;
            }

            $messageStack->add(
                'shopping_cart',
                sprintf(TEXT_PRODUCTS_RESTRICTED_ZONE, zen_get_products_name($productId)),
                'caution'
            );
        }

        if ($cartRequiresReview && $eventID !== 'NOTIFY_HEADER_START_SHOPPING_CART') {
            zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
        }
    }
}
