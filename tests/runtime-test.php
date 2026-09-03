<?php

declare(strict_types=1);

class base
{
}

class TestRecordset
{
    public bool $EOF = false;
    public array $fields = ['geo_zone_id' => 2];

    public function MoveNext(): void
    {
        $this->EOF = true;
    }
}

class TestDatabase
{
    public function Execute(string $sql): TestRecordset
    {
        return new TestRecordset();
    }
}

define('TABLE_ZONES_TO_GEO_ZONES', 'zones_to_geo_zones');
define('PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES', '145:1,145:2');
define('PRODUCTS_RESTRICTED_ZONE_CANT_VALUES', '');

function zen_get_categories_products_list(int $categoryId): array
{
    return [];
}

$db = new TestDatabase();
require dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.2/catalog/includes/functions/extra_functions/products_restricted_zones.php';
require dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.2/catalog/includes/classes/observers/class.products_restricted_zones.php';

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

echo "Runtime checks passed.\n";
