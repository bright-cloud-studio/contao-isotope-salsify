<?php

    /********************************/
    /** INITS AND INCLUDES - START **/
    /********************************/
    
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Product;
    
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
    
    /*******************************/
    /** INITS AND INCLUDES - STOP **/
    /*******************************/
    
    // Get any SalsifyRequests that are on this step
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_related_linking']);
    if($salsify_requests) {
        
	    debug("[SalsifyRequest on 'awaiting_related_linking' Found] Processing all Isotope Products");

	    // Empty the tl_iso_related_product table so we can start fresh
        $reset_related_product_query =  "TRUNCATE TABLE tl_iso_related_product;";
        $reset_related_product_result = $dbh->query($reset_related_product_query);
        debug("[Table 'tl_iso_related_product'] Clearing existing database entries so we start fresh");

        $isotope_products = Product::findBy(['tl_iso_product.pid!=?'], [-1]);
        
        if($isotope_products) {
            debug("[Isotope Products Found] Looping through to find Related Products");
            foreach($isotope_products as $isotope_product) {
                debug("[Isotope Product ID: " . $isotope_product->id . "] Searching for Related Products", 1);
                
                // Get our Related Product SKUs, add them to $linked array for later processing
                $cleaned = str_replace(' ', '', $isotope_product->related_products);
                $cleaned = explode(",",$cleaned);
                $linked[$isotope_product->id] = $cleaned;
                
                debug("[Isotope Product ID: " . $isotope_product->id . "] Adding SKUs to seek list: " . $isotope_product->related_products, 2);
            }
        }

        // For each SalsifyRequest on this status, update them to the next step
        foreach ($salsify_requests as $sr)
		{
		    debug("[SalsifyRequest ID: ".$sr->id."] Moving SalsifyRequest to the next step");
		    $sr->status = 'awaiting_new_file';
            //$sr->save();
		}
    }

    
    debug("Looping through linked array", 3);
    
    // Loop through $linked
    foreach($linked as $key => $skus) {
        
        debug("[Isotope Product ID: " . $key . "] Seeking Related Products", 3);
        
        $ids = array();
        foreach($skus as $sku) {

            $related_product = Product::findOneBy(['tl_iso_product.sku=?'], [$sku]);
            if($related_product) {
                $ids[] = $related_product->id;
                debug("[Seeking Isotope Product by SKU: " . $sku . "] [FOUND Related Isotope Product ID: " . $related_product->id . "]", 4);
            } else {
                debug("[Seeking Isotope Product by SKU: " . $sku . "] [NO Isotope Product found for this SKU!]", 5);
            }

        }
        
        $rp = array();
        $rp['pid'] = $key;
        $rp['tstamp'] = time();
        $rp['category'] = 1;
        $rp['products'] = implode(",", $ids);
        $rp['productsOrder'] = serialize($ids);
        $priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_related_product %s")->set($rp)->execute();
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
