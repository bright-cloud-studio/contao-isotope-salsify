<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    
    use Isotope\Model\Attribute;
    use Isotope\Model\AttributeOption;
    use pcrov\JsonReader\JsonReader;
    
    // Tracks who to turn off at the end for Product Release to site
    $publish_tracker = array();
    // Tracks product grouping
    $group_counter = array();
    $isotope_product_type = '';
    $isotope_product_type_variant = '';
    
    $debug_mode = true;
    if($debug_mode)
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/'.date('m_d_y').'_request_update.txt', "a+") or die("Unable to open file!");
    
    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecom_user", 'I6aX,Ud-EYa^]P9u8g', "ecom_contao_4_13");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }
    

    
    // Loop through all Salsify Requests that are in the 'awaiting_new_file' state
    $sr_query =  "SELECT * FROM tl_salsify_request WHERE status='awaiting_new_file' ORDER BY id ASC";
    $sr_result = $dbh->query($sr_query);
    if($sr_result) {
        while($request = $sr_result->fetch_assoc()) {
            
            if($debug_mode)
                fwrite($log, "Processing SalsifyRequest: ".$request['id']."\n");
            
            echo "SalsifyRequest ID: " . $request['id'] . "<br>";

            ///////////////////////////////////////////////////
            // STEP ONE - Determine which file is the latest //
            ///////////////////////////////////////////////////
            
            // Tracks if we have found a newer file and need to run it
            $run_update = false;
            
            // Build complete folder address
            $folder = $_SERVER['DOCUMENT_ROOT'] . "/../files/" . $request['source_folder'];
            
            // Filter for only things that contain a period in the name
            $files = array_filter(scandir($folder), function($file) {
                return $file[0] !== '.';
            });
            
            // Stores the values of the latest file as we loop through them
            $latest_file_url = '';
            $latest_file_date = '';
            
            // Loop through our found files
            foreach($files as $file) {
                $file_date = filemtime($folder . "/" . $file);
                
                // If newer, save file date and URL
                if($file_date > $latest_file_date) {
                    $latest_file_url = $file;
                    $latest_file_date = $file_date;
                    $latest_file_date -= $latest_file_date % 60;
                }
                // When leaving this loop, we will have whatever the latest file's date and url is
            }
            
            echo "Latest File Date: " . $latest_file_date . "<br>";
            echo "Converted: " . date('m/d/Y H:i:s', $latest_file_date) . "<br>";
            echo "File Date: " . $request['file_date'] . "<br>";
            echo "Converted: " . date('m/d/Y H:i:s', (int)$request['file_date']) . "<br>";
            
            
            // If our found file's date is newer, update
            if($latest_file_date > (int)$request['file_date']) {
                
                echo "Latest Found, updating!<br>";
                
                if($debug_mode)
                    fwrite($log, "Newer Salsify data found\n");
                
                $run_update = true;
                $request['file_url'] = $latest_file_url;
                
                if($request['initial_linking_completed'] == '')
                    $dbh->prepare("UPDATE tl_salsify_request SET file_url='". $latest_file_url ."', file_date='" . $latest_file_date . "', status='awaiting_initial_linking' WHERE id='".$request['id']."'")->execute();
                else
                    $dbh->prepare("UPDATE tl_salsify_request SET file_url='". $latest_file_url ."', file_date='" . $latest_file_date . "', status='awaiting_auto_linking' WHERE id='".$request['id']."'")->execute();
            }



            /////////////////////////////////////////////////////////////////////////
            // STEP TWO - Create or update Salsify Products and Salsify Attributes //
            /////////////////////////////////////////////////////////////////////////
            
            // If we found a file that is newer than our saved one, we need to process this file
            if($run_update) {
                
                if($debug_mode)
                    fwrite($log, "Generating/Updating Salsify Products and Salsify Attributes\n");
                
                // First, turn off all Salsify Products
                if($debug_mode)
                    fwrite($log, "Unpublishing SalsifyProducts and SalsifyAttributes that belong to this SalsifyRequest\n");
                
                
                /* UPDATED TO BE MORE SPECIFIC TO SALSIFYREQUESTS */
                
                // Unpublish all SalsifyProducts that belong to this request
                $existing_salsify_products = SalsifyProduct::findBy('pid', $request['id']);
                if($existing_salsify_products) {
                    foreach($existing_salsify_products as $existing_salsify_product) {
                        if($debug_mode)
                            fwrite($log, "Unpublishing SalsifyProduct: ". $existing_salsify_product->id ."\n");
                        $existing_salsify_product->published = '';
                        $existing_salsify_product->save();
                    }
                }
                
                
                // Unpublish all SalsifyProducts that belong to this request
                $existing_salsify_attributes = SalsifyAttribute::findBy('request', $request['id']);
                if($existing_salsify_attributes) {
                    foreach($existing_salsify_attributes as $existing_salsify_attribute) {
                        if($debug_mode)
                            fwrite($log, "Unpublishing SalsifyAttribute: ". $existing_salsify_attribute->id ."\n");
                        $existing_salsify_attribute->published = '';
                        $existing_salsify_attribute->save();
                    }
                }
                
                

                // Open and process file
                $reader = new JsonReader();
                $reader->open("../files/" . $request['source_folder'] . "/" . $request['file_url']);
                $depth = $reader->depth();
                $reader->read();
                
                $do_loop = 0;
                // Process loaded XML data
                do
                {
                	$do_loop++;
                
                	// Load the first array, which is the overall wrapper of arrays
                	$array_parent = $reader->value();
                
                	$prod_count = 0;
                	// Loop through children arrays, these are what store the actual values here
                	foreach($array_parent as $array_child) {
                		
                		// Get the data for the two Isotope required fields for an Isotope Product, only continue if we have them
                		$required_sku = $array_child[$request['isotope_sku_key']][0];
                		$required_name = $array_child[$request['isotope_name_key']][0];
                		if($required_sku == '' || $required_name == '') {
                		    // Skip generating this SalsifyProduct as we don't have our reqiuired 
                		    if($debug_mode)
                		        fwrite($log, "Skipping Salsify Product: " . $required_sku . " | " . $required_name . "\n");
                		} else {
                		    
                		    // Tick up our SalsifyProduct counter
                            $prod_count++;
                            
                            // Try to find an existing SalsifyProduct with these values
                            $salsify_product;
                            $update_sp = SalsifyProduct::findOneBy(['tl_salsify_product.product_sku=?'],[$array_child[$request['isotope_sku_key']][0]]);
                            if($update_sp != null) {
                                
                                // We found an existing SalsifyProduct
                                if($debug_mode)
                                    fwrite($log, "Updating Salsify Product: " . $array_child[$request['isotope_sku_key']][0] . "\n");
                                echo "SalsifyProduct Found and Updated!<br>";
                                
                                $update_sp->pid = $request['id'];
                        		$update_sp->tstamp = time();
                        		$update_sp->product_sku = $array_child[$request['isotope_sku_key']][0];
                        		$update_sp->product_name = $array_child[$request['isotope_name_key']][0];
                        		$update_sp->published = 1;
                        		$update_sp->save();
                        		$salsify_product = $update_sp;
                                
                            } else {
                                
                                // We need to make a new SalsifyProduct
                                if($debug_mode)
                                    fwrite($log, "Creating Salsify Product: " . $array_child[$request['isotope_sku_key']][0] . "\n");
                                echo "SalsifyProduct Created!<br>";
                                
                        		$salsify_product = new SalsifyProduct();
                        		$salsify_product->pid = $request['id'];
                        		$salsify_product->tstamp = time();
                        		$salsify_product->product_sku = $array_child[$request['isotope_sku_key']][0];
                        		$salsify_product->product_name = $array_child[$request['isotope_name_key']][0];
                        		$salsify_product->published = 1;
                        		$salsify_product->save();
                            }
                    		
                            
                            // Process SalsifyAttributes for this SalsifyProduct
                            $attributes = array();
                            $prod_values = array();
                            foreach($array_child as $key => $val) {
                                
                                $prod_values[$key] = $val[0];
                                
                                // Try and find a SalsifyAttribute
                                $salsify_attribute;
                                $update_sa = SalsifyAttribute::findOneBy(['tl_salsify_attribute.pid=?', 'tl_salsify_attribute.attribute_key=?'],[$salsify_product->id, $key]);
                                if($update_sa != null) {
                                    
                                    
                                    ///////////////////////////////////////
                                    // UPDATE EXISTING SALSIFY ATTRIBUTE //
                                    ///////////////////////////////////////
                                    
                                    // Existing SalsifyAttribute found
                                    if($debug_mode)
                                        fwrite($log, "Updating Salsify Attribute ID: ".$update_sa->id."\n");
                                    
                                    // Update the attribute_value to this latest version
                                    $update_sa->attribute_value = $val[0];
                                    
                                    
                                    // If our found SalsifyAttribute
                                    if($update_sa->controls_published) {
                                        $publish_tracker[$update_sa->pid] = $update_sa->attribute_value;
                                    }
                                    
                                    // If our found SalsifyAttribute is grouping
                                    if($update_sa->is_grouping) {
                                        
                                        if($isotope_product_type == '') {
                                            $isotope_product_type = $update_sa->isotope_product_type;
                                            $isotope_product_type_variant = $update_sa->isotope_product_type_variant;
                                        }
                                        
                                        $group_counter[$update_sa->attribute_value] = $group_counter[$update_sa->attribute_value] + 1;
                                        $salsify_product->variant_group = $update_sa->attribute_value;
                                        $salsify_product->save();
                                    }
                                    
                                    // if we have an isotope attribute linked
                                    if($update_sa->linked_isotope_attribute_option != null) {
                                        
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
                            						if($debug_mode)
                            						    fwrite($log, "Option Found: ".$option->id.", adding to option_ids array \n");
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
                            					if($debug_mode)
                        						    fwrite($log, "New Option Created: ".$new_option->id.", adding to option_ids array \n");
                            				}
        		                            
        		                        }
                                        
                                        $update_sa->linked_isotope_attribute_option = serialize($option_ids);
                                        if($debug_mode)
            					            fwrite($log, "Saving Linked Attribute Option serialized array \n");
                                        $update_sa->status = 'pass';
                                    }
                                    
                                    $update_sa->tstamp = time();
                                    $update_sa->published = 1;
                                    $update_sa->save();
        
                                } else {
                                    
                                    // CREATE NEW SalsifyAttribute
                                    
                                    $salsify_attribute = new SalsifyAttribute();
                                    $salsify_attribute->pid = $salsify_product->id;
                                    $salsify_attribute->request = $request['id'];
                                    $salsify_attribute->attribute_key = $key;
                                    $salsify_attribute->attribute_value = $val[0];
                                    
                                    // First, start off with out linked attribute being null
                                    $salsify_attribute->linked_isotope_attribute = null;
                                    
                                    // CONTROLS PUBLISHING
                                    $sa_controls_published = SalsifyAttribute::findOneBy(['tl_salsify_attribute.attribute_key=?', 'tl_salsify_attribute.controls_published=?'],[$key, 1]);
                                    if($sa_controls_published) {
                                        $publish_tracker[$salsify_attribute->pid] = $salsify_attribute->attribute_value;
                                        $salsify_attribute->controls_published = 1;
                                    }
                                    
                                    
                                    
                                    
                                    
                                    // GROUPING
                                    
                                    // get ALL SalsifyAttributes where the key matches and is checked as a grouping attribute
                                    
                                    if($debug_mode)
                                        fwrite($log, "Checking grouping \n");
                                    
                                    $sa_groupings = SalsifyAttribute::findBy(['tl_salsify_attribute.attribute_key=?', 'tl_salsify_attribute.is_grouping=?'],[$key, 1]);
                                    if($sa_groupings) {
                                        
                                        if($debug_mode)
                                            fwrite($log, "Groupings Found \n");
                                        
                                        foreach($sa_groupings as $as_grouping) {
                                            
                                            if($salsify_attribute->request == $request['id']) {
                                                
                                                if($debug_mode)
                                                    fwrite($log, "Grouping SalsifyAttribute belings to SalsifyRequest \n");
                                                
                                                
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
                                                
                                                if($debug_mode)
                                                    fwrite($log, "Leaving Grouping loop! \n");
                                                
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
                                    
                                    if($debug_mode)
                                        fwrite($log, "NEW Salsify Attribute ID: ".$salsify_attribute->id."\n");
        
                                }
                                
                                $attributes[$salsify_attribute->id]['key'] = $key;
                                $attributes[$salsify_attribute->id]['value'] = $val[0];
                                //$log[$salsify_product->id]['attributes'] = $attributes;
                            }  
    
                		}
                        
                	}
                
                } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
                
                $reader->close();
            
            
            
                if($debug_mode)
                    fwrite($log, print_r($group_counter, true));
                
                // GROUPING
                if($group_counter != null) {
                    
                    if($debug_mode)
                        fwrite($log, "Grouping SalsifyProducts \n\n");
                    
                    $salsify_products = SalsifyProduct::findAll();
                    foreach($salsify_products as $prod) {
                        
                        $change_detected = false;
    
                        if($group_counter[$prod->variant_group] == 1) {
                            
                            if($prod->isotope_product_variant_type == 'variant')
                                $change_detected = true;
                            
                            $prod->isotope_product_variant_type = 'single';
                            $prod->isotope_product_type = $isotope_product_type;
                            if($debug_mode)
                                fwrite($log, "SalsifyProduct ID: " . $prod->id . " set as 'single' using Isotope Product Type ID: " . $isotope_product_type . "\n\n");
                        } else {
                            
                            if($prod->isotope_product_variant_type == 'single')
                                $change_detected = true;
                            
                            $prod->isotope_product_variant_type = 'variant';
                            $prod->isotope_product_type = $isotope_product_type_variant;
                            if($debug_mode)
                                fwrite($log, "SalsifyProduct ID: " . $prod->id . " set as 'variant' using Isotope Product Type ID: " . $isotope_product_type_variant . "\n\n");
                        }
                        $prod->isotope_product_type_linked = 'linked';
                        $prod->save();
                        
                        // If type change detected, unlink all attributes
                        if($change_detected) {
                            if($debug_mode)
                                fwrite($log, "SalsifyProduct ID:" .$prod->id ." Single/Variant change detected, unlinking all SalsifyAttributes \n");
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
                foreach($publish_tracker as $key => $val) {
                    if($val == 'false' || $val == '') {
                        $prod_to_unpublish = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$key]);
                        if($prod_to_unpublish != null) {
                            $prod_to_unpublish->published = '';
                            $prod_to_unpublish->save();
                            
                            if($debug_mode)
                                fwrite($log, "SalsifyProduct Un-Published ID: " . $prod_to_unpublish->id . "\n");
                            
                        }
                    }
                }
            
            } else {
                if($debug_mode)
                    fwrite($log, "No new file found, skipping generation/update \n");
            }
            
            
        }
    }
    
    if($debug_mode)
        fwrite($log, "Process completed successfully! \n");
    
    // Close our logfile
    if($debug_mode)
        fclose($log);
    
    
    function toNumber($dest)
    {
        if ($dest)
            return ord(strtolower($dest)) - 96;
        else
            return 0;
    }
