<?php

/**
 * Return true when the product is allowed by the configured do-not-ship rules.
 */
function product_restricted_zone_cant(int $productId, int $zoneId, int $countryId = 0): bool
{
    $rules = product_restricted_parse_rules((string)PRODUCTS_RESTRICTED_ZONE_CANT_VALUES);
    if ($rules === []) {
        return true;
    }

    $geoZones = product_restricted_find_geo_zones($zoneId, $countryId);
    foreach ($rules as $rule) {
        if (in_array($productId, $rule['products'], true) && in_array($rule['geo_zone_id'], $geoZones, true)) {
            return false;
        }
    }
    return true;
}

/**
 * Return true when the product is allowed by the configured only-ship rules.
 */
function product_restricted_zone_only(int $productId, int $zoneId, int $countryId = 0): bool
{
    $rules = product_restricted_parse_rules((string)PRODUCTS_RESTRICTED_ZONE_ONLY_VALUES);
    if ($rules === []) {
        return true;
    }

    $geoZones = product_restricted_find_geo_zones($zoneId, $countryId);
    $productHasRule = false;
    foreach ($rules as $rule) {
        if (!in_array($productId, $rule['products'], true)) {
            continue;
        }
        $productHasRule = true;
        if (in_array($rule['geo_zone_id'], $geoZones, true)) {
            return true;
        }
    }
    return !$productHasRule;
}

/**
 * Parse Product:Zone and CCategory:Zone rules, ignoring incomplete entries.
 *
 * @return array<int, array{products: array<int, int>, geo_zone_id: int}>
 */
function product_restricted_parse_rules(string $configuredRules): array
{
    static $cache = [];
    if (isset($cache[$configuredRules])) {
        return $cache[$configuredRules];
    }

    $rules = [];
    foreach (explode(',', $configuredRules) as $configuredRule) {
        $parts = array_map('trim', explode(':', $configuredRule, 2));
        if (count($parts) !== 2 || $parts[0] === '' || !ctype_digit($parts[1]) || (int)$parts[1] < 1) {
            continue;
        }

        $selection = strtoupper($parts[0]);
        $products = [];
        if (str_starts_with($selection, 'C') && ctype_digit(substr($selection, 1))) {
            $categoryId = (int)substr($selection, 1);
            foreach ((array)zen_get_categories_products_list($categoryId) as $productId => $path) {
                $products[] = (int)$productId;
            }
        } elseif (ctype_digit($selection) && (int)$selection > 0) {
            $products[] = (int)$selection;
        }

        if ($products !== []) {
            $rules[] = [
                'products' => array_values(array_unique($products)),
                'geo_zone_id' => (int)$parts[1],
            ];
        }
    }

    $cache[$configuredRules] = $rules;
    return $rules;
}

/**
 * Find every zone definition that contains the delivery zone or its entire country.
 *
 * @return array<int, int>
 */
function product_restricted_find_geo_zones(int $zoneId, int $countryId = 0): array
{
    global $db;

    static $cache = [];
    $cacheKey = $countryId . ':' . $zoneId;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    if ($countryId > 0) {
        $where = "zone_country_id = $countryId AND (zone_id = 0 OR zone_id = $zoneId)";
    } else {
        $where = "zone_id = $zoneId";
    }
    $result = $db->Execute(
        "SELECT DISTINCT geo_zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE $where"
    );

    $geoZones = [];
    while (!$result->EOF) {
        $geoZones[] = (int)$result->fields['geo_zone_id'];
        $result->MoveNext();
    }

    $cache[$cacheKey] = $geoZones;
    return $geoZones;
}

function product_restricted_replace(int $productId): int
{
    global $db;

    if (PRODUCTS_RESTRICTED_REPLACE !== 'true') {
        return 0;
    }

    $suffix = trim((string)PRODUCTS_RESTRICTED_REPLACE_MODEL_SUFFIX);
    $currentModel = trim((string)zen_products_lookup($productId, 'products_model'));
    if ($currentModel === '' || $suffix === '') {
        return 0;
    }

    $model = $currentModel . $suffix;
    $sql = "SELECT products_id FROM " . TABLE_PRODUCTS . " WHERE products_model = :model: AND products_status = 1 AND products_id <> :productId: LIMIT 1";
    $sql = $db->bindVars($sql, ':model:', $model, 'string');
    $sql = $db->bindVars($sql, ':productId:', $productId, 'integer');
    $replacement = $db->Execute($sql);

    return $replacement->EOF ? 0 : (int)$replacement->fields['products_id'];
}
