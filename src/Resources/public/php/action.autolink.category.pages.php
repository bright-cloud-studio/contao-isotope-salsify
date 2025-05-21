<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Contao\PageModel;
    use Isotope\Model\Attribute;
    
    // Debug mode and log file
    $debug_mode = true;
    if($debug_mode)
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/'.date('m_d_y').'_autolink_category_pages.txt', "a+") or die("Unable to open file!");
    
    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }

    
    
    //////////////////////////////////////////////////
    // AUTO-LINK Category Pages to Salsify Products //
    //////////////////////////////////////////////////


    // Get Salsify Requests that are in the 'awaiting_cat_linking' state
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_cat_linking']);
    
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
		    if($debug_mode)
		        fwrite($log, "Getting Salsify Products for Salsify Request: ". $sr->id ."\n");
            
            // Loop through all Salsify Products that belong to this Salsify Request
            $sp_query =  "SELECT * FROM tl_salsify_product WHERE pid='".$sr->id."' ORDER BY id ASC";
            $sp_result = $dbh->query($sp_query);
            if($sp_result) {
                while($product = $sp_result->fetch_assoc()) {
                    
                    if($debug_mode)
                        fwrite($log, "Processing Salsify Product ID: ". $product['id'] ."\n");
                    
                    
                    // loop through each attribute
                    $sa_query =  "SELECT * FROM tl_salsify_attribute WHERE pid='".$product['id']."' AND is_cat='1' ORDER BY id ASC";
                    $sa_result = $dbh->query($sa_query);
                    if($sa_result) {
                        while($attribute = $sa_result->fetch_assoc()) {
                            
                            if($debug_mode)
                                fwrite($log, "Processing Salsify Attribute ID: ". $attribute['id'] ."\n");
                            
                            // Break our value down into CSV stuffs
                            $page_titles = explode(", ", $attribute['attribute_value']);
                            
                            $page_ids = array();
                            
                            // Loop through all of our titles
                            foreach($page_titles as $title) {
                                
                                if($debug_mode)
                                    fwrite($log, "Attempting to find Page titled: ". $title ."\n");
                                
                                // Find a page with this title
                                $page_query =  "SELECT * FROM tl_page WHERE title='".$title."' AND published='1' ORDER BY id ASC";
                                $page_result = $dbh->query($page_query);
                                if($page_result) {
                                    while($page = $page_result->fetch_assoc()) {
                                        
                                        if($debug_mode)
                                            fwrite($log, "Page FOUND titled: ". $page['title'] ."\n");
                                        
                                        // Validate that this page belongs to the selected root
                                        $page_type = $page['type'];
                                        $pid = $page['pid'];
                                        $id = $page['id'];
                                        
                                        if($debug_mode)
                                            fwrite($log, "Validating this page belongs to selected root! \n");
                                        
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
                                        echo "Selected Root: " . $root . "<br>";
                                        echo "Our Root: " . $id . "<br>";
                                        
                                        if($root == $id) {
                                            
                                            if($debug_mode)
                                                fwrite($log, "Validation success, belongs to our selected Root \n");
                                                
                                            $page_ids[] = $page['id'];
                                        } else {
                                            if($debug_mode)
                                                fwrite($log, "Validation failed... \n");
                                        }
                                        
                                    }
                                }
                            }
                            
                            if(!$page_ids) {
                                
                            } else {
                                
                                $page_csv = numbersArrayToCsv($page_ids);
                                
                                if($debug_mode)
                                    fwrite($log, "Adding SalsifyProduct to the following pages: ". $page_csv ."\n");
                                
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
    
    
    function numbersArrayToCsv(array $numbers, string $delimiter = ','): string {
        // Filter out non-numeric values to ensure only numbers are included
        $numericValues = array_filter($numbers, 'is_numeric');
    
        // Implode the array with the specified delimiter
        $csvString = implode($delimiter, $numericValues);
    
        return $csvString;
    }
