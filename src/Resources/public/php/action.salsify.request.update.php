<?php

    use Contao\StringUtil;

    // INITIALIZE STUFFS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    
    $dbh = new mysqli("localhost", "ecom_user", 'I6aX,Ud-EYa^]P9u8g', "ecom_contao_4_13");
    if ($dbh->connect_error) {
    die("Connection failed: " . $dbh->connect_error);
    }
    
    
    // Loop through the Salsify Products
    $sr_query =  "SELECT * FROM tl_salsify_request ORDER BY id ASC";
    $sr_result = $dbh->query($sr_query);
    if($sr_result) {
        while($request = $sr_result->fetch_assoc()) {
            
            
            echo "<pre>";
            print_r($request);
            echo "</pre><br><hr><br>";
            
            
            
        }
    }
