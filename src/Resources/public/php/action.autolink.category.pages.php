<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    
    use Contao\PageModel;
    
    use Isotope\Model\Attribute;
    
    // LOG - Create log file
    $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/salsify_autolink_category_pages_'.strtolower(date('m_d_y_H:m:s')).".txt", "w") or die("Unable to open file!");
    
    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecom_user", 'I6aX,Ud-EYa^]P9u8g', "ecom_contao_4_13");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }

    
    
    //////////////////////////////////////////////////
    // AUTO-LINK Category Pages to Salsify Products //
    //////////////////////////////////////////////////


    // Get Salsify Requests that are in the 'awaiting_cat_linking' state
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_cat_linkinging']);
    if(!$salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
            
            // Loop through all Salsify Products that belong to this Salsify Request
            $sp_query =  "SELECT * FROM tl_salsify_product WHERE pid='".$sr->id."' ORDER BY id ASC";
            $sp_result = $dbh->query($sp_query);
            if($sp_result) {
                while($product = $sp_result->fetch_assoc()) {
                    
                    echo "Product ID: " . $product['id'] . "<br>";
                    
                    
                    // loop through each attribute
                    $sa_query =  "SELECT * FROM tl_salsify_attribute WHERE pid='".$product['id']."' AND is_cat='1' ORDER BY id ASC";
                    $sa_result = $dbh->query($sa_query);
                    if($sa_result) {
                        while($attribute = $sa_result->fetch_assoc()) {
                            
                            echo "Attribute ID: " . $attribute['id'] . "<br>";
                            echo "Attribute Key: " . $attribute['attribute_key'] . "<br>";
                            echo "Attribute Value: " . $attribute['attribute_value'] . "<br><br>";
                            
                            // Break our value down into CSV stuffs
                            $page_titles = explode(", ", $attribute['attribute_value']);
                            
                            $page_ids = array();
                            
                            // Loop through all of our titles
                            foreach($page_titles as $title) {
                                
                                echo "Page Title: " . $title . "<br>";
                                
                                
                                // Find a page with this title
                                $page_query =  "SELECT * FROM tl_page WHERE title='".$title."' AND published='1' ORDER BY id ASC";
                                $page_result = $dbh->query($page_query);
                                if($page_result) {
                                    while($page = $page_result->fetch_assoc()) {
                                        
                                        echo "Page ID: " . $page['id'] . "<br>";
                                        echo "Page Title: " . $page['title'] . "<br>";
                                        echo "Reader Page ID: " . $page['iso_readerJumpTo'] . "<br><br>";
                                        
                                        // Validate that this page belongs to the selected root
                                        $page_type = $page['type'];
                                        $pid = $page['pid'];
                                        $id = $page['id'];
                                        
                                        // while we dont have the root page
                                        fwrite($myfile, "Entering Loop! \n");
                                        while ($page_type != 'root') {
                                            
                                            // get the pid page, see if that gets us there
                                            //$parent = PageModel::findPublishedByIdOrAlias($pid);
                                            $parent = PageModel::findBy(['id = ?'], [$pid]);
                                            
                                            $page_type = $parent->type;
                                            $pid = $parent->pid;
                                            $id = $parent->id;
                                            
                                            fwrite($myfile, "Page Type: ". $page_type ."\n");
                                            fwrite($myfile, "PID: ". $pid ."\n");
                                            fwrite($myfile, "ID: ". $id ."\n");
        
                                        }
                                        fwrite($myfile, "Leaving Loop! \n");
                                        
                                        // Now, get the Request and make sure they match!
                                        $request = SalsifyRequest::findBy(['id = ?'], [$product['pid']]);
                                        
                                        $root = unserialize($request->website_root)[0];
                                        echo "Selected Root: " . $root . "<br>";
                                        echo "Our Root: " . $id . "<br>";
                                        
                                        if($root == $id) {
                                            
                                            fwrite($myfile, "Root page validated!\n");
                                            $page_ids[] = $page['id'];
        
                                        }
                                        
                                    }
                                }
                            }
                            
                            if(!$page_ids) {
                                
                            } else {
                                
                                echo "IDS: " . numbersArrayToCsv($page_ids) . "<br>";
                                
                                $update =  "update tl_salsify_attribute set category_page='".numbersArrayToCsv($page_ids)."' WHERE id='".$attribute['id']."'";
                                $result_update = $dbh->query($update);
                                
                                echo "SalsifyAttribute Linked!<br>";
                                
                                $update =  "update tl_salsify_product set category_page='".numbersArrayToCsv($page_ids)."' WHERE id='".$product['id']."'";
                                $result_update = $dbh->query($update);
                                
                                echo "SalsifyProduct Linked!<br>";
                            }
        
                            echo "<hr><br>";
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
    fclose($myfile);
    
    
    function numbersArrayToCsv(array $numbers, string $delimiter = ','): string {
        // Filter out non-numeric values to ensure only numbers are included
        $numericValues = array_filter($numbers, 'is_numeric');
    
        // Implode the array with the specified delimiter
        $csvString = implode($delimiter, $numericValues);
    
        return $csvString;
    }
