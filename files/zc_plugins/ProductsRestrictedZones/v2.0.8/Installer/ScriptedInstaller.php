<?php

declare(strict_types=1);

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected string $configPageKey = 'configProductsRestrictedZone';
    protected string $configGroupTitle = 'Products Restricted Zones';
    protected int $cgi;
    public string $pluginKey = 'ProductsRestrictedZones';
    public string $version = '2.0.8';

    protected function getOrCreateGroupId(): int
    {
        $versionConfig = $this->dbConn->Execute(
            "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'PRODUCTS_RESTRICTED_ZONE_VERSION' LIMIT 1"
        );
        if (!$versionConfig->EOF) {
            return (int)$versionConfig->fields['configuration_group_id'];
        }

        $result = $this->dbConn->Execute(
            "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_title IN ('Products Restricted Zones', 'Products Restricted Zone') ORDER BY configuration_group_id LIMIT 1"
        );
        if (!$result->EOF) {
            return (int)$result->fields['configuration_group_id'];
        }

        $this->executeInstallerSql(
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . " (configuration_group_title, configuration_group_description, sort_order, visible) VALUES ('Products Restricted Zones', 'Control product shipping by zone definition', 0, 1)"
        );
        $cgi = (int)$this->dbConn->Insert_ID();
        if ($cgi > 0) {
            $this->executeInstallerSql("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi");
        }
        return $cgi;
    }

    protected function executeInstall(): bool
    {
        $this->cgi = $this->getOrCreateGroupId();
        if ($this->cgi < 1) {
            return false;
        }

        $this->executeInstallerSql(
            "UPDATE " . TABLE_CONFIGURATION_GROUP . " SET configuration_group_title = 'Products Restricted Zones', configuration_group_description = 'Control product shipping by zone definition', visible = 1 WHERE configuration_group_id = {$this->cgi}"
        );
        $this->executeInstallerSql(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)
             VALUES
                ('Installed version', 'PRODUCTS_RESTRICTED_ZONE_VERSION', '2.0.8', 'Installed Products Restricted Zones version.', {$this->cgi}, 0, 'zen_cfg_select_option(array(\'2.0.8\'),', NOW()),
                ('Enable Products Restricted Zones', 'PRODUCTS_RESTRICTED_ZONE_ENABLED', 'false', 'Enable product and category shipping restrictions by zone definition.', {$this->cgi}, 10, 'zen_cfg_select_option(array(\'true\', \'false\'),', NOW()),
                ('Products limited to specific zones', 'PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES', '', 'Enter the product ID and each Zone Definition ID where that product may be shipped. Separate multiple rules with commas. Example: <code>145:1,145:2</code> allows product 145 to ship only to Zone Definitions 1 and 2. Use <code>C</code> before a category ID, such as <code>C27:2</code>, to apply the rule to every product in category 27.', {$this->cgi}, 20, NULL, NOW()),
                ('Products prohibited from specific zones', 'PRODUCTS_RESTRICTED_ZONE_CANT_VALUES', '', 'Enter the product ID and each Zone Definition ID where that product must not be shipped. Separate multiple rules with commas. Example: <code>145:1</code> prevents product 145 from shipping to Zone Definition 1. Use <code>C</code> before a category ID, such as <code>C27:2</code>, to apply the rule to every product in category 27. Prohibited-zone rules take priority.', {$this->cgi}, 30, NULL, NOW()),
                ('Replace restricted products automatically', 'PRODUCTS_RESTRICTED_REPLACE', 'false', 'Try to replace a restricted product with the product whose model has the configured suffix.', {$this->cgi}, 40, 'zen_cfg_select_option(array(\'true\', \'false\'),', NOW()),
                ('Replacement product model suffix', 'PRODUCTS_RESTRICTED_REPLACE_MODEL_SUFFIX', '', 'Suffix appended to the restricted product model when searching for a replacement product.', {$this->cgi}, 50, NULL, NOW())"
        );
        $this->executeInstallerSql(
            "UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '2.0.8', configuration_group_id = {$this->cgi}, set_function = 'zen_cfg_select_option(array(\'2.0.8\'),' WHERE configuration_key = 'PRODUCTS_RESTRICTED_ZONE_VERSION'"
        );
        $this->executeInstallerSql(
            "UPDATE " . TABLE_CONFIGURATION . " SET configuration_title = 'Products limited to specific zones', configuration_description = 'Enter the product ID and each Zone Definition ID where that product may be shipped. Separate multiple rules with commas. Example: <code>145:1,145:2</code> allows product 145 to ship only to Zone Definitions 1 and 2. Use <code>C</code> before a category ID, such as <code>C27:2</code>, to apply the rule to every product in category 27.' WHERE configuration_key = 'PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES'"
        );
        $this->executeInstallerSql(
            "UPDATE " . TABLE_CONFIGURATION . " SET configuration_title = 'Products prohibited from specific zones', configuration_description = 'Enter the product ID and each Zone Definition ID where that product must not be shipped. Separate multiple rules with commas. Example: <code>145:1</code> prevents product 145 from shipping to Zone Definition 1. Use <code>C</code> before a category ID, such as <code>C27:2</code>, to apply the rule to every product in category 27. Prohibited-zone rules take priority.' WHERE configuration_key = 'PRODUCTS_RESTRICTED_ZONE_CANT_VALUES'"
        );

        zen_deregister_admin_pages([$this->configPageKey]);
        zen_register_admin_page(
            $this->configPageKey,
            'BOX_CONFIGURATION_PRODUCTS_RESTRICTED_ZONE',
            'FILENAME_CONFIGURATION',
            "gID={$this->cgi}",
            'configuration',
            'Y',
            $this->cgi
        );
        return true;
    }

    protected function executeUpgrade(...$args): bool
    {
        return $this->executeInstall();
    }

    protected function executeUninstall(): bool
    {
        $group = $this->dbConn->Execute(
            "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'PRODUCTS_RESTRICTED_ZONE_VERSION' LIMIT 1"
        );
        $cgi = (int)($group->fields['configuration_group_id'] ?? 0);

        $this->executeInstallerSql("DELETE FROM " . TABLE_ADMIN_PAGES . " WHERE page_key = 'configProductsRestrictedZone'");
        $this->executeInstallerSql("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('PRODUCTS_RESTRICTED_ZONE_VERSION', 'PRODUCTS_RESTRICTED_ZONE_ENABLED', 'PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES', 'PRODUCTS_RESTRICTED_ZONE_CANT_VALUES', 'PRODUCTS_RESTRICTED_REPLACE', 'PRODUCTS_RESTRICTED_REPLACE_MODEL_SUFFIX')");
        if ($cgi > 0) {
            $this->executeInstallerSql("DELETE FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_id = $cgi");
        }
        return true;
    }
}
