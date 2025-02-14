<?php

    // INITIALIZE STUFFS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
    die("Connection failed: " . $dbh->connect_error);
    }

    $prod_query =  "SELECT * FROM tl_salsify_product ORDER BY id ASC";
    $prod_result = $dbh->query($prod_query);
    if($prod_result) {
        while($prod = $prod_result->fetch_assoc()) {
            
        }
    }



    // Loop through all products
    // Loop through the Salsify Products
    $prod_query =  "SELECT * FROM tl_salsify_product ORDER BY id ASC";
    $prod_result = $dbh->query($prod_query);
    if($prod_result) {
        while($prod = $prod_result->fetch_assoc()) {
            
            
            // See if it has an entry in tl_iso_related_products
    
            //If not, make one,
            
            //If so, pass
                
            
        }
    }
    

    
