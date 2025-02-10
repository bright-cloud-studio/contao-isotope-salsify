<?php

    // INITIALIZE STUFFS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
    die("Connection failed: " . $dbh->connect_error);
    }
    
    // Store all of our products in data
    $products = array();
    
    
    
    // STAGE DATA
    
    // Loop through the Salsify Products
    $prod_query =  "SELECT * FROM tl_salsify_product ORDER BY id ASC";
    $prod_result = $dbh->query($prod_query);
    if($prod_result) {
        while($prod = $prod_result->fetch_assoc()) {
            
            //$products[$prod->variant_group][$prod->product_sku];
            
            $attr_query =  "SELECT * FROM tl_salsify_attribute WHERE pid='".$prod['id']."' ORDER BY id ASC";
            $attr_result = $dbh->query($attr_query);
            if($attr_result) {
                while($attr = $attr_result->fetch_assoc()) {

                    $prod_values[$attr['attribute_key']] = $attr['attribute_value'];
                    $products[$prod['variant_group']][$prod['product_sku']][$attr['attribute_key']] = $attr['attribute_value'];
                    
                }
            }

            $products[$prod['variant_group']][$prod['product_sku']]['tstamp'] = time();
            $products[$prod['variant_group']][$prod['product_sku']]['dateAdded'] = time();
            $products[$prod['variant_group']][$prod['product_sku']]['type'] = 5;
            $products[$prod['variant_group']][$prod['product_sku']]['orderPages'] = 'a:1:{i:0;s:3:"109";}';
            $products[$prod['variant_group']][$prod['product_sku']]['alias'] = $prod_values['item_number'];
            $products[$prod['variant_group']][$prod['product_sku']]['name'] = $prod_values['specific_product_title'];
            $products[$prod['variant_group']][$prod['product_sku']]['sku'] = $prod_values['item_number'];
            $products[$prod['variant_group']][$prod['product_sku']]['description'] = $prod_values['full_description'];
            $products[$prod['variant_group']][$prod['product_sku']]['published'] = 1;
            $products[$prod['variant_group']][$prod['product_sku']]['upc'] = $prod_values['package_upc'];

        }
    }
    
    
    
    // INSERT PRODUCTS INTO DATABASE
    
    // Loop through our groups
    foreach($products as $group) {
        
        // Build either a single product, or a variant
        if(count($group) == 1) {
            echo "SINGLE PRODUCT<br>";
        } else {
            echo "VARIANT<br>";
        }
        
        
    }
    
    
    
    //echo "<pre>";
    //print_r($products);
    //echo "</pre><br><hr><br>";
            
    echo "success";
