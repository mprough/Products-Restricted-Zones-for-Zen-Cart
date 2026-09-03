<?php

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$autoLoadConfig[90][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/class.products_restricted_zones.php',
];
$autoLoadConfig[90][] = [
    'autoType' => 'classInstantiate',
    'className' => 'productsRestrictedZones',
    'objectName' => 'productsRestrictedZones',
];
