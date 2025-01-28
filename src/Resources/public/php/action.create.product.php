<?php

    // Initialize
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // Database Connection
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
    die("Connection failed: " . $dbh->connect_error);
    }
    
    
    // Loop through the Salsify Products
    
    
        // Make sure all of the attributes "pass"
        
        
            // Create products if everything is "pass"
    
    
    echo "success";
