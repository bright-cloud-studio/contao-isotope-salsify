<?php

    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Product;
    
    // Debug mode and log file
    $debug_mode = true;
    if($debug_mode)
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/'.date('m_d_y').'_generate_products_.txt', "a+") or die("Unable to open file!");

    // INITIALIZE STUFFS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }
    
    // Store all of our products in data
    $products = array();
    
    // Get all the columns for the 'tl_iso_product' database, add to array with the default values
    $defaults = array();
    $default_query =  "DESCRIBE tl_iso_product";
    $default_result = $dbh->query($default_query);
    if($default_result) {
        while($default = $default_result->fetch_assoc()) {
            
            if($default['Extra'] == 'auto_increment') {
                // Skip auto_increment value, as we cant and shouldn't update this
            }
            else if($default['Null'] == 'YES') {
                // Set string 'NULL' value
                //$defaults[$default['Field']] = 'NULL';
                // Set actual NULL as value
                $defaults[$default['Field']] = NULL;
            }
            else
                $defaults[$default['Field']] = $default['Default'];
        }
    }


    
    ////////////////
    // STAGE DATA //
    ////////////////
    
    if($debug_mode)
        fwrite($log, "Staging Data \n");
    
    // Get Salsify Requests that are in the 'awaiting_cat_linking' state
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_iso_generation']);
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
		    if($debug_mode)
		        fwrite($log, "Getting Products in SalsifyRequest: ". $sr->id ." \n");
    
            // Loop through the Salsify Products
            $prod_query =  "SELECT * FROM tl_salsify_product WHERE published='1' AND pid='".$sr->id."' ORDER BY id ASC";
            $prod_result = $dbh->query($prod_query);
            if($prod_result) {
                while($prod = $prod_result->fetch_assoc()) {
                    
                    if($debug_mode)
                        fwrite($log, "Getting Attributes for SalsifyProduct: ". $prod['id'] ." \n");
                    
                    // Apply our "default" database values to the product data. This starts us out as "default", then we plug in the SalsifyAttribute values to replace the defaults
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']] = $defaults;
                    
                    $attr_query =  "SELECT * FROM tl_salsify_attribute WHERE pid='".$prod['id']."' AND published='1' ORDER BY id ASC";
                    $attr_result = $dbh->query($attr_query);
                    if($attr_result) {
                        while($attr = $attr_result->fetch_assoc()) {
                            
                            // Apply our SalsifyAttribute values to our product's data
                            $prod_values[$attr['attribute_key']] = $attr['attribute_value'];
                            
                            // If this product has a 'default_product_variant' attribute and it's set to 'true', set the fallback to 1 for this variant
                            if($attr['attribute_key'] == 'default_product_variant') {
                                if($attr['attribute_value'] == 'true') {
                                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['fallback'] = 1;
                                }
                            }
        
                            $iso_attr_query =  "SELECT * FROM tl_iso_attribute WHERE id='".$attr['linked_isotope_attribute']."' ORDER BY id ASC";
                            $iso_attr_result = $dbh->query($iso_attr_query);
                            if($iso_attr_result) {
                                while($iso_attr = $iso_attr_result->fetch_assoc()) {
                                    if($attr['linked_isotope_attribute_option']) {
                                        $asArray = unserialize($attr['linked_isotope_attribute_option']);
                                        $asCSV = implode(',', $asArray);
                                        $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']][$iso_attr['field_name']] = $asCSV;
                                    } else
                                        $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']][$iso_attr['field_name']] = $attr['attribute_value'];
                                }
                            }
                            
                        }
                    }
        
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['tstamp'] = time();
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['dateAdded'] = time();
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['type'] = $prod['isotope_product_type'];
                    
                    $cat_array = explode(",", $prod['category_page']);
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['orderPages'] = serialize($cat_array);
                    
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['name'] = $prod['product_name'];
                    
                    // If alias is going to be too long, make note in log file
                    if(strlen($prod['product_name']) > 125) {
                        if($debug_mode)
                            fwrite($log, "TRUNCATING alias \n");
                    }
                        
                        
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['alias'] = generateAlias($prod['product_name']);
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['sku'] = $prod['product_sku'];
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['description'] = $prod_values['full_description'];
                    $products[$prod['pid']][$prod['variant_group']][$prod['product_sku']]['published'] = 1;
                }
            }
            
            // Update the status of our Salsify Request and save it
            $sr->status = 'awaiting_related_linking';
            $sr->save();
    
		}
    }
    
    
    /////////////////////
    // CREATE PRODUCTS //
    /////////////////////
    
    if($debug_mode)
        fwrite($log, "Generating Products \n");
    
    // Tracks counts, displayed in Statistics section
    $count_single = 0;
    $count_variant = 0;
    $count_default_kickup = 0;
    $count_generated_parent = 0;
    


    foreach($products as $request_id => $request) {
        
        // Unpublish Isotope Products that belong to this SalsifyRequest
        if($debug_mode)
            fwrite($log, "Unpublishing Isotope Product created from SalsifyRequest: ".$request_id."\n");
        
        $our_request = SalsifyRequest::findBy(['id = ?'], [$request_id]);
        
        if($our_request) {
            foreach(unserialize($our_request->generated_isotope_products) as $unpublish_product_id) {
                $unpublish_product = Product::findOneBy(['tl_iso_product.id = ?'], [$unpublish_product_id]);
                if($unpublish_product) {
                    echo "Isotope Product Found!<br>";
                    $unpublish_product->published = '';
                    $unpublish_product->save();
                }
            }
        }
        
        // Tracks the IDs of the Isotope Products we generate, saving them will link our Isotope Products to our SalsifyRequest
        $generated_isotope_product_ids = array();
        
        foreach($request as $key => $group) {
            
            if(count($group) == 1) {
                
                // CREATE SINGLE PRODUCT
                $count_single++;
                foreach($group as $key2 => $prod) {
                    
                    // If we have a Product Page selected
                    $cat_id = unserialize($prod['orderPages']);
                    if($cat_id[0]) {
    
                        //////////////////////
                        // UPDATE OR INSERT //
                        //////////////////////
    
                        // Check if this product already exists
                        $update_ip = Product::findOneBy(['tl_iso_product.sku=?'],[$prod['sku']]);
                        if($update_ip != null) {
                            
                            if($debug_mode)
                                fwrite($log, "UPDATING single product: ". $update_ip->id ."\n");
                            
                            // Update the product
                            $prod_values_result = \Database::getInstance()->prepare("UPDATE tl_iso_product %s WHERE id=?")->set($prod)->execute($update_ip->id);
                            
                            // Delete all our entries in tl_iso_product_category
                            $result_delete_cats = $dbh->query("delete from tl_iso_product_category WHERE pid='".$update_ip->id."'");
                            
                            if($debug_mode)
                                fwrite($log, "DELETING existing category links \n");
                            
                            // re-add them
                            $prod_cat = array();
                            $prod_cat['pid'] = $update_ip->id;
                            $prod_cat['tstamp'] = time();
                            foreach($cat_id as $cat) {
                                $prod_cat['page_id'] = $cat;
                                $prod_cat_results = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_category %s")->set($prod_cat)->execute();
                                if($debug_mode)
                                    fwrite($log, "ADDING to category: ". $cat ."\n");
                            }
                            
                            // Save our Isotope Product ID for linking to our SalsifyRequest
                            $generated_isotope_product_ids[] = $update_ip->id;
                            
                            
                        } else {
                            
                            if($debug_mode)
                                fwrite($log, "CREATING new product \n");
                            
                            // Else, continue like normal
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
                            
                            // Save our Isotope Product ID for linking to our SalsifyRequest
                            $generated_isotope_product_ids[] = $prod_values_result->insertId;
                            
                        }
    
                    }
                    
                }
                
            } else {
                
                // CREATE VARIANT PRODUCT
    
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
                            
                            // If alias is going to be too long, make note in log file
                            if(strlen($key) > 125) {
                                if($debug_mode)
                                    fwrite($log, "TRUNCATING alias \n");
                            }
                            
                            $parent['alias'] = generateAlias($key);
                            $parent['sku'] = $parent['sku'] . "_parent";
                            
                            // Check if this product already exists
                            $update_ip = Product::findOneBy(['tl_iso_product.sku=?'],[$parent['sku']]);
                            if($update_ip != null) {
                                
                                if($debug_mode)
                                    fwrite($log, "UPDATING variant parent product: ". $update_ip->id ."\n");
                                
                                $prod_values_result = \Database::getInstance()->prepare("UPDATE tl_iso_product %s WHERE id=?")->set($parent)->execute($update_ip->id);
                                $parent_id = $update_ip->id;
                                
                                // Delete all our entries in tl_iso_product_category
                                $result_delete_cats = $dbh->query("delete from tl_iso_product_category WHERE pid='".$update_ip->id."'");
                                if($debug_mode)
                                    fwrite($log, "DELETING existing category links \n");
                                
                                // First, create entry in the 'tl_product_pricetier" table
                                $prod_cat = array();
                                $prod_cat['pid'] = $parent_id;
                                $prod_cat['tstamp'] = time();
                                foreach($cat_id as $cat) {
                                    $prod_cat['page_id'] = $cat;
                                    $prod_cat_results = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_category %s")->set($prod_cat)->execute();
                                }
                                
                                // Save our Isotope Product ID for linking to our SalsifyRequest
                                $generated_isotope_product_ids[] = $update_ip->id;
                                
                            } else {
                                
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
                                
                                // Save our Isotope Product ID for linking to our SalsifyRequest
                                $generated_isotope_product_ids[] = $prod_values_result->insertId;
                                    
                            }
                            
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
                            
                            // If the alias is too long, make note in log file
                            if(strlen($key) > 125) {
                                if($debug_mode)
                                    fwrite($log, "TRUNCATING alias \n");
                            }
                            $parent['alias'] = generateAlias($key);
                            $parent['sku'] = $parent['sku'] . "_parent";
                            
                            // Check if this product already exists
                            $update_ip = Product::findOneBy(['tl_iso_product.sku=?'],[$parent['sku']]);
                            if($update_ip != null) {
                                if($debug_mode)
                                    fwrite($log, "UPDATING variant non-default parent product: ". $update_ip->id ."\n");
                                
                                $prod_values_result = \Database::getInstance()->prepare("UPDATE tl_iso_product %s WHERE id=?")->set($parent)->execute($update_ip->id);
                                $parent_id = $update_ip->id;
                                
                                // Delete all our entries in tl_iso_product_category
                                $result_delete_cats = $dbh->query("delete from tl_iso_product_category WHERE pid='".$update_ip->id."'");
                                
                                if($debug_mode)
                                    fwrite($log, "DELETING existing category links \n");
                                
                                // First, create entry in the 'tl_product_pricetier" table
                                $prod_cat = array();
                                $prod_cat['pid'] = $prod_values_result->insertId;
                                $prod_cat['tstamp'] = time();
                                foreach($cat_id as $cat) {
                                    $prod_cat['page_id'] = $cat;
                                    $prod_cat_results = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_category %s")->set($prod_cat)->execute();
                                }
                                // Save our Isotope Product ID for linking to our SalsifyRequest
                                $generated_isotope_product_ids[] = $update_ip->id;
                                
                            } else {
                                
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
                                
                                // Save our Isotope Product ID for linking to our SalsifyRequest
                                $generated_isotope_product_ids[] = $prod_values_result->insertId;
                                    
                            }
                            
                        }
                    }
                    
                    // CREATE VARIANTS
                    $cat_id = unserialize($prod['orderPages']);
                    if($cat_id[0]) {
                        
                        $variant = $prod;
                        $variant['pid'] = $parent_id;
                        $variant['type'] = 0;
                        $variant['orderPages'] = NULL;
                        
                        // Check if this product already exists
                        $update_ip = Product::findOneBy(['tl_iso_product.sku=?'],[$variant['sku']]);
                        if($update_ip != null) {
                            if($debug_mode)
                                fwrite($log, "UPDATING variant product: ". $update_ip->id ."\n");
                            $prod_values_result = \Database::getInstance()->prepare("UPDATE tl_iso_product %s WHERE id=?")->set($variant)->execute($update_ip->id);
                            // Save our Isotope Product ID for linking to our SalsifyRequest
                            $generated_isotope_product_ids[] = $update_ip->id;
                        } else {
                            $prod_values_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")->set($variant)->execute();
                            // Save our Isotope Product ID for linking to our SalsifyRequest
                            $generated_isotope_product_ids[] = $prod_values_result->insertId;
                        }
                    }
    
                }
                
                
    
                
            }
            
            
        }
        
        // Update our SalsifyRequest by adding our linked Isotope Products
        if($our_request) {
            $our_request->generated_isotope_products = $generated_isotope_product_ids;
            $our_request->save();
        }
        
    }
    
    
    
    ////////////////
    // STATISTICS //
    ////////////////



    echo "Statistics:<br>";
    echo "Single Products: " . $count_single . "<br>";
    echo "Variant Product: " . $count_variant . "<br>";
    
    echo "Default Product Variant used as Parent: " . $count_default_kickup . "<br>";
    echo "Parent generated from first variant: " . $count_generated_parent . "<br>";

    // Close our logfile
    if($debug_mode)
        fclose($log);
    
    ///////////////
    // FUNCTIONS //
    ///////////////
    
    function generateAlias($text) {
        
        // 1. Convert to lowercase:
        $text = strtolower($text);
    
        $arrSearch = array('/[^\pN\pL \.\&\/_-]+/u', '/[ \.\&\/-]+/');
		$arrReplace = array('', '-');
		$text = preg_replace($arrSearch, $arrReplace, $text);
    
        // 4. Remove leading and trailing underscores:
        $text = trim($text, '-');
    
        // 5.  Handle empty strings:
        if (empty($text)) {
            $text = 'default_alias'; // Or any other default you prefer
        }
        $max_length = 125;
        $length_limited = substr($text, 0, $max_length);
        
        return $length_limited;
    }
