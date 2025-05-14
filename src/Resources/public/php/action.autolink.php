<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    
    use Isotope\Model\Attribute;
    use Isotope\Model\AttributeOption;
    use pcrov\JsonReader\JsonReader;
    

    // Stores log messages until the end
    $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/autolink_isotope_attributes'.strtolower(date('m_d_y')).".txt", "w") or die("Unable to open file!");
    
    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecom_user", 'I6aX,Ud-EYa^]P9u8g', "ecom_contao_4_13");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }
    
    fwrite($myfile, "Processing Salsify Requests\n");
    
    // Loop through all Salsify Requests that are in the 'awaiting_new_file' state
    $sr_query =  "SELECT * FROM tl_salsify_request WHERE status='awaiting_new_file' ORDER BY id ASC";
    $sr_result = $dbh->query($sr_query);
    if($sr_result) {
        while($request = $sr_result->fetch_assoc()) {
            
            fwrite($myfile, "Processing SalsifyRequest: ".$request['id']."\n");

            
            
            
        }
    }
    
    // Close our logfile
    fclose($myfile);
