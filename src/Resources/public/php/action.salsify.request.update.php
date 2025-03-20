<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    
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
            
            // DEBUGS
            echo "Request ID: " . $request['id'] . "<br>";
            echo "Request Name: " . $request['request_name'] . "<br>";
            echo "Folder URL: " . $folder . "<br><br>";
            
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
                
                // DEBUGS
                echo "File: $file<br>";
                echo "Last Modified: " . date("m/d/y h:i:s A", filemtime($folder . "/" . $file)) . "<br>";
                
                // If the files date is newer, save the values
                if($file_date > $latest_file_date) {
                    $latest_file_url = $file;
                    $latest_file_date = $file_date;
                }
            }
            
            // If our found file's date is newer, update
            if($latest_file_date > (int)$request['file_date']) {
                
                // DEBUGS
                echo "Request ID:" . $request['id'] . " has found a new file!<br>";
                echo "New File URL: " . $latest_file_url . "<br>";
                echo "New File Date: " . date("m/d/y h:i:s A", $latest_file_date) . "<br>";
                
                $dbh->prepare("UPDATE tl_salsify_request SET file_url='". $folder . "/" . $latest_file_url ."', file_date='" . $latest_file_date . "', flag_update='1' WHERE id='".$request['id']."'")->execute();
            }

            

            // STEP TWO - Create or update Salsify Products and Salsify Attributes
            
            $reader = new JsonReader();
            // Update to use Salsify Request file
            $reader->open($folder . "/" . $latest_file_url);
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
            		
            		// Create a Salsify Product to hold our Salsify Attributes
            		$salsify_product = new SalsifyProduct();
            		$salsify_product->tstamp = time();
            		$salsify_product->product_sku = $do_loop . '_' . $prod_count;
            		$salsify_product->name = 'product_' . $do_loop . '_' . $prod_count;
            		$salsify_product->save();

                    $attributes = array();
                
                    $prod_values = array();



                    foreach($array_child as $key => $val) {
                        
                        $prod_values[$key] = $val[0];
                        
                        
                        $salsify_attribute = new SalsifyAttribute();
                        $salsify_attribute->pid = $salsify_product->id;
                        $salsify_attribute->attribute_key = $key;
                        $salsify_attribute->attribute_value = $val[0];
                        $salsify_attribute->isotope_linked_attribute = null;
                        $salsify_attribute->tstamp = time();
                        $salsify_attribute->save();
                        
                        $attributes[$salsify_attribute->id]['key'] = $key;
                        $attributes[$salsify_attribute->id]['value'] = $val[0];
                        $log[$salsify_product->id]['attributes'] = $attributes;
                        
                    }

            	}
            
            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
            
            $reader->close();
            
            
            
            
            
            
            
            
            
        
            // STEP THREE - Create or update Isotope Products
            
            
            
            // DEBUGS
            echo "<hr><br>";
        }
    }
