<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Attribute;
    use pcrov\JsonReader\JsonReader;
    
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
                $dbh->prepare("UPDATE tl_salsify_request SET file_url='". $latest_file_url ."', file_date='" . $latest_file_date . "', status='awaiting_cat_linking' WHERE id='".$request['id']."'")->execute();
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
                
                //die();
            
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
                		
                		$required_sku = $array_child[$request['isotope_sku_key']][0];
                		$required_name = $array_child[$request['isotope_name_key']][0];
                		
                		if($required_sku == '' || $required_name == '') {
                		    fwrite($myfile, "Skipping Salsify Product: " . $required_sku . " | " . $required_name . "\n");
                		} else {
                            $prod_count++;
                            
                            
        
                            // Find and update, else create
                            $salsify_product;
                            $update_sp = SalsifyProduct::findOneBy(['tl_salsify_product.product_sku=?'],[$array_child[$request['isotope_sku_key']][0]]);
                            if($update_sp != null) {
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
                    		
        
                            $attributes = array();
                            $prod_values = array();
                            foreach($array_child as $key => $val) {
                                $prod_values[$key] = $val[0];
                                
                                // Find and update, else create
                                $salsify_attribute;
                                $update_sa = SalsifyAttribute::findOneBy(['tl_salsify_attribute.pid=?', 'tl_salsify_attribute.attribute_key=?'],[$salsify_product->id, $key]);
                                if($update_sa != null) {
                                    
                                    fwrite($myfile, "Updating Salsify Attribute ID: ".$update_sa->id."\n");
                                    
                                    $update_sa->attribute_value = $val[0];
                                    
                                    // If there is an Isotope Option we need to break this link so we can re-link and generate the option the right way.
                                    // If there is no Isotope Option during an update then we can safely assume this is plain text and just push the new value in
                                    if($update_sa->linked_isotope_attribute_option != null) {
                                        $update_sa->linked_isotope_attribute = null;
                                        $update_sa->linked_isotope_attribute_option = null;
                                        $update_sa->status = 'fail';
                                    }
                                    
                                    $update_sa->tstamp = time();
                                    $update_sa->published = 1;
                                    $update_sa->save();
        
                                } else {
                                    $salsify_attribute = new SalsifyAttribute();
                                    $salsify_attribute->pid = $salsify_product->id;
                                    $salsify_attribute->attribute_key = $key;
                                    $salsify_attribute->attribute_value = $val[0];
                                    
                                    // AUTOLINK REMOVED, just go straight to null
                                    //$salsify_attribute->linked_isotope_attribute = null;
                                    
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
            
        }
    }
    
    // Close our logfile
    fclose($myfile);
