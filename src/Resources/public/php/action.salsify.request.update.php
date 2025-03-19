<?php

    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecom_user", 'I6aX,Ud-EYa^]P9u8g', "ecom_contao_4_13");
    if ($dbh->connect_error) {
    die("Connection failed: " . $dbh->connect_error);
    }
    
    
    // Loop through all Salsify Requests
    $sr_query =  "SELECT * FROM tl_salsify_request ORDER BY id ASC";
    $sr_result = $dbh->query($sr_query);
    if($sr_result) {
        while($request = $sr_result->fetch_assoc()) {
            
            // Build complete folder address
            $folder = $_SERVER['DOCUMENT_ROOT'] . "/../files/" . $request['source_folder'];
            
            // DEBUGS
            echo "Request ID: " . $request['id'] . "<br>";
            echo "Request Name: " . $request['request_name'] . "<br>";
            echo "Folder URL: " . $folder . "<br><br>";
            
            // Filter for only things that contain a period in the name
            $files = array_filter(scandir($folder), function($file) {
                return $file[0] !== '.';
            });
            
            // Loop throuhg our found files
            foreach($files as $file) {
                echo "File: $file<br>";
                echo "Last Modified: " . date("m/d/y h:i:s A", filemtime($folder . "/" . $file)) . "<br>";
            }
            
            // DEBUGS
            echo "<hr><br>";
            
        }
    }
