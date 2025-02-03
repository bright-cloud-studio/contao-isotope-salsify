<?php

    // Initialize
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // Database Connection
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
    die("Connection failed: " . $dbh->connect_error);
    }
    
    
    // Loop through the Salsify Products
    $prod_query =  "SELECT * FROM tl_salsify_product ORDER BY id ASC";
    $prod_result = $dbh->query($prod_query);
    if($prod_result) {
        while($prod = $prod_result->fetch_assoc()) {
            
            $attr_query =  "SELECT * FROM tl_salsify_attribute WHERE pid='".$prod['id']."' ORDER BY id ASC";
            $attr_result = $dbh->query($attr_query);
            if($attr_result) {
                while($attr = $attr_result->fetch_assoc()) {


                    $prod_values[$attr['attribute_key']] = $attr['attribute_value'];
                }
            }


                    

                    // Fill in the rest of the product's information then create the product
                    $prod_values['tstamp'] = time();
                    $prod_values['dateAdded'] = time();
                    $prod_values['type'] = 5;
                    $prod_values['orderPages'] = 'a:1:{i:0;s:3:"109";}';
                    $prod_values['alias'] = $prod_values['item_number'];
                    $prod_values['name'] = $prod_values['specific_product_title'];
                    $prod_values['sku'] = $prod_values['item_number'];
                    $prod_values['description'] = $prod_values['full_description'];
                    $prod_values['published'] = 1;
                    $prod_values['upc'] = $prod_values['package_upc'];
                    $prod_values_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")
                     ->set($prod_values)
                     ->execute();
                     
                     
                     
                     // First, create entry in the 'tl_product_pricetier" table
                    $prod_cat = array();
                    $prod_cat['pid'] = $prod_values_result->insertId;
                    $prod_cat['tstamp'] = time();
                    $prod_cat['page_id'] = '109';
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
    
    echo "success";
