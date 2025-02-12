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
            $products[$prod['variant_group']][$prod['product_sku']]['type'] = 5;
            $products[$prod['variant_group']][$prod['product_sku']]['orderPages'] = serialize([$prod['category_page']]);
            $products[$prod['variant_group']][$prod['product_sku']]['alias'] = $prod_values['item_number'];
            $products[$prod['variant_group']][$prod['product_sku']]['name'] = $prod_values['specific_product_title'];
            $products[$prod['variant_group']][$prod['product_sku']]['sku'] = $prod_values['item_number'];
            $products[$prod['variant_group']][$prod['product_sku']]['description'] = $prod_values['full_description'];
            $products[$prod['variant_group']][$prod['product_sku']]['published'] = 1;
            //$products[$prod['variant_group']][$prod['product_sku']]['upc'] = $prod_values['package_upc'];

        }
    }
    
    
    
    // INSERT PRODUCTS INTO DATABASE
    
    // Loop through our groups
    $count_single = 0;
    $count_variant = 0;
    foreach($products as $key => $group) {
        
        // Build either a single product, or a variant
        if(count($group) == 1) {
            
            $count_single++;
            
            foreach($group as $key2 => $prod) {
                
                
                
                // If we have a Product Page selected
                $cat_id = unserialize($prod['orderPages']);
                if($cat_id[0]) {
                    
                    echo "PAGE: " . $cat_id[0] . "<br>";

                    $prod_values_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")->set($prod)->execute();
                    
                     // First, create entry in the 'tl_product_pricetier" table
                    $prod_cat = array();
                    $prod_cat['pid'] = $prod_values_result->insertId;
                    $prod_cat['tstamp'] = time();
                    $prod_cat['page_id'] = $cat_id[0];
                    $prod_cat_results = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_category %s")->set($prod_cat)->execute();
                    
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
            
            $count_variant++;
            
            // For now, we need to use the first loop to create the parent, track if it is that loop
            $create_parent = true;
            $parent_id = 0;
            
            foreach($group as $key2 => $prod) {
                
                /*
                if($create_parent) {
                    $create_parent = false;
                    
                    // CREATE PARENT PRODUCT
                    $parent['tstamp'] = time();
                    $parent['dateAdded'] = time();
                    $parent['type'] = 5;
                    $parent['orderPages'] = 'a:1:{i:0;s:3:"109";}';
                    $parent['alias'] = str_replace(' ', '_', strtolower($key));
                    $parent['name'] = $key;
                    $parent['sku'] = $prod_values['item_number'];
                    $parent['description'] = $prod_values['full_description'];
                    $parent['published'] = 1;
                    //$parent_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")->set($parent)->execute();
                    
                    $parent_id = $parent_result->insertId;
                    
                    // First, create entry in the 'tl_product_pricetier" table
                    $parent_cat = array();
                    $parent_cat['pid'] = $parent_result->insertId;
                    $parent_cat['tstamp'] = time();
                    $parent_cat['page_id'] = '109';
                    //$parent_cat_results = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_category %s")->set($parent_cat)->execute();
        
                     // Second, create entry in the 'tl_product_price' table                    
                    $price = array();
                    $price['pid'] = $parent_result->insertId;
                    $price['tstamp'] = time();
                    $price['tax_class'] = 1;
                    $price['config_id'] = 0;
                    $price['member_group'] = 0;
                    //$priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_price %s")->set($price)->execute();                                           
                                                             
                    // First, create entry in the 'tl_product_pricetier" table
                    $priceTier = array();
                    $priceTier['pid'] = $priceResult->insertId;
                    $priceTier['tstamp'] = time();
                    $priceTier['min'] = 1;
                    $priceTier['price'] = '1.00';
                    //$priceTierResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_pricetier %s")->set($priceTier)->execute();
                    
                } else {
                    
                    // CREATE CHILD
                    $parent['tstamp'] = time();
                    $parent['dateAdded'] = time();
                    $parent['pid'] = $parent_id;
                    $parent['type'] = 5;
                    $parent['orderPages'] = 'a:1:{i:0;s:3:"109";}';
                    $parent['alias'] = str_replace(' ', '_', strtolower($key));
                    $parent['name'] = $key;
                    $parent['sku'] = $prod_values['item_number'];
                    $parent['description'] = $prod_values['full_description'];
                    $parent['published'] = 1;
                    //$parent_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")->set($parent)->execute();
                    
                    // First, create entry in the 'tl_product_pricetier" table
                    $parent_cat = array();
                    $parent_cat['pid'] = $parent_result->insertId;
                    $parent_cat['tstamp'] = time();
                    $parent_cat['page_id'] = '109';
                    //$parent_cat_results = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_category %s")->set($parent_cat)->execute();
        
                     // Second, create entry in the 'tl_product_price' table                    
                    $price = array();
                    $price['pid'] = $parent_result->insertId;
                    $price['tstamp'] = time();
                    $price['tax_class'] = 1;
                    $price['config_id'] = 0;
                    $price['member_group'] = 0;
                    //$priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_price %s")->set($price)->execute();                                           
                                                             
                    // First, create entry in the 'tl_product_pricetier" table
                    $priceTier = array();
                    $priceTier['pid'] = $priceResult->insertId;
                    $priceTier['tstamp'] = time();
                    $priceTier['min'] = 1;
                    $priceTier['price'] = '1.00';
                    //$priceTierResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_pricetier %s")->set($priceTier)->execute();
                }
                
                */

            }
            
        }
        
        
    }

    echo "success";
