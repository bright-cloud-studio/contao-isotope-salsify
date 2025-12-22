<?php

    /** INITS AND INCLUDES - START **/
    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Attribute;
    use Isotope\Model\AttributeOption;
    use pcrov\JsonReader\JsonReader;
    
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
    $step_completed = false;
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
                    }
                }

                // New file found, process it
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
                        debug($debug_mode, $log, "Salsify Products Unpublished: " . count($existing_salsify_products));
                    }
                    
                    // Unpublish all SalsifyProducts that belong to this request
                    debug($debug_mode, $log, "Unpublishing Salsify Attributes linked to this Salsify Request");
                    $existing_salsify_attributes = SalsifyAttribute::findBy('request', $request['id']);
                    if($existing_salsify_attributes) {
                        foreach($existing_salsify_attributes as $existing_salsify_attribute) {
                            $existing_salsify_attribute->published = '';
                            $existing_salsify_attribute->save();
                        }
                        debug($debug_mode, $log, "Salsify Attributes Unpublished: " . count($existing_salsify_attributes));
                    }
                    
                    // Add a blank line to our debug log before moving on to product generation
                    debug($debug_mode, $log, "");

                    /** PROCESS JSON FILE - START **/
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
                            		$update_sp->product_name = encode_non_url_string($array_child[$request['isotope_name_key']][0]);
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
                            		$salsify_product->product_name = encode_non_url_string($array_child[$request['isotope_name_key']][0]);
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
                                        debug($debug_mode, $log, "\tUpdate Salsify Attribute [ID: ".$update_sa->id."] [KEY: " . $key . "]");
                                        
                                        // FIRST CONVERSION HERE
                                        $update_sa->attribute_value = encode_non_url_string($val[0]);
                                        $update_sa->tstamp = time();
                                        $update_sa->published = 1;
                                        
                                        debug($debug_mode, $log, "\t\t[ID: ".$update_sa->id."] [KEY: " . $key . "] [VAL: " . $update_sa->attribute_value . "]");
                                        if($request['isotope_category_key'] == $key) {
                                            debug($debug_mode, $log, "\t\t[ID: ".$update_sa->id."] [KEY: " . $key . "] Applying 'Category' from Salsify Request's Isotope Category Key");
                                            $update_sa->is_cat = 1;
                                        }
                                        if($request['isotope_grouping_key'] == $key) {
                                            debug($debug_mode, $log, "\t\t[ID: ".$update_sa->id."] [KEY: " . $key . "] Applying 'Grouping' from Salsify Request's Isotope Grouping Key");
                                            $update_sa->is_grouping = 1;
                                        }
                                        if($request['isotope_publish_key'] == $key) {
                                            debug($debug_mode, $log, "\t\t[ID: ".$update_sa->id."] [KEY: " . $key . "] Applying 'Publish' from Salsify Request's Isotope Publish Key");
                                            $update_sa->controls_published = 1;
                                        }
                                        
                                        $update_sa->save();
            
                                    } else {
                                        
                                        $salsify_attribute = new SalsifyAttribute();
                                        $salsify_attribute->pid = $salsify_product->id;
                                        $salsify_attribute->request = $request['id'];
                                        $salsify_attribute->attribute_key = $key;
                                        $salsify_attribute->attribute_value = encode_non_url_string($val[0]);
                                        $salsify_attribute->linked_isotope_attribute = null;
                                        $salsify_attribute->tstamp = time();
                                        $salsify_attribute->published = 1;

                                        if($request['isotope_category_key'] == $key) {
                                            $salsify_attribute->is_cat = 1;
                                        }
                                        if($request['isotope_grouping_key'] == $key) {
                                            $salsify_attribute->is_grouping = 1;
                                        }
                                        if($request['isotope_publish_key'] == $key) {
                                            $salsify_attribute->controls_published = 1;
                                        }
                                        
                                        // Save new Salsify Attribute BEFORE debug notes or we wont have an ID
                                        $salsify_attribute->save();

                                        // Debug Messages
                                        debug($debug_mode, $log, "\tCreate Salsify Attribute [ID: ".$salsify_attribute->id."] [KEY: " . $key . "]");
                                        debug($debug_mode, $log, "\t\t[ID: ".$salsify_attribute->id."] [KEY: " . $key . "] [VAL: " . $salsify_attribute->attribute_value . "]");
                                        if($request['isotope_category_key'] == $key) 
                                            debug($debug_mode, $log, "\t\t[ID: ".$salsify_attribute->id."] [KEY: " . $key . "] Applying 'Category' from Salsify Request's Isotope Category Key");
                                        if($request['isotope_grouping_key'] == $key) 
                                            debug($debug_mode, $log, "\t\t[ID: ".$salsify_attribute->id."] [KEY: " . $key . "] Applying 'Grouping' from Salsify Request's Isotope Grouping Key");
                                        if($request['isotope_publish_key'] == $key) 
                                            debug($debug_mode, $log, "\t\t[ID: ".$salsify_attribute->id."] [KEY: " . $key . "] Applying 'Publish' from Salsify Request's Isotope Publish Key");
            
                                    }

                                }  
        
                                // Add a blank line between products in the debug log
                                debug($debug_mode, $log, "-------------------------------------------");
                    		}
                            
                    	}
                    
                    } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
                    $reader->close();
                    /** PROCESS JSON FILE - END **/
                    
                    // Update our Salsify Request now that the step has completed
                    $dbh->prepare("UPDATE tl_salsify_request SET file_url='". $latest_file_url ."', file_date='" . $latest_file_date . "', status='awaiting_grouping' WHERE id='".$request['id']."'")->execute();
                    
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
        
        return $string;
        /*
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
        */
    }
