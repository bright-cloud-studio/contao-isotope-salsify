<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Attribute;
    use pcrov\JsonReader\JsonReader;
    
    
    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecom_user", 'I6aX,Ud-EYa^]P9u8g', "ecom_contao_4_13");
    if ($dbh->connect_error) {
    die("Connection failed: " . $dbh->connect_error);
    }



    // Loop through all Salsify Requests
    $sr_query =  "SELECT * FROM tl_salsify_request ORDER BY id ASC";
    $sr_result = $dbh->query($sr_query);
    if($sr_result) {
        while($request = $sr_result->fetch_assoc()) {


            // STEP ONE - Determine which file is the latest


            // Build complete folder address
            $folder = $_SERVER['DOCUMENT_ROOT'] . "/../files/" . $request['source_folder'];
            
            // Filter for only things that contain a period in the name
            $files = array_filter(scandir($folder), function($file) {
                return $file[0] !== '.';
            });
            
            // Store the values of the latest file as we loop through them
            $latest_file_url = '';
            $latest_file_date = '';
            
            // Loop throuhg our found files
            foreach($files as $file) {
                $file_date = filemtime($folder . "/" . $file);
                
                // If the files date is newer, save the values
                if($file_date > $latest_file_date) {
                    $latest_file_url = $file;
                    $latest_file_date = $file_date;
                }
                
            }
            
            // If our found file's date is newer, update
            if($latest_file_date > (int)$request['file_date']) {
                $request['file_url'] = $latest_file_url;
                $dbh->prepare("UPDATE tl_salsify_request SET file_url='". $latest_file_url ."', file_date='" . $latest_file_date . "', flag_update='1' WHERE id='".$request['id']."'")->execute();
            }
            
            
            
            
            
            
            // STEP TWO - Create or update Salsify Products and Salsify Attributes
            
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
            		$prod_count++;

                    // Find and update, else create
                    $salsify_product;
                    $update_sp = SalsifyProduct::findOneBy(['tl_salsify_product.product_sku=?'],[$array_child[$request['isotope_sku_key']][0]]);
                    if($update_sp != null) {
                        echo "SalsifyProduct Found and Updated!<br>";
                        $update_sp->pid = $request['id'];
                		$update_sp->tstamp = time();
                		$update_sp->product_sku = $array_child[$request['isotope_sku_key']][0];
                		$update_sp->product_name = $array_child[$request['isotope_name_key']][0];
                		$update_sp->save();
                		$salsify_product = $update_sp;
                        
                    } else {
                        echo "SalsifyProduct Created!<br>";
                		$salsify_product = new SalsifyProduct();
                		$salsify_product->pid = $request['id'];
                		$salsify_product->tstamp = time();
                		$salsify_product->product_sku = $array_child[$request['isotope_sku_key']][0];
                		$salsify_product->product_name = $array_child[$request['isotope_name_key']][0];
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
                            echo "SalsifyAttribute Found and Updated!<br>";
                            $update_sa->attribute_value = $val[0];
                            
                            // If autolink, find iso attribute, otherwise return null
                            if($request['autolink_isotope_attributes'] == '1') {
                                $iso_attr = Attribute::findBy(['field_name = ?'], [$key]);
                                $update_sa->linked_isotope_attribute = $iso_attr->id;
                            } else {
                                $update_sa->linked_isotope_attribute = null;
                            }
                            
                            $update_sa->tstamp = time();
                            $update_sa->save();

                        } else {
                            echo "SalsifyAttribute Created!<br>";
                            $salsify_attribute = new SalsifyAttribute();
                            $salsify_attribute->pid = $salsify_product->id;
                            $salsify_attribute->attribute_key = $key;
                            $salsify_attribute->attribute_value = $val[0];
                            
                            // If autolink, find iso attribute, otherwise return null
                            if($request['autolink_isotope_attributes'] == '1') {
                                $iso_attr = Attribute::findBy(['field_name = ?'], [$key]);
                                $salsify_attribute->linked_isotope_attribute = $iso_attr->id;
                                
                            } else {
                                $salsify_attribute->linked_isotope_attribute = null;
                            }
                            
                            $salsify_attribute->tstamp = time();
                            $salsify_attribute->save();

                        }
                        
                        $attributes[$salsify_attribute->id]['key'] = $key;
                        $attributes[$salsify_attribute->id]['value'] = $val[0];
                        $log[$salsify_product->id]['attributes'] = $attributes;
                    }
                    
            	}
            
            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
            
            $reader->close();


        }
    }
