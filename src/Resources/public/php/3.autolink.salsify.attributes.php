<?php

    /** INITS AND INCLUDES - START **/
    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Attribute;
    use Isotope\Model\AttributeOption;
    use Isotope\Model\ProductType;
    use pcrov\JsonReader\JsonReader;

    $debug_mode = true;
    if($debug_mode)
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/step_three_'.date('m_d_y').'.txt', "a+") or die("Unable to open file!");
    
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    $serializedData = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/../db.txt');
	$db_info = unserialize($serializedData);
    $dbh = new mysqli("localhost", $db_info[0], $db_info[1], $db_info[2]);
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }

    /** INITS AND INCLUDES - STOP **/
    
    ////////////////
    // STAGE DATA //
    ////////////////
    
    // Loop through Salsify Requests on Step Three
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_auto_linking']);
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
		    //debug($debug_mode, $log, "[Checking SalsifyRequest] ID: ".$sr->id. " - " . $sr->request_name);
		    
		    // Loop through Salsify Products that belong to this Salsify Request
		    $salsify_products = SalsifyProduct::findBy(['pid = ?'], [$sr->id]);
            if($salsify_products) {

                foreach ($salsify_products as $sp)
        		{
        		    
        		    //debug($debug_mode, $log, "\t[Salsify Product ID: " . $sp->id . "] Attempting Auto-Link on Salsify Attributes");
        		    
        		    // Find all Salsify Attributes for this Salsify Product
        		    $salsify_attributes = SalsifyAttribute::findBy(['pid = ?', 'linked_isotope_attribute IS ?'], [$sp->id, null]);
                    if($salsify_attributes) {
                        foreach($salsify_attributes as $sa) {
                            
                            $save = false;
                            //debug($debug_mode, $log, "\t\t[Salsify Attribute ID: " . $sa->id . "] Seeking link for '" .$sa->attribute_key . "'");

                            $iso_attr = null;
                            if($sp->isotope_product_variant_type == 'single') {
                                // Single product, just look at the field name using the key
                                $iso_attr = Attribute::findBy(['field_name = ?'], [$sa->attribute_key]);
                            }
                            else if($sp->isotope_product_variant_type == 'variant') {
                                
                                // If this is a variant, try finding the _v version first
                                debug($debug_mode, $log, "\t\t\tSeeking Variant version of Isotope Attribute: " . $sa->attribute_key . '_v');
                                $v_att = Attribute::findOneBy(['field_name = ?'], [$sa->attribute_key . '_v']);
                                if($v_att) {
                                    
                                    debug($debug_mode, $log, "\t\t\t\t[Variant] Variant Isotope Attribute ID ".$v_att->id." found, validating it belongs to Product Type (ID: " . $sp->isotope_product_type . ")" );
                                    $linked_attributes = array();
                                    $pt = ProductType::findOneBy(['tl_iso_producttype.id=?'],[$sp->isotope_product_type]);
                                    if($pt != null) {
                                         if($pt->variant_attributes != null) {
                                            foreach($pt->variant_attributes as $key => $attr) {
                                                if($attr['enabled'] == '1') {
                                                    $linked_attributes[$key] = $linked_attributes[$key] + 1;
                                                }
                                            }
                                        } else {
                                            foreach($pt->attributes as $key => $attr) {
                                                if($attr['enabled'] == '1') {
                                                    $linked_attributes[$key] = $linked_attributes[$key] + 1;
                                                }
                                            }
                                        }
                                    }
                                    
                                    debug($debug_mode, $log, "\t\t\t\t\t[VALID CHECK] [KEY: " . $sa->attribute_key. "_v" . "] " . $linked_attributes[$sa->attribute_key. "_v"]);
                                    if($linked_attributes[$sa->attribute_key . "_v"] >= 1) {
                                        debug($debug_mode, $log, "\t\t\t\t\t[Variant] VALID!");
                                        $iso_attr = $v_att;
                                    } else {
                                        $iso_attr = Attribute::findBy(['field_name = ?'], [$sa->attribute_key]);
                                        debug($debug_mode, $log, "\t\t\t[Variant] INVALID, Seeking normal Isotope Attribute (" . count($iso_attr) . ")");
                                    }
                                    
                                    
                                } else {
                                    $iso_attr = Attribute::findBy(['field_name = ?'], [$sa->attribute_key]);
                                    debug($debug_mode, $log, "\t\t\t[Variant] Seeking normal Isotope Attribute (" . count($iso_attr) . ")");
                                }
                                
                            }

                            
                            // Through our multiple attempts above, check if we have ended up with an Isotope Attribute
                            if($iso_attr) {
                                
                                $save = true;
                                $sa->linked_isotope_attribute = $iso_attr->id;
                                $sa->status = 'pass';
                                
                                // Link or Create Option
        	                    if($iso_attr->type == 'select' || $iso_attr->type == 'radio') {
        	                        
        	                        debug($debug_mode, $log, "\t[Isotope Attribute ID: " . $iso_attr->id . "] Isotope Attribute ID required, attempting Update or Creation");
        	                        
        	                        // Loop through comma separated attribute values
        	                        $option_ids = array();
        	                        $attribute_values = explode(", ", $sa->attribute_value);
        	                        foreach($attribute_values as $val) {
        	                            
        	                            // Try and find an existing Attribute Option
        	                            $existing_option = AttributeOption::findOneBy(['tl_iso_attribute_option.pid=?', 'tl_iso_attribute_option.label=?'],[$sa->linked_isotope_attribute, $val]);
        	                            if($existing_option) {
        	                                
        	                                $option_ids[] = $existing_option->id;
        	                                //debug($debug_mode, $log, "\t\t[Isotope Attribute Option ID: " . $existing_option->id . "] Existing Isotope Attribute Option for this Isotope Attribute found");
        	                                
        	                            } else {
        	                                
        	                                $new_option = new AttributeOption();
                        					$new_option->pid = $sa->linked_isotope_attribute;
                        					$new_option->label = $val;
                        					$new_option->tstamp = time();
                        					$new_option->published = 1;
                        					$new_option->ptable = 'tl_iso_attribute';
                        					$new_option->type = 'option';
                        					
                        					// Sorting
                        					$new_option->sorting = generateSortNumber($sa->attribute_value);
                        					debug($debug_mode, $log, "\t\t\t[Sorting Number for: " . $sa->attribute_value . "]: " . $new_option->sorting);

                        					$new_option->save();
                        					
                        					$option_ids[] = $new_option->id;
                        					//debug($debug_mode, $log, "\t[Isotope Attribute Option ID: " . $new_option->id . "] New Isotope Attribute Option created");
        	                            }
    
        	                            
        	                        }
        
                                    $sa->linked_isotope_attribute_option = serialize($option_ids);
        	                    }
                                

                            } else
                                debug($debug_mode, $log, "\t\tNo Isotope Attribute found, awaiting manual linking");
                            











                            if($save)
                                $sa->save();
                        }
                    }
                    
                    
                    debug($debug_mode, $log, "\t- - - - - - - - - - - - - - - - - -");
        		}

        		

            } else {
                // Add a blank line between our Salsify Requests
                debug($debug_mode, $log, "\tNo Salsify Products found");
            }
            
            // Add a blank line between our Salsify Requests
            debug($debug_mode, $log, "- - - - - - - - - - - - - - - - - - - - - -\n");
        
            $sr->status = 'awaiting_cat_linking';
            $sr->save();
		}
    }
    


    // Close our logfile
    if($debug_mode)
        fclose($log);
        
        
    /** HELPER FUNCTIONS **/
    function debug($debug_mode, $log, $message) {
        if($debug_mode)
            fwrite($log, $message . "\n");
        echo $message . "<br>";
    }
    
    function generateSortNumber($value) {
        // 1. Ensure we have a string and handle short inputs
        $str = str_pad((string)$value, 3, "\0");
        
        // 2. Extract the first three characters
        $char1 = ord($str[0]);
        $char2 = ord($str[1]);
        $char3 = ord($str[2]);
    
        /**
         * 3. Calculate the weighted sum.
         * We use 65536 (256^2) for the first position and 
         * 256 for the second to ensure no overlap.
         */
        $sortNumber = ($char1 * 65536) + ($char2 * 256) + $char3;
    
        return $sortNumber;
    }
