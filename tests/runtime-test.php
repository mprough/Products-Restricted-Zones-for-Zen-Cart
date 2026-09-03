<?php

declare(strict_types=1);

class base
{
}

class TestNotifier
{
    public array $events = [];

    public function attach(object $observer, array $events): void
    {
        $this->events = $events;
    }
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
            return new TestRecordset(['entry_zone_id' => 47, 'entry_country_id' => 223]);
        }
        if (str_contains($sql, 'zone_id = 13')) {
            return new TestRecordset(['geo_zone_id' => 1]);
        }
        return new TestRecordset([]);
    }
}

define('TABLE_ZONES_TO_GEO_ZONES', 'zones_to_geo_zones');
define('TABLE_ADDRESS_BOOK', 'address_book');
define('PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES', 'C15:1');
define('PRODUCTS_RESTRICTED_ZONE_CANT_VALUES', '');
define('PRODUCTS_RESTRICTED_ZONE_ENABLED', 'true');
define('PRODUCTS_RESTRICTED_REPLACE', 'false');
define('PRODUCTS_RESTRICTED_REPLACE_MODEL_SUFFIX', '');
define('FILENAME_SHOPPING_CART', 'shopping_cart');

function zen_get_categories_products_list(int $categoryId): array
{
    return $categoryId === 15 ? [145 => '15'] : [];
}

function zen_get_products_name(int $productId): string
{
    return "Product $productId";
}

function zen_href_link(string $page): string
{
    return "/index.php?main_page=$page";
}

class RedirectException extends RuntimeException
{
}

function zen_redirect(string $url): never
{
    throw new RedirectException($url);
}

$db = new TestDatabase();
require dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.7/catalog/includes/functions/extra_functions/products_restricted_zones.php';
require dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.7/catalog/includes/classes/observers/class.products_restricted_zones.php';

if (!defined('TEXT_PRODUCTS_RESTRICTED_ZONE') || !defined('TEXT_PRODUCTS_RESTRICTED_REPLACEMENT')) {
    throw new RuntimeException('The storefront restriction messages must always be defined.');
}

if (!product_restricted_zone_only(145, 13, 223)) {
    throw new RuntimeException('Category 15 must be allowed for its configured GA Zone Definition 1.');
}
if (product_restricted_zone_only(145, 47, 223)) {
    throw new RuntimeException('Category 15 must be blocked for OH because OH is outside Zone Definition 1.');
}
if (!product_restricted_zone_only(999, 10, 223)) {
    throw new RuntimeException('A product without an only-ship rule must remain unrestricted.');
}
if (!product_restricted_zone_cant(145, 10, 223)) {
    throw new RuntimeException('An empty prohibited-zone setting must not restrict products.');
}

$zco_notifier = new TestNotifier();
new productsRestrictedZones();
foreach ([
    'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
    'NOTIFY_HEADER_END_CHECKOUT_SHIPPING',
    'NOTIFY_HEADER_START_CHECKOUT_PAYMENT',
    'NOTIFY_HEADER_END_CHECKOUT_PAYMENT',
    'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION',
    'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION',
    'NOTIFY_HEADER_START_CHECKOUT_PROCESS',
] as $requiredCheckoutNotifier) {
    if (!in_array($requiredCheckoutNotifier, $zco_notifier->events, true)) {
        throw new RuntimeException("Checkout enforcement is missing $requiredCheckoutNotifier.");
    }
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
if ($observer->destination('NOTIFY_HEADER_START_CHECKOUT_PAYMENT', $order) !== [9, 38]) {
    throw new RuntimeException('The checkout delivery destination was not detected.');
}

$_SESSION = ['customer_id' => 12, 'sendto' => 7, 'customer_country_id' => 38, 'customer_zone_id' => 9];
if ($observer->destination('NOTIFY_HEADER_START_CHECKOUT_SHIPPING', null) !== [47, 223]) {
    throw new RuntimeException('The selected checkout shipping address was not detected.');
}

$_SESSION = ['customer_id' => 12, 'customer_default_address_id' => 7];
if ($observer->destination('PRODUCTS_RESTRICTED_ZONES_CHECKOUT_GUARD', null) !== [47, 223]) {
    throw new RuntimeException('The default checkout shipping address was not detected before Zen Cart sets sendto.');
}

$autoLoaderSource = file_get_contents(dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.7/catalog/includes/auto_loaders/config.products_restricted_zones.php');
if (!str_contains($autoLoaderSource, 'init_products_restricted_zones_checkout.php')) {
    throw new RuntimeException('The direct checkout request guard is not registered.');
}

class TestCart
{
    public function count_contents(): int
    {
        return 1;
    }

    public function get_products(): array
    {
        return [['id' => '145', 'quantity' => 1]];
    }
}

class TestMessageStack
{
    public array $messages = [];

    public function add_session(string $class, string $message, string $type): void
    {
        $this->messages[] = compact('class', 'message', 'type');
    }
}

$_SESSION = ['cart' => new TestCart()];
$messageStack = new TestMessageStack();
$ohOrder = (object)['delivery' => ['country' => ['id' => 223], 'zone_id' => 47]];
$order = $ohOrder;
$notifier = new TestNotifier();
try {
    $observer->update($notifier, 'NOTIFY_HEADER_END_CHECKOUT_SHIPPING');
    throw new RuntimeException('The OH checkout was not redirected.');
} catch (RedirectException $redirect) {
    if ($redirect->getMessage() !== '/index.php?main_page=shopping_cart') {
        throw new RuntimeException('The OH checkout redirected to the wrong page.');
    }
}
if ($messageStack->messages === []) {
    throw new RuntimeException('The blocked OH checkout did not retain its warning.');
}

echo "Runtime checks passed.\n";
