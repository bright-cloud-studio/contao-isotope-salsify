<?php

    /** INITS AND INCLUDES - START **/
    use Bcs\Model\SalsifyAttribute;
    use Bcs\Model\SalsifyProduct;
    use Bcs\Model\SalsifyRequest;
    use Isotope\Model\Attribute;
    use Isotope\Model\AttributeOption;
    use pcrov\JsonReader\JsonReader;

    $debug_mode = true;
    if($debug_mode)
        $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/step_two_'.date('m_d_y').'.txt', "a+") or die("Unable to open file!");
    
    session_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
    
    $serializedData = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/../db.txt');
	$db_info = unserialize($serializedData);
    $dbh = new mysqli("localhost", $db_info[0], $db_info[1], $db_info[2]);
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }

    /** INITS AND INCLUDES - STOP **/
    
    
    
    
    ////////////////
    // STAGE DATA //
    ////////////////
    
    $grouping_counter = array();
    
    
    // Loop through Salsify Requests on Step Two
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_grouping']);
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
		    debug($debug_mode, $log, "[Checking SalsifyRequest] ID: ".$sr->id. " - " . $sr->request_name);
		    
		    // Loop through Salsify Products that belong to this Salsify Request
		    $salsify_products = SalsifyProduct::findBy(['pid = ?'], [$sr->id]);
            if($salsify_products) {
                
                debug($debug_mode, $log, "\tProducts Found: " . count($salsify_products));
                
                foreach ($salsify_products as $sp)
        		{

        		    // Find THIS products Salsify Attribute that controls grouping
        		    $salsify_attribute = SalsifyAttribute::findOneBy(['pid = ?', 'is_grouping = ?'], [$sp->id, 1]);
                    if($salsify_attribute) {
                        
                        // Find all Salsify Attributes that are grouping with the same value, that arent our kickoff one
                        $others_in_group = SalsifyAttribute::findBy(['id != ?', 'attribute_value = ?', 'is_grouping = ?'], [$salsify_attribute->id, $salsify_attribute->attribute_value, 1]);
                        if($others_in_group) {
                            debug($debug_mode, $log, "\t[Salsify Product ID: " . $sp->id . "] [Group: ".$salsify_attribute->attribute_value."] Detected as Variant Product");
                            
                            // Salsify Product is Variant
                            $sp->variant_group = $salsify_attribute->attribute_value;
                            $sp->isotope_product_variant_type = 'variant';
                            $sp->isotope_product_type = $sr->isotope_product_type_variant;
                            $sp->save();
                            
                        } else {
                            debug($debug_mode, $log, "\t[Salsify Product ID: " . $sp->id . "] Detected as Single Product");
                            
                            // Salsify Product is Single Product
                            $sp->variant_group = $salsify_attribute->attribute_value;
                            $sp->isotope_product_variant_type = 'single';
                            $sp->isotope_product_type = $sr->isotope_product_type;
                            $sp->save();
                        }
                        
                        //$grouping_counter[$salsify_attribute->attribute_value] += 1;
                        //debug($debug_mode, $log, "\t[Grouping: ".$salsify_attribute->attribute_value."] Total In Group: " . $grouping_counter[$salsify_attribute->attribute_value]);
                    }

        		}

        		

            } else {
                // Add a blank line between our Salsify Requests
                debug($debug_mode, $log, "\tNo Salsify Products found");
            }
            
            // Add a blank line between our Salsify Requests
            debug($debug_mode, $log, "- - - - - - - - - - - - - - - - - - - - - -\n");

		}
    }
    


    // Close our logfile
    if($debug_mode)
        fclose($log);
        
        
    /** HELPER FUNCTIONS **/
    function debug($debug_mode, $log, $message) {
        if($debug_mode)
            fwrite($log, $message . "\n");
        echo $message . "<br>";
    }
