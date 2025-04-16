<?php

    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Attribute;
    
    // LOG - Create log file
    $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/salsify_autolink_category_pages_'.strtolower(date('m_d_y_H:m:s')).".txt", "w") or die("Unable to open file!");
    
    // INITS
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    // DATABASE CONNECTION
    $dbh = new mysqli("localhost", "ecomm2_user", '(nNFuy*d8O=aC@BDCh', "ecomm2_contao_413");
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }
    
    ////////////////////////////////////////////////////////////////////////////////////////
    // AUTO-LINK Category Pages to Salsify Products //
    ////////////////////////////////////////////////////////////////////////////////////////

    // Loop through all Salsify Products
    $sp_query =  "SELECT * FROM tl_salsify_product ORDER BY id ASC";
    $sp_result = $dbh->query($sp_query);
    if($sp_result) {
        while($product = $sp_result->fetch_assoc()) {
            
            echo "Product ID: " . $product['id'] . "<br>";
            
            
            // loop through each attribute
            $sa_query =  "SELECT * FROM tl_salsify_attribute WHERE pid='".$product['id']."' AND is_cat='1' ORDER BY id ASC";
            $sa_result = $dbh->query($sa_query);
            if($sa_result) {
                while($attribute = $sa_result->fetch_assoc()) {
                    
                    echo "Attribute ID: " . $attribute['id'] . "<br>";
                    echo "Attribute Key: " . $attribute['attribute_key'] . "<br>";
                    echo "Attribute Value: " . $attribute['attribute_value'] . "<br><br>";
                    
                    // Find a page with this title
                    $page_query =  "SELECT * FROM tl_page WHERE title='".$attribute['attribute_value']."' AND published='1' ORDER BY id ASC";
                    $page_result = $dbh->query($page_query);
                    if($page_result) {
                        while($page = $page_result->fetch_assoc()) {
                            
                            echo "Page ID: " . $page['id'] . "<br>";
                            echo "Page Title: " . $page['title'] . "<br>";
                            echo "Reader Page ID: " . $page['iso_readerJumpTo'] . "<br><br>";
                            
                            // Validate that this page belongs to this root
                            $type = $page['type'];
                            
                            // If we don't have 'root', we are still on a child page
                            while($type != 'root') {

                            }

                            // If we are here, we have the 'root' page

                            // i page id == selected root, validate
                            
    
                            
                            $update =  "update tl_salsify_attribute set category_page='".$page['id']."' WHERE id='".$attribute['id']."'";
                            $result_update = $dbh->query($update);
                            
                            echo "SalsifyAttribute Linked!<br>";
                            
                            $update =  "update tl_salsify_product set category_page='".$page['id']."' WHERE id='".$product['id']."'";
                            $result_update = $dbh->query($update);
                            
                            echo "SalsifyProduct Linked!<br>";
                            
                            
                        }
                    }
                    
                    echo "<hr><br>";
                    
                }
            }

        }
    }
    
    // LOG - Close our log file
    fclose($myfile);
