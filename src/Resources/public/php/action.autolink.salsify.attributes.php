<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Attribute;
    
    // Stores log messages until the end
    $log_messages = '';
    $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/salsify_autolink_'.strtolower(date('m_d_y_H:m:s')).".txt", "w") or die("Unable to open file!");
    
    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }
    
    ////////////////////////////////////////////////////////////////////////////////////////
    // AUTO-LINK SalsifyAttribute to IsotopeAttribue based on field name and product type //
    ////////////////////////////////////////////////////////////////////////////////////////

    // First things first, g
    $sp_query =  "SELECT * FROM tl_salsify_product ORDER BY id ASC";
    $sp_result = $dbh->query($sp_query);
    if($sp_result) {
        while($product = $sp_result->fetch_assoc()) {

            echo "<pre>";
            print_r($product);
            echo "</pre>";
            echo "<br><hr><br>";

        }
    }
    
    fclose($myfile);
