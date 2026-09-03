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
        $replacement = $type === 'integer' ? (string)(int)$value : "'" . addslashes((string)$value) . "'";
        return str_replace($placeholder, $replacement, $sql);
    }

    public function Execute(string $sql): TestRecordset
    {
        if (str_contains($sql, 'address_book')) {
            return new TestRecordset(['entry_zone_id' => 47, 'entry_country_id' => 223]);
        }
        if (str_contains($sql, 'zone_id = 13')) {
            return new TestRecordset(['geo_zone_id' => 1]);
        }
        if (str_contains($sql, 'products_model') && str_contains($sql, "'11275-ALT'")) {
            return new TestRecordset(['products_id' => 260]);
        }
        return new TestRecordset([]);
    }
}

define('TABLE_ZONES_TO_GEO_ZONES', 'zones_to_geo_zones');
define('TABLE_ADDRESS_BOOK', 'address_book');
define('TABLE_PRODUCTS', 'products');
define('PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES', '');
define('PRODUCTS_RESTRICTED_ZONE_CANT_VALUES', '305:1');
define('PRODUCTS_RESTRICTED_ZONE_ENABLED', 'true');
define('PRODUCTS_RESTRICTED_REPLACE', 'true');
define('PRODUCTS_RESTRICTED_REPLACE_MODEL_SUFFIX', '-ALT');
define('FILENAME_SHOPPING_CART', 'shopping_cart');

function zen_get_categories_products_list(int $categoryId): array
{
    return $categoryId === 15 ? [145 => '15'] : [];
}

function zen_get_products_name(int $productId): string
{
    return "Product $productId";
}

function zen_products_lookup(int $productId, string $field): string
{
    return $productId === 305 && $field === 'products_model' ? '11275' : '';
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
require dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.9/catalog/includes/functions/extra_functions/products_restricted_zones.php';
require dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.9/catalog/includes/classes/observers/class.products_restricted_zones.php';

if (!defined('TEXT_PRODUCTS_RESTRICTED_ZONE') || !defined('TEXT_PRODUCTS_RESTRICTED_REPLACEMENT')) {
    throw new RuntimeException('The storefront restriction messages must always be defined.');
}

if (product_restricted_zone_cant(305, 13, 223)) {
    throw new RuntimeException('Product 305 must be prohibited in its configured GA Zone Definition 1.');
}
if (!product_restricted_zone_cant(305, 47, 223)) {
    throw new RuntimeException('Product 305 must remain allowed in OH because OH is outside Zone Definition 1.');
}
if (product_restricted_replace(305) !== 260) {
    throw new RuntimeException('Model 11275 must resolve to replacement model 11275-ALT, product 260.');
}
$categoryRules = product_restricted_parse_rules('C15:1');
if (($categoryRules[0]['products'] ?? []) !== [145] || ($categoryRules[0]['geo_zone_id'] ?? 0) !== 1) {
    throw new RuntimeException('Category 15 must still expand to product 145 in Zone Definition 1.');
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
    'NOTIFY_CHECKOUT_ONE_CONFIRMATION_PRE_ORDER_CHECK',
    'NOTIFY_SHIPPING_MODULE_PRE_CALCULATE_BOXES_AND_TARE',
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

$autoLoaderSource = file_get_contents(dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.9/catalog/includes/auto_loaders/config.products_restricted_zones.php');
if (!str_contains($autoLoaderSource, 'init_products_restricted_zones_checkout.php')) {
    throw new RuntimeException('The direct checkout request guard is not registered.');
}

$checkoutGuardSource = file_get_contents(dirname(__DIR__) . '/files/zc_plugins/ProductsRestrictedZones/v2.0.9/catalog/includes/init_includes/init_products_restricted_zones_checkout.php');
foreach (['checkout_one', 'checkout_one_confirmation'] as $opcPage) {
    if (!str_contains($checkoutGuardSource, "'$opcPage'")) {
        throw new RuntimeException("The direct checkout request guard is missing OPC page $opcPage.");
    }
}

class TestCart
{
    public array $products = [['id' => '305', 'quantity' => 1]];
    public array $removed = [];
    public array $added = [];

    public function count_contents(): int
    {
        return 1;
    }

    public function get_products(): array
    {
        return $this->products;
    }

    public function remove(string $productId): void
    {
        $this->removed[] = $productId;
        $this->products = [];
    }

    public function add_cart(int $productId, float $quantity, array $attributes, bool $notify): void
    {
        $this->added[] = compact('productId', 'quantity', 'attributes', 'notify');
    }
}

class TestMessageStack
{
    public array $messages = [];

    public function add_session(string $class, string $message, string $type): void
    {
        $this->messages[] = compact('class', 'message', 'type');
    }

    public function add(string $class, string $message, string $type): void
    {
        $this->messages[] = compact('class', 'message', 'type');
    }
}

$_SESSION = ['cart' => new TestCart()];
$messageStack = new TestMessageStack();
$ohOrder = (object)['delivery' => ['country' => ['id' => 223], 'zone_id' => 13]];
$order = $ohOrder;
$notifier = new TestNotifier();
try {
    $observer->update($notifier, 'NOTIFY_HEADER_END_CHECKOUT_SHIPPING');
    throw new RuntimeException('The GA checkout was not redirected after replacement.');
} catch (RedirectException $redirect) {
    if ($redirect->getMessage() !== '/index.php?main_page=shopping_cart') {
        throw new RuntimeException('The GA checkout redirected to the wrong page.');
    }
}
if ($_SESSION['cart']->removed !== ['305'] || ($_SESSION['cart']->added[0]['productId'] ?? 0) !== 260) {
    throw new RuntimeException('The GA checkout did not replace product 305 with product 260.');
}

$messageStack = new TestMessageStack();
$error = false;
$_SESSION = ['cart' => new TestCart()];
try {
    $observer->updateNotifyCheckoutOneConfirmationPreOrderCheck(
        $notifier,
        'NOTIFY_CHECKOUT_ONE_CONFIRMATION_PRE_ORDER_CHECK',
        '',
        $error
    );
    throw new RuntimeException('The OPC GA checkout was not redirected after replacement.');
} catch (RedirectException $redirect) {
    if ($redirect->getMessage() !== '/index.php?main_page=shopping_cart') {
        throw new RuntimeException('The OPC GA checkout redirected to the wrong page.');
    }
}
if ($_SESSION['cart']->removed !== ['305'] || ($_SESSION['cart']->added[0]['productId'] ?? 0) !== 260) {
    throw new RuntimeException('The OPC GA checkout did not replace product 305 with product 260.');
}

$_SESSION = ['cart' => new TestCart(), 'cart_country_id' => 223, 'cart_zone' => 13];
$messageStack = new TestMessageStack();
$order = (object)['delivery' => ['country' => ['id' => 223], 'country_id' => 223, 'zone_id' => 13]];
$current_page_base = 'shopping_cart';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['action' => 'submit', 'zone_country_id' => '223', 'zone_id' => '13'];
try {
    $observer->updateNotifyShippingModulePreCalculateBoxesAndTare(
        $notifier,
        'NOTIFY_SHIPPING_MODULE_PRE_CALCULATE_BOXES_AND_TARE'
    );
    throw new RuntimeException('The submitted GA shipping estimate was not refreshed after replacement.');
} catch (RedirectException $redirect) {
    if ($redirect->getMessage() !== '/index.php?main_page=shopping_cart') {
        throw new RuntimeException('The shipping estimator replacement redirected to the wrong page.');
    }
}
if ($_SESSION['cart']->removed !== ['305'] || ($_SESSION['cart']->added[0]['productId'] ?? 0) !== 260) {
    throw new RuntimeException('The shipping estimator did not replace product 305 with product 260.');
}

echo "Runtime checks passed.\n";
