<?php

    /** INITS AND INCLUDES - START **/
    use Bcs\Model\SalsifyRequest;
    
    /* DEBUG STUFFS */
    define('DEBUG_MODE', true);
    define('DEBUG_FILE', fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/step_six_'.date('m_d_y').'.txt', "a+"));

    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    $serializedData = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/../db.txt');
	$db_info = unserialize($serializedData);
    $dbh = new mysqli("localhost", $db_info[0], $db_info[1], $db_info[2]);
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }

    $linked = array();
    /** INITS AND INCLUDES - STOP **/
    
    
    // Get Salsify Requests that are in the 'awaiting_cat_linking' state
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_related_linking']);
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
		    debug("[Salsify Request ID: $sr->id] Searching for Isotope Products - OUTDATED!");
		    
            // LOOP THROUGH PRODUCTS
            $prod_query =  "SELECT * FROM tl_iso_product ORDER BY id ASC";
            $prod_result = $dbh->query($prod_query);
            if($prod_result) {
                debug("[Isotope Products Found] Looping through to find Related Products");
                while($prod = $prod_result->fetch_assoc()) {
                    
                    debug("[Isotope Product ID: " . $prod['id'] . "] Searching for Related Products", 1);
                    
                    $found = false;
                    
                    // Loop through all related product entries
                    $related_query =  "SELECT * FROM tl_iso_related_product ORDER BY id ASC";
                    $related_result = $dbh->query($related_query);
                    if($related_result) {
                        while($related = $related_result->fetch_assoc()) {
                            // If one of our entries matches 
                            if($prod['id'] == $related['pid']) {
                                $found = true;
                                debug("[Isotope Related Product ID: " . $related['id'] . "] Existing entry in Isotope Related Products found", 2);
                            }
                        }
                    }
                    
                    // If we didnt find a related products entry yet, make one
                    if(!$found) {
                        $cleaned = str_replace(' ', '', $prod['related_products']);
                        $cleaned = explode(",",$cleaned);
        
                        $linked[$prod['id']] = $cleaned;
                        
                        debug("[Isotope Product ID: " . $prod['id'] . "] No existing entry found. Adding to linked: " . $prod['related_products'], 2);
                    }
                }
            }
            
            // Update the status of our Salsify Request and save it
            $sr->status = 'awaiting_new_file';
            //$sr->save();
    
		}
    }

    
    debug("Looping through linked array", 3);
    
    // Loop through $linked
    foreach($linked as $key => $skus) {
        
        
        debug("[KEY: " . $key . "]", 3);
        
        $ids = array();
        foreach($skus as $sku) {
            
            debug("[SKU: " . $sku . "]", 4);
            
            $prod_query =  "SELECT * FROM tl_iso_product where sku='".$sku."' ORDER BY id ASC";
            $prod_result = $dbh->query($prod_query);
            if($prod_result) {
                while($prod = $prod_result->fetch_assoc()) {
                    
                    $ids[] = $prod['id'];
                    debug("[Isotope Product ID: " . $prod['id'] . "] Linked Product Found", 4);
                }
            }
        }
        
        $rp = array();
        $rp['pid'] = $key;
        $rp['tstamp'] = time();
        $rp['category'] = 1;
        
        $rp['products'] = implode(",", $ids);
    
        $rp['productsOrder'] = serialize($ids);
        $priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_related_product %s")->set($rp)->execute();
        
        debug("[PID: " . $rp['pid'] . "] Updating DB", 4);
    }
    
    // Empty out and reset (aka. TRUNCATE) the tl_iso_productcache table
    $reset_productcache_query =  "TRUNCATE TABLE tl_iso_productcache;";
    $reset_productcache_result = $dbh->query($reset_productcache_query);
    
    
    // Close our log file
    if(DEBUG_MODE)
        fclose(DEBUG_FILE);
    
    
    /** Helper Functions **/
    function debug($message, $indent_level = 0) {
        if(DEBUG_MODE) {
            $indent = str_repeat("\t", $indent_level);
            $message = $indent . $message;
            fwrite(DEBUG_FILE, $message . "\n");
            echo $message . "<br>";
            
        } else {
            fwrite(DEBUG_FILE, "DEBUG MODE not active" . "\n");
        }
    }
