<?php

    use Bcs\Model\SalsifyRequest;

    // INITIALIZE STUFFS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }


    // Stores log messages until the end
    $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/autolink_isotope_attributes'.strtolower(date('m_d_y')).".txt", "a+") or die("Unable to open file!");


    $linked = array();
    
    // Get Salsify Requests that are in the 'awaiting_cat_linking' state
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_related_linking']);
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{

            echo "Searching for Products in Salsify Request: " . $sr->id . "<br>";
            fwrite($myfile, "Searching for Product in Salsify Request: " . $sr->id . "\n");

            // LOOP THROUGH PRODUCTS
            $prod_query =  "SELECT * FROM tl_iso_product ORDER BY id ASC";
            $prod_result = $dbh->query($prod_query);
            if($prod_result) {
                while($prod = $prod_result->fetch_assoc()) {
                    
                    
                    echo "Staging data for Product: " . $prod[''] . "<br>";
                    fwrite($myfile, "Staging data for Product: " . $prod[''] . "\n");
                    
                    $found = false;
                    
                    // Loop through all related product entries
                    $related_query =  "SELECT * FROM tl_iso_related_product ORDER BY id ASC";
                    $related_result = $dbh->query($related_query);
                    if($related_result) {
                        while($related = $related_result->fetch_assoc()) {
                            // If one of our entries matches 
                            if($prod['id'] == $related['pid'])
                                $found = true;
                        }
                    }
                    
                    // If we didnt find a related products entry yet, make one
                    if(!$found) {
                        $cleaned = str_replace(' ', '', $prod['related_products']);
                        $cleaned = explode(",",$cleaned);
        
                        $linked[$prod['id']] = $cleaned;
                    }
                }
            }
            
            // Update the status of our Salsify Request and save it
            $sr->status = 'awaiting_new_file';
            $sr->save();
    
		}
    }

    
    
    // Loop through $linked
    foreach($linked as $key => $skus) {
        
        $ids = array();
        foreach($skus as $sku) {
            
            $prod_query =  "SELECT * FROM tl_iso_product where sku='".$sku."' ORDER BY id ASC";
            $prod_result = $dbh->query($prod_query);
            if($prod_result) {
                while($prod = $prod_result->fetch_assoc()) {
                    
                    $ids[] = $prod['id'];
                }
            }
        }
        
        echo "IDS: <br><pre>";
        fwrite($myfile, "IDs: " . print_r($ids) . "\n");
        print_r($ids);
        echo "</pre><br><hr><br>";
        
        $rp = array();
        $rp['pid'] = $key;
        $rp['tstamp'] = time();
        $rp['category'] = 1;
        
        $rp['products'] = implode(",", $ids);
    
        $rp['productsOrder'] = serialize($ids);
        $priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_related_product %s")->set($rp)->execute();
    }
