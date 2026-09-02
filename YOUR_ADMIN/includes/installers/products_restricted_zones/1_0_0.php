<?php
// use $configuration_group_id where needed
$db->Execute("INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
        . "VALUES ('Enable Products Restricted Zones', 'PRODUCTS_RESTRICTED_ZONE_ENABLED', 'false', 'Selected if you would like to restrict products by zone', ".$configuration_group_id.", '1', 'zen_cfg_select_option(array(\'true\', \'false\'), ', now());");
$db->Execute("INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
        . "VALUES ('Only Ship These Products to these zones', 'PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES', '', 'Enter the zones that only these products can ship to. To include an entire category add a C and the the category id. <br /> Product:Zone <br />Example: <i>145:1,C27:2</i>', ".$configuration_group_id.", '2',  now());");
$db->Execute("INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
        . "VALUES ('Can Not Ship These Products to these Zones', 'PRODUCTS_RESTRICTED_ZONE_CANT_VALUES', '', 'Enter the zones that these products can not ship to. To include an entire category add a C and the the category id. <br /> Product:Zone <br />Example: <i>145:1,C27:2</i><br /> <b>NOTE: This takes priority over those that you can ship to</b>', ".$configuration_group_id.", '3',  now());");

// For Admin Pages

$zc150 = (PROJECT_VERSION_MAJOR > 1 || (PROJECT_VERSION_MAJOR == 1 && substr(PROJECT_VERSION_MINOR, 0, 3) >= 5));
if ($zc150) { // continue Zen Cart 1.5.0
  // delete configuration menu
  $db->Execute("DELETE FROM ".TABLE_ADMIN_PAGES." WHERE page_key = 'configProductsRestrictedZone' LIMIT 1;");
  // add configuration menu
  if (!zen_page_key_exists('configProductsRestrictedZone')) {
    if ((int)$configuration_group_id > 0) {
      zen_register_admin_page('configProductsRestrictedZone',
                              'BOX_CONFIGURATION_PRODUCTS_RESTRICTED_ZONE', 
                              'FILENAME_CONFIGURATION',
                              'gID=' . $configuration_group_id, 
                              'configuration', 
                              'Y',
                              $configuration_group_id);
        
      $messageStack->add('Enabled Products Restricted Zone Configuration Menu.', 'success');
    }
  }
}
