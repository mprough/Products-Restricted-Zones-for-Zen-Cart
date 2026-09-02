<?php

function product_restricted_zone_cant($product_id,$zone_id){
    $result = false;
    if(PRODUCTS_RESTRICTED_ZONE_CANT_VALUES == ''){
        $result = false;
        return $result;
    }
    $entered_values_array = explode(",",PRODUCTS_RESTRICTED_ZONE_CANT_VALUES);
    foreach($entered_values_array as $values){
        $products_array = array();
        $entry = explode(":",$values);
        $selection = strtoupper($entry[0]);
        $zoned = (int)$entry[1];
        if(strpos($selection, "C") !== false){
            $category = str_replace("C", "", $selection);
            $cateories_array = zen_get_categories_products_list($category);
            foreach($cateories_array as $prdts => $path ){
                $products_array[] = $prdts;
            }
        }
        else{
            $products_array[] = $selection;
        }
        if(!in_array($product_id, $products_array)){
            $result = true;
            
        }
        $geo_zones = product_restricted_find_geo_zones($zone_id);
        if(in_array($zoned, $geo_zones) && in_array($product_id, $products_array)){
            $result = false;
        }
    }
    return $result;
}

function product_restricted_zone_only($product_id,$zone_id){
    $pass = true;
    if(PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES == ''){
        $result = true;
        return $result;
    }
    $entered_values_array = explode(",",PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES);
    foreach($entered_values_array as $values){
        $products_array = array();
        $entry = explode(":",$values);
        $selection = strtoupper($entry[0]);
        $zoned = (int)$entry[1];
        
        if(strpos($selection, "C") !== false){
            $category = str_replace("C", "", $selection);
            $cateories_array = zen_get_categories_products_list($category);
            foreach($cateories_array as $prdts => $path ){
                $products_array[] = $prdts;
            }
        }
        else{
            $products_array[] = $selection;
        }
        $geo_zones = product_restricted_find_geo_zones($zone_id);
        if(!in_array($product_id, $products_array)){
            $pass = true;
            return $pass;
        }
        if(in_array($product_id, $products_array) && !in_array($zoned, $geo_zones)){
            $pass = false;
            return $pass;
        }
    }
    return $pass;
}

function product_restricted_find_geo_zones($zone_id){
    global $db;
    $to_geo_zone_query = $db->Execute("SELECT * FROM ".TABLE_ZONES_TO_GEO_ZONES." WHERE zone_id=".(int)$zone_id);
    while(!$to_geo_zone_query->EOF){
        $geo_zones[] = $to_geo_zone_query->fields['geo_zone_id'];
        $to_geo_zone_query->MoveNext();
    }
    return $geo_zones;
}

function product_restricted_replace($product_id) {
    global $db;
    if (PRODUCTS_RESTRICTED_REPLACE == 'true') {
        $current_model = zen_products_lookup($product_id, 'products_model');
        $new_model = $db->Execute("SELECT products_id FROM " . TABLE_PRODUCTS . " WHERE products_model='" . $current_model . PRODUCTS_RESTRICTED_REPLACE_MODEL_SUFFIX . "'");
        if ($new_model->RecordCount() > 0) {
            $return = (int) $new_model->fields['products_id'];
        }
    } else {
        $return = 0;
    }
    return $return;
}
