<?php
$db->Execute("INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
        . "VALUES ('Try to Replace Products Automatically', 'PRODUCTS_RESTRICTED_REPLACE', 'false', 'Selected if you would like to have Restricted Products Replaced Automatically', " . $configuration_group_id . ", '4', 'zen_cfg_select_option(array(\'true\', \'false\'), ', now());");
$db->Execute("INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
        . "VALUES ('Replacement Product Model Suffix', 'PRODUCTS_RESTRICTED_REPLACE_MODEL_SUFFIX', '', 'Try and find a product with a module number, the same as the restricted product with the suffix of', " . $configuration_group_id . ", '5',  now());");

