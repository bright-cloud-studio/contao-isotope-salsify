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
    
    // Loop through Salsify Requests on Step Two
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_auto_linking']);
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
		    debug($debug_mode, $log, "[Checking SalsifyRequest] ID: ".$sr->id. " - " . $sr->request_name);
		    
		    // Loop through Salsify Products that belong to this Salsify Request
		    $salsify_products = SalsifyProduct::findBy(['pid = ?'], [$sr->id]);
            if($salsify_products) {
                foreach ($salsify_products as $sp)
        		{
        		    
        		    debug($debug_mode, $log, "\t[Salsify Product ID: ".$sp->id."] Checking Salsify Attributes");
        		    
        		    // Loop through Salsify Attributes that belong to this Salsify Product
        		    $salsify_attributes = SalsifyAttribute::findBy(['pid = ?', 'published = ?'], [$sp->id, 1]);
                    if($salsify_attributes) {
                        foreach ($salsify_attributes as $sa)
                		{
                		    // If we have a linked attribute, save it to our mapping
                		    if($sa->linked_isotope_attribute == '') {
                		        debug($debug_mode, $log, "\t\t[Salsify Attribute ID: ".$sa->id."][KEY: ".$sa->attribute_key."] Needs Linking");
                		    }
                		    
                		}
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
    
    
    ////////////////////////
    // APPLY TO UN-LINKED //
    ////////////////////////
    
    
    // Loop through SalsifyAttributes
    /*
    $unlinked_attributes = SalsifyAttribute::findBy(['tl_salsify_attribute.linked_isotope_attribute IS null'], []);
    if($unlinked_attributes) {
        foreach ($unlinked_attributes as $unlinked_sa)
		{
		    $parent_salsify_product = SalsifyProduct::findOneBy(['id = ?'], [$unlinked_sa->pid]);
            if($parent_salsify_product) {
                
                // See if we have a linking value in our mapping
                if($mapping[$parent_salsify_product->isotope_product_type][$unlinked_sa->attribute_key] != '') {

                    $unlinked_sa->linked_isotope_attribute = $mapping[$parent_salsify_product->isotope_product_type][$unlinked_sa->attribute_key];
                    $unlinked_sa->status = "pass";
                    
                    ////////////////////////////////
                    // AUTO-LINK ATTRIBUTE OPTION //
                    ////////////////////////////////
                    
                    // Check if 'radio' or 'select'
                    $iso_attr = Attribute::findBy(['id = ?'], [$unlinked_sa->linked_isotope_attribute]);
                    if($iso_attr->type == 'select' || $iso_attr->type == 'radio') {
                        
                        // Store our option IDs until the end
                        $option_ids = array();
                        $attribute_values = explode(", ", $unlinked_sa->attribute_value);
                        // Loop through each CSV value
                        foreach($attribute_values as $val) {
                            
                            // Find all Options for this Attribute
            				$existing_options = AttributeOption::findByPid($unlinked_sa->linked_isotope_attribute);
            				$opt_found = false;
            				foreach($existing_options as $option) {
            					// If an Option's label matches our Attribute Value, it already exists
            					if($option->label == $val) {
            						$opt_found = true;
            						$option_ids[] = $option->id;
            						
            						debug($debug_mode, $log, "Option Found: ".$option->id.", adding to option_ids array");
            					}
            				}
            				// If no Attribute Option is found, create it
            				if($opt_found != true) {
            					$new_option = new AttributeOption();
            					$new_option->pid = $unlinked_sa->linked_isotope_attribute;
            					$new_option->label = $val;
            					$new_option->tstamp = time();
            					$new_option->published = 1;
            					$new_option->ptable = 'tl_iso_attribute';
            					$new_option->type = 'option';
            					
            					// Sorting
            					if($unlinked_sa->attribute_option_sorting == 'sort_numerical') {
            						// Strip everything but numbers from label, use that as sorting number
            						$only_number = preg_replace("/[^0-9]/","", $val);
            						$new_option->sorting = $only_number;
            						
            					} else if($unlinked_sa->attribute_option_sorting == 'sort_alphabetical') {
            						// Get just the first letter of the label, convert to number in alphabet, use as sorting number
            						$alphabet = range('A', 'Z');
            						$only_letter = substr($val, 0);
            						
            						$new_option->sorting = $alphabet[$only_letter];
            					}

            					$new_option->save();
            					
            					$option_ids[] = $new_option->id;
            					
            					debug($debug_mode, $log, "New Option Created: ".$new_option->id.", adding to option_ids array");
            				}
                            
                        }
                        
                        $unlinked_sa->linked_isotope_attribute_option = serialize($option_ids);
                        
                        debug($debug_mode, $log, "Saving Linked Attribute Option serialized array");

                    }
                    
                    
                    $unlinked_sa->save();
                }
                
            }
		}
    }
    
    // Loop through SalsifyRequests
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
		    $sr->status = 'awaiting_cat_linking';
            $sr->save();
		}
    }
    */

    // Close our logfile
    if($debug_mode)
        fclose($log);
        
        
    /** HELPER FUNCTIONS **/
    function debug($debug_mode, $log, $message) {
        if($debug_mode)
            fwrite($log, $message . "\n");
        echo $message . "<br>";
    }
