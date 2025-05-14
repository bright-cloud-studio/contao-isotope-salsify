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
    
    $mapping = array();
    
    
    ////////////////
    // STAGE DATA //
    ////////////////
    
    // Loop through SalsifyRequests
    $salsify_requests = SalsifyRequest::findBy(['status = ?'], ['awaiting_auto_linking']);
    if($salsify_requests) {
        foreach ($salsify_requests as $sr)
		{
		    // Loop through SalsifyProducts
		    $salsify_products = SalsifyProduct::findBy(['pid = ?', 'published = ?'], [$sr->id, 1]);
            if($salsify_products) {
                foreach ($salsify_products as $sp)
        		{
        		    // Loop through SalsifyAttributes
        		    $salsify_attributes = SalsifyAttribute::findBy(['pid = ?', 'published = ?'], [$sp->id, 1]);
                    if($salsify_attributes) {
                        foreach ($salsify_attributes as $sa)
                		{
                		    // If we have a linked attribute, save it to our mapping
                		    if($sa->linked_isotope_attribute != '')
                		        $mapping[$sp->isotope_product_type][$sa->attribute_key] = $sa->linked_isotope_attribute;
                		}
                    }
                    
        		}
            }

		}
    }
    
    
    ////////////////////////
    // APPLY TO UN-LINKED //
    ////////////////////////
    
    
    // Loop through SalsifyAttributes
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
            						//$attribute->linked_isotope_attribute_option = $option->id;
            						$option_ids[] = $option->id;
            						fwrite($myfile, "Option Found: ".$option->id.", adding to option_ids array \n");
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
        						fwrite($myfile, "New Option Created: ".$new_option->id.", adding to option_ids array \n");
            				}
                            
                        }
                        
                        $unlinked_sa->linked_isotope_attribute_option = serialize($option_ids);
				        fwrite($myfile, "Saving Linked Attribute Option serialized array \n");

                    }
                    
                    $unlinked_sa->save();
                }
                
            }
		}
    }

    // Close our logfile
    fclose($myfile);
