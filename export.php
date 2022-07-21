<?php

require_once('../app/Mage.php');
umask(0);
Mage::app('admin');

/*** POST DATA ***/
$step = $_POST['step'];
$store_id = strval($_POST['store_id']);
$timestamp = $_POST['timestamp'];

/*** ATTRIBUTES FOR CUSTOM FIELDS ***/
$attributes = ['ppc_approved', 'epr_midsize', 'epr_fullsize', 'epr_crew', 'general_polaris', 'aarm_guard_cat_attribute', 'air_intake_categoryoption', 'audio_categoryoption', 'axle_length_catoption', 'battery_categoryoption', 'bed_tailgate_catoption', 'bolt_pattern_catoption', 'brakes_catoption', 'cab_enclosures_catoption', 'cargo_racks_catoption', 'clutches_clutch_kits_catoption', 'doors_catoption', 'exhaust_categoryoption', 'farming_catoption', 'fenderflares_categoryoption', 'gun_racks_hunting_catoption', 'harness_categoryoption', 'hitchesframes_catoption', 'lift_kit_categoryoption', 'lighting_electrical_catoption', 'material_categoryoption', 'mirrors_catoption', 'mountsgrillsvisor_catoption', 'oil_change_catoption', 'portal_gear_catoption', 'rack_pinions_catoption', 'radius_rods_tie_rods_catoption', 'roofs_categoryoptions', 'roofs_catoption', 'seat_belts_safety_catoption', 'seats_roll_cages_catoption', 'shocks_springs_catoption', 'skid_plates_catoption', 'snow_plows_catoption', 'storage_catoption', 'streetlegal_categoryoption', 'tie_downs_catoption', 'tire_height_catoption', 'tire_offset_catoption', 'tire_ply_catoption', 'tire_rimdiameter_catoption', 'tire_type_catoption', 'tire_wheel_catoption', 'tire_width_catoption', 'turn_signal_catoption', 'vehicle_location_catoption', 'wheel_offset_catoption', 'whip_lights_flags_catoption', 'winch_access_catoption', 'winch_feature_lb', 'winches_rope_type', 'windshields_catoption'];

/*** PRODUCT QUERY ***/
$products = Mage::getModel('catalog/product')
    ->getCollection()
    ->addStoreFilter(Mage::app()->getStore($store_id))
    ->addAttributeToSelect('*')
    ->addAttributeToFilter('bigcommerce_id', ['neq' => ''])
    ->setPageSize(200)
    ->setCurPage($step);

$total_products = $products->getSize();
$total_pages = $products->getLastPageNumber();

if ($step > $total_pages) die(-1);

$cells = [];
$csv_headers = [['Product ID', 'Product Name', 'Product Custom Fields']];

foreach($products as $product) {
    $custom_fields = [];

    foreach($attributes as $attribute_code) {
        $attribute_text = $product->getAttributeText($attribute_code);
        $type = $product->getResource()->getAttribute($attribute_code)->getFrontendInput();

        if ($attribute_text) {
            $label = $product->getResource()->getAttribute($attribute_code)->getStoreLabel();

            switch($type) {
                case 'multiselect':
                    if (is_string($attribute_text)) {
                        $custom_fields[] = "{$attribute_code}={$attribute_text}";
                    } else {
                        foreach($attribute_text as $value) {
                            $custom_fields[] = "{$attribute_code}={$value}";
                        }
                    }
                    break;
                case 'select':
                    $custom_fields[] = "{$attribute_code}={$attribute_text}";
                    break;
            }
        }
    }

    $cells[] = [
        $product->getBigcommerceId(),
        $product->getName(),
        !empty($custom_fields) ? implode(';', $custom_fields) : ''
    ];

}

$products->clear();

/*** HANDLE CSV ***/
$file_name = "export-{$timestamp}.csv";

if ($step == 1) {
    $file = fopen($file_name, 'w');
    $csv = array_merge($csv_headers, $cells);
} else {
    $file = fopen($file_name, 'a');
    $csv = $cells;
}

foreach($csv as $row) {
    fputcsv($file, $row);
}

fclose($file);

/*** RESPONSE ***/
if ($step < $total_pages) {
    $step += 1;
    $message = $step * $batch_amount . ' of ' . $total_products . ' products exported';
} else {
    $step = 'done';
    $message = $total_products . ' products were exported. <a href="' . $file_name . '">Download File</a>';
}

echo json_encode([
    'step' => $step,
    'message' => $message
]);

exit;
