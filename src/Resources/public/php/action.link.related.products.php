<?php

    // INITIALIZE STUFFS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
    die("Connection failed: " . $dbh->connect_error);
    }

    $linked = array();



    // LOOP THROUGH PRODUCTS
    $prod_query =  "SELECT * FROM tl_iso_product ORDER BY id ASC";
    $prod_result = $dbh->query($prod_query);
    if($prod_result) {
        while($prod = $prod_result->fetch_assoc()) {
            
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
    
    
    // Loop through $linked
    
    foreach($linked as $key => $skus) {
        
        foreach($skus as $sku) {
            
            $ids = array();
            
            $prod_query =  "SELECT * FROM tl_iso_product where item_number='".$sku."' ORDER BY id ASC";
            $prod_result = $dbh->query($prod_query);
            if($prod_result) {
                while($prod = $prod_result->fetch_assoc()) {
                    array_push($ids, $prod['id']);
                }
            }
        }
        
        $rp = array();
            $rp['pid'] = $key;
            $rp['tstamp'] = time();
            $rp['category'] = 1;
            
            $first = true;
            foreach($ids as $id) {
                if($first) {
                    $first = false;
                    $rp['products'] = strval($id);
                } else {
                    $rp['products'] =  $rp['products'] . ", " . strval($id);
                }
            }
            
            
            
            $rp['productsOrder'] = serialize($ids);
            
            $priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_related_product %s")
                             ->set($rp)
                             ->execute();
        
        
    }
