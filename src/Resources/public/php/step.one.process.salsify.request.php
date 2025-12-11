<?php

    /** INITS AND INCLUDES - START **/
    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Attribute;
    use Isotope\Model\AttributeOption;
    use pcrov\JsonReader\JsonReader;
    
    $publish_tracker = array();
    $group_counter = array();
    $isotope_product_type = '';
    $isotope_product_type_variant = '';
    
    $debug_mode = true;
    if($debug_mode)
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/step_one_'.date('m_d_y').'.txt', "a+") or die("Unable to open file!");
    
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
	$serializedData = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/../db.txt');
	$db_info = unserialize($serializedData);
    $dbh = new mysqli("localhost", $db_info[0], $db_info[1], $db_info[2]);
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }
    /** INITS AND INCLUDES - STOP **/
    

    // Loop through all Salsify Requests that are in the 'awaiting_new_file' state
    $sr_query =  "SELECT * FROM tl_salsify_request WHERE status='awaiting_new_file' ORDER BY id ASC";
    $sr_result = $dbh->query($sr_query);
    if($sr_result) {
        while($request = $sr_result->fetch_assoc()) {
            
            debug($debug_mode, $log, "[Checking SalsifyRequest] ID: ".$request['id']. " - " . $request['request_name']);

            // Tracks if we have found a newer file and need to run it
            $run_update = false;
            
            // Build complete folder address
            $folder = $_SERVER['DOCUMENT_ROOT'] . "/../files/" . $request['source_folder'];
            
            // Filter for only things that contain a period in the name
            $files = array_filter(scandir($folder), function($file) {
                return $file[0] !== '.';
            });
            
            // If we have Files
            if($files) {

                // Stores the values of the latest file as we loop through them
                $latest_file_url = '';
                $latest_file_date = '';
                
                debug($debug_mode, $log, "Looping through Files found in Folder");
                
                // Loop through our found files
                foreach($files as $file) {
                    $file_date = filemtime($folder . "/" . $file);
                    
                    debug($debug_mode, $log, "File Date: " . $file_date);
                    
                    // If newer, save file date and URL
                    if($file_date > $latest_file_date) {
                        $latest_file_url = $file;
                        $latest_file_date = $file_date;
                        $latest_file_date -= $latest_file_date % 60;
                    }
                }
                
                if($latest_file_date) {
                    
                    // If our found file's date is newer, update
                    if($latest_file_date > (int)$request['file_date']) {
                        
                        debug($debug_mode, $log, "Newer Salsify file found");
                        
                        $run_update = true;
                        $request['file_url'] = $latest_file_url;
                        
                        if($request['initial_linking_completed'] == '')
                            $dbh->prepare("UPDATE tl_salsify_request SET file_url='". $latest_file_url ."', file_date='" . $latest_file_date . "', status='awaiting_initial_linking' WHERE id='".$request['id']."'")->execute();
                        else
                            $dbh->prepare("UPDATE tl_salsify_request SET file_url='". $latest_file_url ."', file_date='" . $latest_file_date . "', status='awaiting_auto_linking' WHERE id='".$request['id']."'")->execute();
                    }
                }

                /** PROCESS NEW FILE **/
                if($run_update) {
                    
                    debug($debug_mode, $log, "Processing new Salsify File");
                    
                    // Unpublish all SalsifyProducts that belong to this request
                    debug($debug_mode, $log, "Unpublishing Salsify Products linked to this Salsify Request");
                    $existing_salsify_products = SalsifyProduct::findBy('pid', $request['id']);
                    if($existing_salsify_products) {
                        foreach($existing_salsify_products as $existing_salsify_product) {
                            $existing_salsify_product->published = '';
                            $existing_salsify_product->save();
                        }
                    }
                    
                    // Unpublish all SalsifyProducts that belong to this request
                    debug($debug_mode, $log, "Unpublishing Salsify Attributes linked to this Salsify Request");
                    $existing_salsify_attributes = SalsifyAttribute::findBy('request', $request['id']);
                    if($existing_salsify_attributes) {
                        foreach($existing_salsify_attributes as $existing_salsify_attribute) {
                            $existing_salsify_attribute->published = '';
                            $existing_salsify_attribute->save();
                        }
                    }
                    
                    // Add a blank line to our debug log before moving on to product generation
                    debug($debug_mode, $log, "");

                    // Open and process Salsify File
                    $reader = new JsonReader();
                    $reader->open("../files/" . $request['source_folder'] . "/" . $request['file_url']);
                    $depth = $reader->depth();
                    $reader->read();
                    $do_loop = 0;
                    do
                    {
                    	$do_loop++;
                    
                    	// Load the first array, which is the overall wrapper of arrays
                    	$array_parent = $reader->value();
                    
                        // Loop through children arrays, these are the Salsify Products
                    	$prod_count = 0;
                    	foreach($array_parent as $array_child) {
                    		$prod_count++;
                    		// Get the data for the two Isotope required fields for an Isotope Product, only continue if we have them
                    		$required_sku = $array_child[$request['isotope_sku_key']][0];
                    		$required_name = $array_child[$request['isotope_name_key']][0];
                    		if($required_sku == '' || $required_name == '') {
                    		    debug($debug_mode, $log, "Required fields in Salsify Request are empty, not processing Salsify File");
                    		} else {
                                
                                
                                $salsify_product;
                                $update_sp = SalsifyProduct::findOneBy(['tl_salsify_product.product_sku=?'],[$array_child[$request['isotope_sku_key']][0]]);
                                if($update_sp != null) {
                                    // Existing Salsify Product Found
                                    debug($debug_mode, $log, "Update Salsify Product [SKU: " . $array_child[$request['isotope_sku_key']][0] . "]");

                                    $update_sp->pid = $request['id'];
                            		$update_sp->tstamp = time();
                            		$update_sp->product_sku = $array_child[$request['isotope_sku_key']][0];
                            		$update_sp->product_name = $array_child[$request['isotope_name_key']][0];
                            		$update_sp->published = 1;
                            		$update_sp->save();
                            		$salsify_product = $update_sp;
                                    
                                } else {
                                    // New Salsify Product
                                    debug($debug_mode, $log, "Create Salsify Product [SKU: " . $array_child[$request['isotope_sku_key']][0] . "]");
      
                            		$salsify_product = new SalsifyProduct();
                            		$salsify_product->pid = $request['id'];
                            		$salsify_product->tstamp = time();
                            		$salsify_product->product_sku = $array_child[$request['isotope_sku_key']][0];
                            		$salsify_product->product_name = $array_child[$request['isotope_name_key']][0];
                            		$salsify_product->published = 1;
                            		$salsify_product->save();
                                }
                        		
                                
                                // Loop through children arrays, these are the Salsify Attributes
                                //$attributes = array();
                                foreach($array_child as $key => $val) {
                                    
                                    $salsify_attribute;
                                    $update_sa = SalsifyAttribute::findOneBy(['tl_salsify_attribute.pid=?', 'tl_salsify_attribute.attribute_key=?', 'tl_salsify_attribute.request=?'],[$salsify_product->id, $key, $request['id']]);
                                    if($update_sa != null) {
                                        // Existing SalsifyAttribute found
                                        debug($debug_mode, $log, "Update Salsify Attribute [ID: ".$update_sa->id."] [KEY: " . $key . "]");
                                        
                                        // SECOND CONVERSION HERE
                                        $update_sa->attribute_value = encode_non_url_string($val[0]);
                                        
                                        debug($debug_mode, $log, "     [ID: ".$update_sa->id."] [KEY: " . $key . "] [VAL: " . $update_sa->attribute_value . "]");
                                        
                                        // Add to Publish Tracker so it gets turned on at the end
                                        if($update_sa->controls_published) {
                                            $publish_tracker[$update_sa->pid] = $update_sa->attribute_value;
                                        }
                                        
                                        // If this is a grouping Salsify Attribute
                                        if($update_sa->is_grouping) {
                                            if($isotope_product_type == '') {
                                                $isotope_product_type = $update_sa->isotope_product_type;
                                                $isotope_product_type_variant = $update_sa->isotope_product_type_variant;
                                            }
                                            $group_counter[$update_sa->attribute_value] = $group_counter[$update_sa->attribute_value] + 1;
                                            $salsify_product->variant_group = $update_sa->attribute_value;
                                            $salsify_product->save();
                                        }
                                        
                                        // if we have an isotope attribute option linked
                                        if($update_sa->linked_isotope_attribute_option != null) {
                                            
                                            //debug($debug_mode, $log, "[ID: ".$update_sa->id."] [KEY: " . $key . "] Isotope Attribute Option linked");
                                            
                                            ///////////////////////////////
                                            // UPDATE - ATTRIBUTE OPTION //
                                            ///////////////////////////////
                                            
                                            // Store our option IDs until the end
    		                                $option_ids = array();
    		                                $attribute_values = explode(", ", $update_sa->attribute_value);
                                            // Loop through each CSV value
                                            foreach($attribute_values as $val) {
            		                            
            		                            // Find all Options for this Attribute
                                				$existing_options = AttributeOption::findByPid($update_sa->linked_isotope_attribute);
                                				$opt_found = false;
                                				foreach($existing_options as $option) {
                                					// If an Option's label matches our Attribute Value, it already exists
                                					if($option->label == $val) {
                                						$opt_found = true;
                                						//$attribute->linked_isotope_attribute_option = $option->id;
                                						$option_ids[] = $option->id;
                                						
                                						debug($debug_mode, $log, "     [ID: ".$update_sa->id."] [KEY: " . $key . "] Existing Isotope Attribute Option");
                                					}
                                				}
                                				// If no Attribute Option is found, create it
                                				if($opt_found != true) {
                                					$new_option = new AttributeOption();
                                					$new_option->pid = $update_sa->linked_isotope_attribute;
                                					$new_option->label = $val;
                                					$new_option->tstamp = time();
                                					$new_option->published = 1;
                                					$new_option->ptable = 'tl_iso_attribute';
                                					$new_option->type = 'option';
                                					
                                					// Sorting
                                					if($update_sa->attribute_option_sorting == 'sort_numerical') {
                                						// Strip everything but numbers from label, use that as sorting number
                                						$only_number = preg_replace("/[^0-9]/","", $val);
                                						$new_option->sorting = $only_number;
                                						
                                					} else if($update_sa->attribute_option_sorting == 'sort_alphabetical') {
                                						// Get just the first letter of the label, convert to number in alphabet, use as sorting number
                                						$alphabet = range('A', 'Z');
                                						$only_letter = substr($val, 0);
                                						
                                						$new_option->sorting = $alphabet[$only_letter];
                                					}
                
                                					$new_option->save();
                                					
                                					$option_ids[] = $new_option->id;
                                					debug($debug_mode, $log, "     [ID: ".$update_sa->id."] [KEY: " . $key . "] New Isotope Attribute Option created with ID: " . $new_option->id);
                                				}
            		                            
            		                        }
                                            
                                            $update_sa->linked_isotope_attribute_option = serialize($option_ids);
                                            $update_sa->status = 'pass';
                                        }
                                        
                                        $update_sa->tstamp = time();
                                        $update_sa->published = 1;
                                        $update_sa->save();
            
                                    } else {
                                        
                                        $salsify_attribute = new SalsifyAttribute();
                                        $salsify_attribute->pid = $salsify_product->id;
                                        $salsify_attribute->request = $request['id'];
                                        $salsify_attribute->attribute_key = $key;
                                        $salsify_attribute->attribute_value = encode_non_url_string($val[0]);
                                        
                                        // First, start off with our linked attribute being null
                                        $salsify_attribute->linked_isotope_attribute = null;
                                        
                                        // CONTROLS PUBLISHING
                                        $sa_controls_published = SalsifyAttribute::findOneBy(['tl_salsify_attribute.attribute_key=?', 'tl_salsify_attribute.controls_published=?'],[$key, 1]);
                                        if($sa_controls_published) {
                                            $publish_tracker[$salsify_attribute->pid] = $salsify_attribute->attribute_value;
                                            $salsify_attribute->controls_published = 1;
                                        }
                                        

                                        // GROUPING
                                        
                                        // get ALL SalsifyAttributes where the key matches and is checked as a grouping attribute
                                        $sa_groupings = SalsifyAttribute::findBy(['tl_salsify_attribute.attribute_key=?', 'tl_salsify_attribute.is_grouping=?'],[$key, 1]);
                                        if($sa_groupings) {
                                            
                                            foreach($sa_groupings as $as_grouping) {
                                                
                                                if($salsify_attribute->request == $request['id']) {
                                                    
                                                    if($isotope_product_type == '') {
                                                        $isotope_product_type = $sa_grouping->isotope_product_type;
                                                        $isotope_product_type_variant = $sa_grouping->isotope_product_type_variant;
                                                    }
                                                    
                                                    $group_counter[$salsify_attribute->attribute_value] = $group_counter[$salsify_attribute->attribute_value] + 1;
                                                    $salsify_attribute->is_grouping = true;
                                                    $salsify_attribute->isotope_product_type = $sa_grouping->isotope_product_type;
                                                    $salsify_attribute->isotope_product_type_variant = $sa_grouping->isotope_product_type_variant;
                                                    
                                                    $salsify_product->variant_group = $salsify_attribute->attribute_value;
                                                    $salsify_product->save();
                                                    
                                                    break;
                                                }
                                                
                                            }
     
                                        }
                                        
                                        // IS CAT
                                        $sa_is_cat = SalsifyAttribute::findOneBy(['tl_salsify_attribute.attribute_key=?', 'tl_salsify_attribute.is_cat=?'],[$key, 1]);
                                        if($sa_is_cat) {
                                            $salsify_attribute->is_cat = 1;
                                        }
    
                                        $salsify_attribute->tstamp = time();
                                        $salsify_attribute->published = 1;
                                        $salsify_attribute->save();
                                        
                                        // New Salsify Attribute
                                        debug($debug_mode, $log, "Create Salsify Attribute [ID: ".$salsify_attribute->id."] [KEY: " . $key . "]");
            
                                    }
                                    
                                    //$attributes[$salsify_attribute->id]['key'] = $key;
                                    //$attributes[$salsify_attribute->id]['value'] = encode_non_url_string($val[0]);
                                }  
        
                                // Add a blank line between products in the debug log
                                debug($debug_mode, $log, "-------------------------------------------");
        
                    		}
                            
                    	}
                    
                    } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
                    
                    $reader->close();

                    // GROUPING
                    if($group_counter != null) {
                        
                        debug($debug_mode, $log, "Grouping Salsify Products");
                        
                        $salsify_products = SalsifyProduct::findBy('pid', $request['id']);
                        foreach($salsify_products as $prod) {
                            
                            $change_detected = false;
        
                            if($group_counter[$prod->variant_group] == 1) {
                                
                                if($prod->isotope_product_variant_type == 'variant')
                                    $change_detected = true;
                                
                                $prod->isotope_product_variant_type = 'single';
                                $prod->isotope_product_type = $isotope_product_type;
                                
                                debug($debug_mode, $log, "SalsifyProduct ID: " . $prod->id . " set as 'single' using Isotope Product Type ID: " . $isotope_product_type);

                            } else {
                                
                                if($prod->isotope_product_variant_type == 'single')
                                    $change_detected = true;
                                
                                $prod->isotope_product_variant_type = 'variant';
                                $prod->isotope_product_type = $isotope_product_type_variant;
                                
                                debug($debug_mode, $log, "SalsifyProduct ID: " . $prod->id . " set as 'variant' using Isotope Product Type ID: " . $isotope_product_type_variant);

                            }
                            $prod->isotope_product_type_linked = 'linked';
                            $prod->save();
                            
                            // If type change detected, unlink all attributes
                            if($change_detected) {
                                
                                debug($debug_mode, $log, "SalsifyProduct ID:" .$prod->id ." Single/Variant change detected, unlinking all SalsifyAttributes");

                		        $child_attributes = SalsifyAttribute::findBy('pid', $prod->id);
                        		if($child_attributes)
                        		{
                        			foreach ($child_attributes as $child_attribute)
                        		    {
                        		        $child_attribute->linked_isotope_attribute = null;
                        		        $child_attribute->linked_isotope_attribute_option = null;
                        		        $child_attribute->status = "fail";
                        		        $child_attribute->save();
                        		    }
                        		}
                            }
                            
                            
                        }
                    }
                    
                    // At the end of the Salsify Request, we want to turn off things with $publish_tracker
                    // Update SalsifyProducts, unpublish when necessary
                    
                    if($publish_tracker) {
                        debug($debug_mode, $log, "Unpublishing Salsify Products from the Publish Tracker");
                        foreach($publish_tracker as $key => $val) {
                            if($val == 'false' || $val == '') {
                                $prod_to_unpublish = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$key]);
                                if($prod_to_unpublish != null) {
                                    $prod_to_unpublish->published = '';
                                    $prod_to_unpublish->save();
                                    
                                    debug($debug_mode, $log, "Salsify Product Unpublished [ID: " . $prod_to_unpublish->id . "]");
 
                                }
                            }
                        }
                    }
                
                }
                
            } else {
                debug($debug_mode, $log, "No Files found in the Folder");
            }
            
            
            debug($debug_mode, $log, "Salsify Products updated: " . $prod_count);
            

            // Add a blank line between our Salsify Requests
            debug($debug_mode, $log, "- - - - - - - - - - - - - - - - - - - - - -\n");
        }
    }
    
    debug($debug_mode, $log, "Step One Completed");
    
    if($debug_mode)
        fclose($log);
    
    
    
    
    
    
    
    /** HELPER FUNCTIONS **/
    function debug($debug_mode, $log, $message) {
        if($debug_mode)
            fwrite($log, $message . "\n");
        echo $message . "<br>";
    }
    
    
    function toNumber($dest)
    {
        if ($dest)
            return ord(strtolower($dest)) - 96;
        else
            return 0;
    }
    
    function encode_non_url_string($string)
    {
        // Use FILTER_VALIDATE_URL to check if the string is a valid URL.
        if (filter_var($string, FILTER_VALIDATE_URL)) {
            // If it's a URL, return the original string without encoding.
            return $string;
        } else {
            // If it's not a URL, encode it.
            // htmlentities converts special characters to HTML entities,
            // making the string safe to display in a web page.
            return htmlentities($string, ENT_QUOTES, 'UTF-8');
        }
    }
