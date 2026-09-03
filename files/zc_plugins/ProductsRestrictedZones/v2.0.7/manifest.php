<?php

declare(strict_types=1);

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

return [
    'pluginVersion' => 'v2.0.7',
    'pluginName' => 'Products Restricted Zones',
    'pluginDescription' => 'Restrict individual products or categories by Zen Cart zone definition.',
    'pluginAuthor' => 'PRO-Webs.net',
    'pluginId' => 1981,
    'zcVersions' => ['v200', 'v210', 'v220'],
    'changelog' => 'https://github.com/mprough/Products-Restricted-Zones-for-Zen-Cart/blob/main/CHANGELOG.md',
    'github_repo' => 'https://github.com/mprough/Products-Restricted-Zones-for-Zen-Cart',
    'pluginGroups' => [],
];
