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
    public string $version = '2.0.0';

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
                ('Installed version', 'PRODUCTS_RESTRICTED_ZONE_VERSION', '2.0.0', 'Installed Products Restricted Zones version.', {$this->cgi}, 0, 'zen_cfg_select_option(array(\'2.0.0\'),', NOW()),
                ('Enable Products Restricted Zones', 'PRODUCTS_RESTRICTED_ZONE_ENABLED', 'false', 'Enable product and category shipping restrictions by zone definition.', {$this->cgi}, 10, 'zen_cfg_select_option(array(\'true\', \'false\'),', NOW()),
                ('Only ship these products to these zones', 'PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES', '', 'Comma-separated Product:Zone entries. Prefix a category ID with C. Examples: 145:1,C27:2. A listed product can ship only to its listed zones.', {$this->cgi}, 20, NULL, NOW()),
                ('Do not ship these products to these zones', 'PRODUCTS_RESTRICTED_ZONE_CANT_VALUES', '', 'Comma-separated Product:Zone entries. Prefix a category ID with C. Examples: 145:1,C27:2. A do-not-ship rule takes priority.', {$this->cgi}, 30, NULL, NOW()),
                ('Replace restricted products automatically', 'PRODUCTS_RESTRICTED_REPLACE', 'false', 'Try to replace a restricted product with the product whose model has the configured suffix.', {$this->cgi}, 40, 'zen_cfg_select_option(array(\'true\', \'false\'),', NOW()),
                ('Replacement product model suffix', 'PRODUCTS_RESTRICTED_REPLACE_MODEL_SUFFIX', '', 'Suffix appended to the restricted product model when searching for a replacement product.', {$this->cgi}, 50, NULL, NOW())"
        );
        $this->executeInstallerSql(
            "UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '2.0.0', configuration_group_id = {$this->cgi}, set_function = 'zen_cfg_select_option(array(\'2.0.0\'),' WHERE configuration_key = 'PRODUCTS_RESTRICTED_ZONE_VERSION'"
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
