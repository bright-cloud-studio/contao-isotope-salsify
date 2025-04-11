<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Attribute;
    
    // Stores log messages until the end
    $log_messages = '';
    $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/salsify_autolink_'.strtolower(date('m_d_y_H:m:s')).".txt", "w") or die("Unable to open file!");
    
    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }
    
    ////////////////////////////////////////////////////////////////////////////////////////
    // AUTO-LINK SalsifyAttribute to IsotopeAttribue based on field name and product type //
    ////////////////////////////////////////////////////////////////////////////////////////

    // Loop through all Salsify Products
    $sp_query =  "SELECT * FROM tl_salsify_product ORDER BY id ASC";
    $sp_result = $dbh->query($sp_query);
    if($sp_result) {
        while($product = $sp_result->fetch_assoc()) {
            
            // Save our product types
            $product_single = '';
            $product_variant = '';

            // Get 'Grouping' attribute, get Isotope Product Types
            $grouping_query =  "SELECT * FROM tl_salsify_attribute WHERE pid='".$product['id']."' AND is_grouping='1' AND isotope_product_type IS NOT NULL AND isotope_product_type_variant IS NOT NULL ORDER BY id ASC";
            $grouping_result = $dbh->query($grouping_query);
            if($grouping_result) {
                while($grouping = $grouping_result->fetch_assoc()) {
                    
                    $product_single = $grouping['isotope_product_type'];
                    $product_variant = $grouping['isotope_product_type_variant'];
                }
            }

            // Now, if we have our product types we can auto-link attributes
            if($product_single != '' && $product_variant != '') {
                

                // Loop through each Salsify Attribute
                $sa_query =  "SELECT * FROM tl_salsify_attribute WHERE pid='".$product['id']."' AND linked_isotope_attribute IS NULL ORDER BY id ASC";
                $sa_result = $dbh->query($sa_query);
                if($sa_result) {
                    while($attribute = $sa_result->fetch_assoc()) {
                        
                        
                        // Find an Isotope Attribute that belongs to our product type and has the same key
                        //echo "Attribute Key: " . $attribute['attribute_key'] . "<br>";
                        
                        switch ($product['isotope_product_variant_type']) {
                            case "single":
                                
                                // get our Product Type
                                $pt_query =  "SELECT * FROM tl_iso_producttype WHERE id='".$product_single."' ORDER BY id ASC";
                                $pt_result = $dbh->query($pt_query);
                                if($pt_result) {
                                    while($product_type = $pt_result->fetch_assoc()) {
                                        
                                        // Find an Isotope Attribute that belongs to our product type and has the same key
                                        $contains = str_contains($product_type['attributes'], $attribute['attribute_key']);
                                        
                                        if($contains) {
                                            
                                            // find the ID for the tl_iso_attribute with this field name
                                            $ia_query =  "SELECT * FROM tl_iso_attribute WHERE field_name='".$attribute['attribute_key']."' ORDER BY id ASC";
                                            $ia_result = $dbh->query($ia_query);
                                            if($ia_result) {
                                                while($iso_attribute = $ia_result->fetch_assoc()) {
                                                    
                                                    echo "Linked: single<br>";

                                                    $update =  "update tl_salsify_attribute set linked_isotope_attribute='".$iso_attribute['id']."' WHERE id='".$attribute['id']."'";
                                                    $result_update = $dbh->query($update);
                                            
                                                    fwrite($myfile, "Linked Salsify Attribute ID: " . $attribute['id'] . " to Isotope Attribute ID: " . $iso_attribute['id'] . "\n");

                                                }
                                            }

                                        }

                                    }
                                }
                                
                                
                                break;
                            case "variant":
                                
                                // get our Product Type
                                $pt_query =  "SELECT * FROM tl_iso_producttype WHERE id='".$product_single."' ORDER BY id ASC";
                                $pt_result = $dbh->query($pt_query);
                                if($pt_result) {
                                    while($product_type = $pt_result->fetch_assoc()) {
                                        
                                        // Find an Isotope Attribute that belongs to our product type and has the same key
                                        $contains = str_contains($product_type['attributes'], $attribute['attribute_key']);
                                        $contains_v = str_contains($product_type['variant_attributes'], $attribute['attribute_key']);
                                        
                                        if($contains || $contains_v) {
                                            
                                            // find the ID for the tl_iso_attribute with this field name
                                            $ia_query =  "SELECT * FROM tl_iso_attribute WHERE field_name='".$attribute['attribute_key']."' ORDER BY id ASC";
                                            $ia_result = $dbh->query($ia_query);
                                            if($ia_result) {
                                                while($iso_attribute = $ia_result->fetch_assoc()) {
                                                    
                                                    echo "Linked: variant<br>";
                                                    
                                                    $update =  "update tl_salsify_attribute set linked_isotope_attribute='".$iso_attribute['id']."' WHERE id='".$attribute['id']."'";
                                                    $result_update = $dbh->query($update);
                                            
                                                    fwrite($myfile, "VARIANT: Linked Salsify Attribute ID: " . $attribute['id'] . " to Isotope Attribute ID: " . $iso_attribute['id'] . "\n");

                                                }
                                            }

                                        }

                                    }
                                }
                                break;
                        }
                    }
                }
            }
        }
    }
    
    fclose($myfile);
