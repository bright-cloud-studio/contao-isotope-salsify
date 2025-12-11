<?php

    /** INITS AND INCLUDES - START **/
    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Contao\PageModel;
    use Isotope\Model\Attribute;
    
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

    
    
    //////////////////////////////////////////////////
    // AUTO-LINK Category Pages to Salsify Products //
    //////////////////////////////////////////////////


    // Get Salsify Requests that are in the 'awaiting_cat_linking' state
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_cat_linking']);
    
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
		    debug($debug_mode, $log, "Getting Salsify Products for Salsify Request: ". $sr->id);
            
            // Loop through all Salsify Products that belong to this Salsify Request
            $sp_query =  "SELECT * FROM tl_salsify_product WHERE pid='".$sr->id."' ORDER BY id ASC";
            $sp_result = $dbh->query($sp_query);
            if($sp_result) {
                while($product = $sp_result->fetch_assoc()) {
                    
                    debug($debug_mode, $log, "Processing Salsify Product ID: ". $product['id']);
                    
                    // loop through each attribute
                    $sa_query =  "SELECT * FROM tl_salsify_attribute WHERE pid='".$product['id']."' AND is_cat='1' ORDER BY id ASC";
                    $sa_result = $dbh->query($sa_query);
                    if($sa_result) {
                        while($attribute = $sa_result->fetch_assoc()) {
                            
                            debug($debug_mode, $log, "Processing Salsify Attribute ID: ". $attribute['id']);
                            
                            // Break our value down into CSV stuffs
                            $page_titles = explode(", ", $attribute['attribute_value']);
                            
                            $page_ids = array();
                            
                            // Loop through all of our titles
                            foreach($page_titles as $title) {
                                
                                debug($debug_mode, $log, "Attempting to find Page titled: ". $title);
                                
                                // Find a page with this title
                                $page_query =  "SELECT * FROM tl_page WHERE title='".$title."' AND published='1' ORDER BY id ASC";
                                $page_result = $dbh->query($page_query);
                                if($page_result) {
                                    while($page = $page_result->fetch_assoc()) {
                                        
                                        debug($debug_mode, $log, "Page FOUND titled: ". $page['title']);
                                        
                                        // Validate that this page belongs to the selected root
                                        $page_type = $page['type'];
                                        $pid = $page['pid'];
                                        $id = $page['id'];
                                        
                                        debug($debug_mode, $log, "Validating this page belongs to selected root");
                                        
                                        // while we dont have the root page
                                        while ($page_type != 'root') {
                                            
                                            // get the pid page, see if that gets us there
                                            //$parent = PageModel::findPublishedByIdOrAlias($pid);
                                            $parent = PageModel::findBy(['id = ?'], [$pid]);
                                            
                                            $page_type = $parent->type;
                                            $pid = $parent->pid;
                                            $id = $parent->id;
        
                                        }
                                        
                                        // Now, get the Request and make sure they match!
                                        $request = SalsifyRequest::findBy(['id = ?'], [$product['pid']]);
                                        
                                        $root = unserialize($request->website_root)[0];
                                        
                                        if($root == $id) {
                                            
                                            debug($debug_mode, $log, "Validation success, belongs to our selected Root");
                                                
                                            $page_ids[] = $page['id'];
                                        } else {
                                            debug($debug_mode, $log, "Validation failed...");
                                        }
                                        
                                    }
                                }
                            }
                            
                            if(!$page_ids) {
                                
                            } else {
                                
                                $page_csv = numbersArrayToCsv($page_ids);
                                
                                debug($debug_mode, $log, "Adding SalsifyProduct to the following pages: ". $page_csv);
                                
                                $update =  "update tl_salsify_attribute set category_page='".$page_csv."' WHERE id='".$attribute['id']."'";
                                $result_update = $dbh->query($update);
                                
                                $update =  "update tl_salsify_product set category_page='".$page_csv."' WHERE id='".$product['id']."'";
                                $result_update = $dbh->query($update);

                            }
        
                        }
                    }
                }
            }
            
            // Update the status of our Salsify Request and save it
            $sr->status = 'awaiting_iso_generation';
            $sr->save();

        }
    }
    
    
    // LOG - Close our log file
    if($debug_mode)
        fclose($log);
    
    
    
    /** HELPER FUNCTIONS **/
    function debug($debug_mode, $log, $message) {
        if($debug_mode)
            fwrite($log, $message . "\n");
        echo $message . "<br>";
    }
    
    function numbersArrayToCsv(array $numbers, string $delimiter = ','): string {
        // Filter out non-numeric values to ensure only numbers are included
        $numericValues = array_filter($numbers, 'is_numeric');
    
        // Implode the array with the specified delimiter
        $csvString = implode($delimiter, $numericValues);
    
        return $csvString;
    }
