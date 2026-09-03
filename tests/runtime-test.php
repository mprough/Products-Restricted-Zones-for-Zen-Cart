<?php

declare(strict_types=1);

class base
{
}

class TestRecordset
{
    public bool $EOF;
    public array $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
        $this->EOF = $fields === [];
    }

    public function MoveNext(): void
    {
        $this->EOF = true;
    }
}

class TestDatabase
{
    public function bindVars(string $sql, string $placeholder, mixed $value, string $type): string
    {
        return str_replace($placeholder, (string)(int)$value, $sql);
    }

    public function Execute(string $sql): TestRecordset
    {
        if (str_contains($sql, 'address_book')) {
            return new TestRecordset(['entry_zone_id' => 44, 'entry_country_id' => 223]);
        }
        return new TestRecordset(['geo_zone_id' => 2]);
    }
}

define('TABLE_ZONES_TO_GEO_ZONES', 'zones_to_geo_zones');
define('TABLE_ADDRESS_BOOK', 'address_book');
define('PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES', '145:1,145:2');
define('PRODUCTS_RESTRICTED_ZONE_CANT_VALUES', '');

function zen_get_categories_products_list(int $categoryId): array
{
    return [];
}

$db = new TestDatabase();
require dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.4/catalog/includes/functions/extra_functions/products_restricted_zones.php';
require dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.4/catalog/includes/classes/observers/class.products_restricted_zones.php';

if (!defined('TEXT_PRODUCTS_RESTRICTED_ZONE') || !defined('TEXT_PRODUCTS_RESTRICTED_REPLACEMENT')) {
    throw new RuntimeException('The storefront restriction messages must always be defined.');
}

if (!product_restricted_zone_only(145, 10, 223)) {
    throw new RuntimeException('A product with several only-ship rules must pass when any configured zone matches.');
}
if (!product_restricted_zone_only(999, 10, 223)) {
    throw new RuntimeException('A product without an only-ship rule must remain unrestricted.');
}
if (!product_restricted_zone_cant(145, 10, 223)) {
    throw new RuntimeException('An empty prohibited-zone setting must not restrict products.');
}

class TestProductsRestrictedZones extends productsRestrictedZones
{
    public function __construct()
    {
    }

    public function destination(string $eventId, ?object $order): array
    {
        return $this->getDestination($eventId, $order);
    }
}

$observer = new TestProductsRestrictedZones();
$_SESSION = [];
$_POST = ['zone_country_id' => '223', 'zone_id' => '47'];
if ($observer->destination('NOTIFY_HEADER_START_SHOPPING_CART', null) !== [47, 223]) {
    throw new RuntimeException('The current shipping-estimator POST destination was not detected.');
}

$_POST = [];
$_SESSION = ['cart_country_id' => 223, 'cart_zone' => 39];
if ($observer->destination('NOTIFY_HEADER_START_SHOPPING_CART', null) !== [39, 223]) {
    throw new RuntimeException('The saved shipping-estimator destination was not detected.');
}

$order = (object)['delivery' => ['country' => ['id' => 38], 'zone_id' => 9]];
if ($observer->destination('NOTIFY_HEADER_START_CHECKOUT', $order) !== [9, 38]) {
    throw new RuntimeException('The checkout delivery destination was not detected.');
}

$_SESSION = ['customer_id' => 12, 'sendto' => 7, 'customer_country_id' => 38, 'customer_zone_id' => 9];
if ($observer->destination('NOTIFY_HEADER_START_CHECKOUT_SHIPPING', null) !== [44, 223]) {
    throw new RuntimeException('The selected checkout shipping address was not detected.');
}

echo "Runtime checks passed.\n";
