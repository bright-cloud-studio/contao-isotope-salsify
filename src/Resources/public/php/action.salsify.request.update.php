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
            
            // Store the values of the latest file as we loop through them
            $latest_file_url = '';
            $latest_file_date = '';
            
            // Loop throuhg our found files
            foreach($files as $file) {
                
                $file_date = filemtime($folder . "/" . $file);
                
                // DEBUGS
                echo "File: $file<br>";
                echo "Last Modified: " . date("m/d/y h:i:s A", filemtime($folder . "/" . $file)) . "<br>";
                
                // If the files date is newer, save the values
                if($file_date > $latest_file_date) {
                    $latest_file_url = $file;
                    $latest_file_date = $file_date;
                }
                
                
            }
            
            
            // If our found file's date is newer, update
            if($latest_file_date > (int)$request['file_date']) {
                echo "Newer File Found!<br>";
                
                
                //$sr_query =  "SELECT * FROM tl_salsify_request ORDER BY id ASC";
                //$sr_result = $dbh->query($sr_query);
                
                $dbh->prepare("UPDATE tl_salsify_request SET file_url='". $latest_file_url ."', file_date='" . $latest_file_date . "' WHERE id='".$request['id']."'")->execute();
                
                
                
            }
            
            
            
            echo "Latest File: " . $latest_file_url . "<br>";
            echo "Latest Date: " . date("m/d/y h:i:s A", $latest_file_date) . "<br>";
            
            
            // DEBUGS
            echo "<hr><br>";
            
        }
    }
