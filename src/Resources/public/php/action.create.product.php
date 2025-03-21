<?php

    // INITIALIZE STUFFS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    $dbh = new mysqli("localhost", "ecom_user", 'I6aX,Ud-EYa^]P9u8g', "ecom_contao_4_13");
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
                    
                    // If this product has a 'default_product_variant' attribute and it's set to 'true', set the fallback to 1 for this variant
                    if($attr['attribute_key'] == 'default_product_variant') {
                        if($attr['attribute_value'] == 'true') {
                            $products[$prod['variant_group']][$prod['product_sku']]['fallback'] = 1;
                        }
                    }
                    

                    $iso_attr_query =  "SELECT * FROM tl_iso_attribute WHERE id='".$attr['linked_isotope_attribute']."' ORDER BY id ASC";
                    $iso_attr_result = $dbh->query($iso_attr_query);
                    if($iso_attr_result) {
                        while($iso_attr = $iso_attr_result->fetch_assoc()) {
                            
                            if($attr['linked_isotope_attribute_option'])
                                $products[$prod['variant_group']][$prod['product_sku']][$iso_attr['field_name']] = $attr['linked_isotope_attribute_option'];
                            else
                                $products[$prod['variant_group']][$prod['product_sku']][$iso_attr['field_name']] = $attr['attribute_value'];
                        }
                    }
                    
                    
                    
                    
                }
            }

            

            $products[$prod['variant_group']][$prod['product_sku']]['tstamp'] = time();
            $products[$prod['variant_group']][$prod['product_sku']]['dateAdded'] = time();
            $products[$prod['variant_group']][$prod['product_sku']]['type'] = $prod['isotope_product_type'];
            
            $cat_array = explode(",", $prod['category_page']);
            $products[$prod['variant_group']][$prod['product_sku']]['orderPages'] = serialize($cat_array);
            
            $products[$prod['variant_group']][$prod['product_sku']]['name'] = $prod['product_name'];
            $products[$prod['variant_group']][$prod['product_sku']]['alias'] = generateAlias($prod['product_name']);
            $products[$prod['variant_group']][$prod['product_sku']]['sku'] = $prod['product_sku'];
            $products[$prod['variant_group']][$prod['product_sku']]['description'] = $prod_values['full_description'];
            $products[$prod['variant_group']][$prod['product_sku']]['published'] = 1;
            //$products[$prod['variant_group']][$prod['product_sku']]['upc'] = $prod_values['package_upc'];

        }
    }
    
    
    
    // INSERT PRODUCTS INTO DATABASE
    
    // Loop through our groups
    $count_single = 0;
    $count_variant = 0;
    $count_default_kickup = 0;
    $count_generated_parent = 0;
    
    //echo "<pre>";
    //print_r($products);
    //die();
    
    foreach($products as $key => $group) {
        
        // Build either a single product, or a variant
        if(count($group) == 1) {
            
            $count_single++;
            
            foreach($group as $key2 => $prod) {
                
                // If we have a Product Page selected
                $cat_id = unserialize($prod['orderPages']);
                if($cat_id[0]) {

                    $prod_values_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")->set($prod)->execute();
                    
                    $prod_cat = array();
                    $prod_cat['pid'] = $prod_values_result->insertId;
                    $prod_cat['tstamp'] = time();
                    foreach($cat_id as $cat) {
                        $prod_cat['page_id'] = $cat;
                        $prod_cat_results = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_category %s")->set($prod_cat)->execute();
                    }
                    
                    // Second, create entry in the 'tl_product_price' table                    
                    $price = array();
                    $price['pid'] = $prod_values_result->insertId;
                    $price['tstamp'] = time();
                    $price['tax_class'] = 1;
                    $price['config_id'] = 0;
                    $price['member_group'] = 0;
                    $priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_price %s")->set($price)->execute();
                    
                    // First, create entry in the 'tl_product_pricetier" table
                    $priceTier = array();
                    $priceTier['pid'] = $priceResult->insertId;
                    $priceTier['tstamp'] = time();
                    $priceTier['min'] = 1;
                    $priceTier['price'] = '1.00';
                    $priceTierResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_pricetier %s")->set($priceTier)->execute();
                    
                }
                
            }
            
        } else {
            
            // VARIANT

            // For now, we need to use the first loop to create the parent, track if it is that loop
            $create_parent = true;
            $parent_id = 0;
            
            // First, create a parent if we can. If not, continue
            foreach($group as $key2 => $prod) {

                // CREATE PARENT PRODUCT
                if($prod['default_product_variant'] == 'true') {
                    $count_default_kickup++;
                    
                    $create_parent = false;
                    
                    $cat_id = unserialize($prod['orderPages']);
                    if($cat_id[0]) {
                        
                        $parent = $prod;
                        $parent['name'] = $key;
                        //$parent['name'] = "DEFAULT PRODUCT VARIANT: " . $key;
                        $parent['alias'] = generateAlias($key);
                        $parent['sku'] = $parent['sku'] . "_parent";
 
                        $prod_values_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")->set($parent)->execute();
                        $parent_id = $prod_values_result->insertId;

                        
                         // First, create entry in the 'tl_product_pricetier" table
                        $prod_cat = array();
                        $prod_cat['pid'] = $prod_values_result->insertId;
                        $prod_cat['tstamp'] = time();
                        foreach($cat_id as $cat) {
                            $prod_cat['page_id'] = $cat;
                            $prod_cat_results = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_category %s")->set($prod_cat)->execute();
                        }
                        
                        // Second, create entry in the 'tl_product_price' table                    
                        $price = array();
                        $price['pid'] = $prod_values_result->insertId;
                        $price['tstamp'] = time();
                        $price['tax_class'] = 1;
                        $price['config_id'] = 0;
                        $price['member_group'] = 0;
                        $priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_price %s")->set($price)->execute();
                        
                        // First, create entry in the 'tl_product_pricetier" table
                        $priceTier = array();
                        $priceTier['pid'] = $priceResult->insertId;
                        $priceTier['tstamp'] = time();
                        $priceTier['min'] = 1;
                        $priceTier['price'] = '1.00';
                        $priceTierResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_pricetier %s")->set($priceTier)->execute();
                    }
                }
            }
            
            // Now, continue as normal.
            // $create_parent will be set to false if the parent was made earlier
            // If not, it will use the first product as our fallback.
            
            foreach($group as $key2 => $prod) {
                $count_variant++;

                // CREATE PARENT PRODUCT
                if($create_parent) {
                    $count_generated_parent++;
                    
                    $create_parent = false;
                    
                    $cat_id = unserialize($prod['orderPages']);
                    if($cat_id[0]) {
                        
                        $parent = $prod;
                        $parent['name'] = $key;
                        //$parent['name'] = "GENERATED PARENT: " . $key;
                        $parent['alias'] = generateAlias($key);
                        $parent['sku'] = $parent['sku'] . "_parent";
 
                        $prod_values_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")->set($parent)->execute();
                        $parent_id = $prod_values_result->insertId;

                        
                         // First, create entry in the 'tl_product_pricetier" table
                        $prod_cat = array();
                        $prod_cat['pid'] = $prod_values_result->insertId;
                        $prod_cat['tstamp'] = time();
                        foreach($cat_id as $cat) {
                            $prod_cat['page_id'] = $cat;
                            $prod_cat_results = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_category %s")->set($prod_cat)->execute();
                        }
                        
                        // Second, create entry in the 'tl_product_price' table                    
                        $price = array();
                        $price['pid'] = $prod_values_result->insertId;
                        $price['tstamp'] = time();
                        $price['tax_class'] = 1;
                        $price['config_id'] = 0;
                        $price['member_group'] = 0;
                        $priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_price %s")->set($price)->execute();
                        
                        // First, create entry in the 'tl_product_pricetier" table
                        $priceTier = array();
                        $priceTier['pid'] = $priceResult->insertId;
                        $priceTier['tstamp'] = time();
                        $priceTier['min'] = 1;
                        $priceTier['price'] = '1.00';
                        $priceTierResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_pricetier %s")->set($priceTier)->execute();
                    }
                }
                
                // CREATE VARIANTS
                $cat_id = unserialize($prod['orderPages']);
                if($cat_id[0]) {
                    
                    $variant = $prod;
                    $variant['pid'] = $parent_id;
                    $variant['type'] = 0;
                    $variant['orderPages'] = NULL;
                    $prod_values_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")->set($variant)->execute();
                }

            }
            
            
            
            
            
            
            
            
            
            
            
        }
        
        
    }

    echo "Statistics:<br>";
    echo "Single Products: " . $count_single . "<br>";
    echo "Variant Product: " . $count_variant . "<br>";
    
    echo "Default Product Variant used as Parent: " . $count_default_kickup . "<br>";
    echo "Parent generated from first variant: " . $count_generated_parent . "<br>";

    
    
    function generateAlias($text) {
        // 1. Convert to lowercase:
        $text = strtolower($text);
    
        // 2. Replace all non-alphanumeric characters with underscores:
        $text = preg_replace('/[^a-z0-9_]/', '_', $text);
    
        // 3. Remove multiple consecutive underscores:
        $text = preg_replace('/_+/', '_', $text);
    
        // 4. Remove leading and trailing underscores:
        $text = trim($text, '_');
    
        // 5.  Handle empty strings:
        if (empty($text)) {
            $text = 'default_alias'; // Or any other default you prefer
        }
    
        return $text;
    }
