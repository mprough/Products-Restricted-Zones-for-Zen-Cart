<?php

// Keep storefront restrictions operational if a template or supported Zen Cart
// bootstrap sequence does not load the extra-definition file before this observer.
if (!defined('TEXT_PRODUCTS_RESTRICTED_ZONE')) {
    define('TEXT_PRODUCTS_RESTRICTED_ZONE', '%s cannot be shipped to your area.');
}
if (!defined('TEXT_PRODUCTS_RESTRICTED_REPLACEMENT')) {
    define('TEXT_PRODUCTS_RESTRICTED_REPLACEMENT', 'We cannot ship %1$s to your area, so it has been replaced with %2$s.');
}

class productsRestrictedZones extends base
{
    public function __construct()
    {
        global $zco_notifier;

        $zco_notifier->attach($this, [
            'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
            'NOTIFY_HEADER_END_CHECKOUT_SHIPPING',
            'NOTIFY_HEADER_START_CHECKOUT_PAYMENT',
            'NOTIFY_HEADER_END_CHECKOUT_PAYMENT',
            'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION',
            'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION',
            'NOTIFY_HEADER_START_CHECKOUT_PROCESS',
            'NOTIFY_CHECKOUT_ONE_CONFIRMATION_PRE_ORDER_CHECK',
            'NOTIFY_HEADER_START_SHOPPING_CART',
            'NOTIFY_SHIPPING_MODULE_PRE_CALCULATE_BOXES_AND_TARE',
        ]);
    }

    public function updateNotifyShippingModulePreCalculateBoxesAndTare(
        &$class,
        string $eventID,
        $paramsArray = []
    ): void {
        global $current_page_base;

        if (
            ($current_page_base ?? '') !== 'shopping_cart'
            || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
            || !isset($_POST['action'])
            || $_POST['action'] !== 'submit'
        ) {
            return;
        }

        // Zen Cart builds the estimator's order and saves its destination before
        // requesting shipping quotes. Recheck here so custom templates that do
        // not expose the estimator POST during the cart header are still covered.
        $this->update($class, $eventID, is_array($paramsArray) ? $paramsArray : []);
    }

    public function updateNotifyCheckoutOneConfirmationPreOrderCheck(
        &$class,
        string $eventID,
        $paramsArray,
        &$error
    ): void {
        $this->update($class, $eventID, is_array($paramsArray) ? $paramsArray : []);
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

        [$zoneId, $countryId] = $this->getDestination($eventID, $order ?? null);
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
                $messageMethod = $eventID === 'NOTIFY_HEADER_START_SHOPPING_CART' ? 'add' : 'add_session';
                $messageStack->$messageMethod(
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

            $messageMethod = $eventID === 'NOTIFY_HEADER_START_SHOPPING_CART' ? 'add' : 'add_session';
            $messageStack->$messageMethod(
                'shopping_cart',
                sprintf(TEXT_PRODUCTS_RESTRICTED_ZONE, zen_get_products_name($productId)),
                'caution'
            );
        }

        if ($cartRequiresReview && $eventID !== 'NOTIFY_HEADER_START_SHOPPING_CART') {
            zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
        }
    }

    /**
     * Resolve the address available at the current stage of the cart or checkout.
     *
     * The shopping-cart header runs before Zen Cart's shipping-estimator module,
     * so estimator POST values must be read directly on that request.
     *
     * @return array{0: int, 1: int} Zone ID and country ID.
     */
    protected function getDestination(string $eventID, ?object $order): array
    {
        global $db;

        if ($eventID === 'NOTIFY_HEADER_START_SHOPPING_CART') {
            $postedCountryId = (int)($_POST['zone_country_id'] ?? 0);
            if ($postedCountryId > 0) {
                $postedZoneId = (int)($_POST['zone_id'] ?? 0);
                if ($postedZoneId < 1 && isset($_POST['state']) && trim((string)$_POST['state']) !== '') {
                    $sql = "SELECT zone_id FROM " . TABLE_ZONES . " WHERE zone_country_id = :countryId: AND (zone_name = :state: OR zone_code = :state:) LIMIT 1";
                    $sql = $db->bindVars($sql, ':countryId:', $postedCountryId, 'integer');
                    $sql = $db->bindVars($sql, ':state:', trim((string)$_POST['state']), 'string');
                    $zone = $db->Execute($sql);
                    $postedZoneId = $zone->EOF ? 0 : (int)$zone->fields['zone_id'];
                }
                return [$postedZoneId, $postedCountryId];
            }

            $estimatedCountryId = (int)($_SESSION['cart_country_id'] ?? 0);
            if ($estimatedCountryId > 0) {
                return [(int)($_SESSION['cart_zone'] ?? 0), $estimatedCountryId];
            }
        }

        $deliveryCountryId = (int)($order->delivery['country']['id'] ?? $order->delivery['country_id'] ?? 0);
        if ($deliveryCountryId > 0) {
            return [(int)($order->delivery['zone_id'] ?? 0), $deliveryCountryId];
        }

        // Checkout start notifiers run before Zen Cart creates the order object.
        // Resolve the selected shipping address directly from the owned address-book
        // entry instead of falling back to the customer's default address.
        $customerId = (int)($_SESSION['customer_id'] ?? 0);
        $shippingAddressId = (int)($_SESSION['sendto'] ?? $_SESSION['customer_default_address_id'] ?? 0);
        if ($customerId > 0 && $shippingAddressId > 0) {
            $sql = "SELECT entry_zone_id, entry_country_id FROM " . TABLE_ADDRESS_BOOK . " WHERE address_book_id = :addressId: AND customers_id = :customerId: LIMIT 1";
            $sql = $db->bindVars($sql, ':addressId:', $shippingAddressId, 'integer');
            $sql = $db->bindVars($sql, ':customerId:', $customerId, 'integer');
            $address = $db->Execute($sql);
            if (!$address->EOF) {
                return [
                    (int)$address->fields['entry_zone_id'],
                    (int)$address->fields['entry_country_id'],
                ];
            }
        }

        $defaultAddressId = (int)($_SESSION['customer_default_address_id'] ?? 0);
        if ($customerId > 0 && $defaultAddressId > 0 && $defaultAddressId !== $shippingAddressId) {
            $sql = "SELECT entry_zone_id, entry_country_id FROM " . TABLE_ADDRESS_BOOK . " WHERE address_book_id = :addressId: AND customers_id = :customerId: LIMIT 1";
            $sql = $db->bindVars($sql, ':addressId:', $defaultAddressId, 'integer');
            $sql = $db->bindVars($sql, ':customerId:', $customerId, 'integer');
            $address = $db->Execute($sql);
            if (!$address->EOF) {
                return [
                    (int)$address->fields['entry_zone_id'],
                    (int)$address->fields['entry_country_id'],
                ];
            }
        }

        return [
            (int)($_SESSION['customer_zone_id'] ?? 0),
            (int)($_SESSION['customer_country_id'] ?? 0),
        ];
    }
}
