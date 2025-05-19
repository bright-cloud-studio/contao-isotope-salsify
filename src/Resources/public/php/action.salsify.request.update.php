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
    
    // Stores log messages until the end
    $log_messages = '';
    $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/salsify_request_update_'.strtolower(date('m_d_y')).".txt", "w") or die("Unable to open file!");
    
    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }
    
    fwrite($myfile, "Processing Salsify Requests\n");
    
    // Loop through all Salsify Requests that are in the 'awaiting_new_file' state
    $sr_query =  "SELECT * FROM tl_salsify_request WHERE status='awaiting_new_file' ORDER BY id ASC";
    $sr_result = $dbh->query($sr_query);
    if($sr_result) {
        while($request = $sr_result->fetch_assoc()) {
            
            fwrite($myfile, "Processing SalsifyRequest: ".$request['id']."\n");

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
                }
                // When leaving this loop, we will have whatever the latest file's date and url is
            }
            
            // If our found file's date is newer, update
            if($latest_file_date > (int)$request['file_date']) {
                
                fwrite($myfile, "Newer Salsify data found\n");
                
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
                
                fwrite($myfile, "Generating/Updating Salsify Products and Salsify Attributes\n");
                
                // First, turn off all Salsify Products
                fwrite($myfile, "Unpublishing all Salsify Products and Salsify Attributes\n");
                $dbh->prepare("UPDATE tl_salsify_product SET published=''")->execute();
                $dbh->prepare("UPDATE tl_salsify_attribute SET published=''")->execute();
            
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
                		    fwrite($myfile, "Skipping Salsify Product: " . $required_sku . " | " . $required_name . "\n");
                		} else {
                		    
                		    // Tick up our SalsifyProduct counter
                            $prod_count++;
                            
                            // Try to find an existing SalsifyProduct with these values
                            $salsify_product;
                            $update_sp = SalsifyProduct::findOneBy(['tl_salsify_product.product_sku=?'],[$array_child[$request['isotope_sku_key']][0]]);
                            if($update_sp != null) {
                                
                                // We found an existing SalsifyProduct
                                fwrite($myfile, "Updating Salsify Product: " . $array_child[$request['isotope_sku_key']][0] . "\n");
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
                                fwrite($myfile, "Creating Salsify Product: " . $array_child[$request['isotope_sku_key']][0] . "\n");
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
                                    fwrite($myfile, "Updating Salsify Attribute ID: ".$update_sa->id."\n");
                                    
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
                            						fwrite($myfile, "Option Found: ".$option->id.", adding to option_ids array \n");
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
                        						fwrite($myfile, "New Option Created: ".$new_option->id.", adding to option_ids array \n");
                            				}
        		                            
        		                        }
                                        
                                        $update_sa->linked_isotope_attribute_option = serialize($option_ids);
            					        fwrite($myfile, "Saving Linked Attribute Option serialized array \n");
                                        $update_sa->status = 'pass';
                                        
                                        
                                        //$update_sa->linked_isotope_attribute = null;
                                        //$update_sa->linked_isotope_attribute_option = null;
                                        //$update_sa->status = 'fail';
                                        
                                    }
                                    
                                    $update_sa->tstamp = time();
                                    $update_sa->published = 1;
                                    $update_sa->save();
        
                                } else {
                                    
                                    // CREATE NEW SalsifyAttribute
                                    
                                    $salsify_attribute = new SalsifyAttribute();
                                    $salsify_attribute->pid = $salsify_product->id;
                                    $salsify_attribute->attribute_key = $key;
                                    $salsify_attribute->attribute_value = $val[0];
                                    
                                    // First, start off with out linked attribute being null
                                    $salsify_attribute->linked_isotope_attribute = null;
                                    
                                    
                                    
                                    
                                    
                                    
                                    
                                    // TRY TO APPLY OUR SPECIALIZED SETTINGS FROM OTHER ATTRIBUTES
                                    
                                    // CONTROLS PUBLISHING
                                    $sa_controls_published = SalsifyAttribute::findOneBy(['tl_salsify_attribute.attribute_key=?', 'tl_salsify_attribute.controls_published=?'],[$key, 1]);
                                    if($sa_controls_published) {
                                        $publish_tracker[$salsify_attribute->pid] = $salsify_attribute->attribute_value;
                                        $salsify_attribute->controls_published = 1;
                                    }
                                    
                                    // GROUPING
                                    $sa_grouping = SalsifyAttribute::findOneBy(['tl_salsify_attribute.attribute_key=?', 'tl_salsify_attribute.is_grouping=?'],[$key, 1]);
                                    if($sa_grouping) {
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
                                        
                                    }
                                    
                                    // IS CAT
                                    $sa_is_cat = SalsifyAttribute::findOneBy(['tl_salsify_attribute.attribute_key=?', 'tl_salsify_attribute.is_cat=?'],[$key, 1]);
                                    if($sa_is_cat) {
                                        $salsify_attribute->is_cat = 1;
                                    }






                                    $salsify_attribute->tstamp = time();
                                    $salsify_attribute->published = 1;
                                    $salsify_attribute->save();
                                    
                                    fwrite($myfile, "NEW Salsify Attribute ID: ".$salsify_attribute->id."\n");
        
                                }
                                
                                $attributes[$salsify_attribute->id]['key'] = $key;
                                $attributes[$salsify_attribute->id]['value'] = $val[0];
                                $log[$salsify_product->id]['attributes'] = $attributes;
                            }  
                            
                            
                            
                            
                            
                            
                            
                            
    
                		}
                        
                	}
                
                } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
                
                $reader->close();
            
            } else {
                fwrite($myfile, "No new file found, skipping generation/update \n");
            }
            
            
            fwrite($myfile, print_r($group_counter, true));
            
            // GROUPING
            if($group_counter != null) {
                fwrite($myfile, "Grouping SalsifyProducts \n\n");
                $salsify_products = SalsifyProduct::findAll();
                foreach($salsify_products as $prod) {
                    
                    $change_detected = false;

                    if($group_counter[$prod->variant_group] == 1) {
                        
                        if($prod->isotope_product_variant_type == 'variant')
                            $change_detected = true;
                        
                        $prod->isotope_product_variant_type = 'single';
                        $prod->isotope_product_type = $isotope_product_type;
                        fwrite($myfile, "SalsifyProduct ID: " . $prod->id . " set as 'single' using Isotope Product Type ID: " . $isotope_product_type . "\n\n");
                    } else {
                        
                        if($prod->isotope_product_variant_type == 'single')
                            $change_detected = true;
                        
                        $prod->isotope_product_variant_type = 'variant';
                        $prod->isotope_product_type = $isotope_product_type_variant;
                        fwrite($myfile, "SalsifyProduct ID: " . $prod->id . " set as 'variant' using Isotope Product Type ID: " . $isotope_product_type_variant . "\n\n");
                    }
                    $prod->isotope_product_type_linked = 'linked';
                    $prod->save();
                    
                    // If type change detected, unlink all attributes
                    if($change_detected) {
                        fwrite($myfile, "SalsifyProduct ID:" .$prod->id ." Single/Variant change detected, unlinking all SalsifyAttributes \n");
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
                        
                        fwrite($myfile, "SalsifyProduct Un-Published ID: " . $prod_to_unpublish->id . "\n");
                        
                    }
                }
            }
            
            
        }
    }
    
    // Close our logfile
    fclose($myfile);
