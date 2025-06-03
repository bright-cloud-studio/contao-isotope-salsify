<?php

namespace Bcs\Backend;

use Bcs\Model\SalsifyAttribute;
use Bcs\Model\SalsifyProduct;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Image;
use Contao\Input;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;

use Contao\PageModel;

use Isotope\Model\Attribute;
use Isotope\Model\AttributeOption;
use Isotope\Model\ProductType;

class SalsifyAttributeBackend extends Backend
{

    
    public function addLinkMatchingAttributes($arrButtons, DataContainer $dc)
	{
	    
	    // If we have submiited the page
		if (Input::post('link_similar') !== null && Input::post('FORM_SUBMIT') == 'tl_salsify_attribute')
		{
		    // Create log file
	        $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/link_matching_attributes_'.strtolower(date('m_d_y')).".txt", "a+") or die("Unable to open file!");
	    
            // Stores counts of the uses of the grouping value
            $group_counter = array();
            $publish_tracker = array();
            $isotope_product_type = '';
            $isotope_product_type_variant = '';
            
            // If our kickoff has 'controls_published' ticked, add to our publish tracker
            if($dc->activeRecord->controls_published) {
                $publish_tracker[$dc->activeRecord->pid] = $dc->activeRecord->attribute_value;
                //echo "Added activeRecord to Publish Tracker";
            }
            
		    
		    // Find  all SalsifyAttributes where the the 'KEY' is the same
		    $matching_attributes = SalsifyAttribute::findBy(['attribute_key = ?', 'request = ?'], [$dc->activeRecord->attribute_key, $dc->activeRecord->request]);
		    if($matching_attributes) {
		        
		        // Write to log
	            fwrite($myfile, "Kickoff SalsifyAttribute ID: " . $dc->activeRecord->id . "\n\n");
		        
		        foreach($matching_attributes as $attribute) {
		            $save = false;
	                
	                // ISOTOPE ATTRIBUTE LINKING
	                if($dc->activeRecord->linked_isotope_attribute != null) {
	                    
		                // Get our parents
		                $kickoff_parent = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$dc->activeRecord->pid]);
		                $matching_parent = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attribute->pid]);
		                
		                // If the variant type matches
		                if($kickoff_parent->isotope_product_variant_type == $matching_parent->isotope_product_variant_type) {
		                    
		                    fwrite($myfile, "Variant Type Match!" . "\n");
		                    
		                    // Apply the linked attribute
		                    $attribute->linked_isotope_attribute = $dc->activeRecord->linked_isotope_attribute;
		                    $attribute->attribute_option_sorting = $dc->activeRecord->attribute_option_sorting;
		                    $attribute->status = 'pass';
		                    fwrite($myfile, "New Attribute Linked ID: " . $attribute->id . "\n");
		                    
		                    
		                    
		                    // Link or Create Option
		                    $iso_attr = Attribute::findBy(['id = ?'], [$attribute->linked_isotope_attribute]);
		                    if($iso_attr->type == 'select' || $iso_attr->type == 'radio') {
		                        fwrite($myfile, "Linked Isotope Attribute detected as SELECT or RADIO \n");
		                        
		                        
		                        
		                        // Covert 'attribute_value' into array, loop through
		                        $option_ids = array();
		                        $attribute_values = explode(", ", $attribute->attribute_value);
		                        foreach($attribute_values as $val) {
		                            
		                            // Find all Options for this Attribute
                    				$existing_options = AttributeOption::findByPid($attribute->linked_isotope_attribute);
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
                    					$new_option->pid = $attribute->linked_isotope_attribute;
                    					$new_option->label = $val;
                    					$new_option->tstamp = time();
                    					$new_option->published = 1;
                    					$new_option->ptable = 'tl_iso_attribute';
                    					$new_option->type = 'option';
                    					
                    					// Sorting
                    					if($attribute->attribute_option_sorting == 'sort_numerical') {
                    						// Strip everything but numbers from label, use that as sorting number
                    						$only_number = preg_replace("/[^0-9]/","", $attr->attribute_value);
                    						$new_option->sorting = $only_number;
                    						
                    					} else if($attribute->attribute_option_sorting == 'sort_alphabetical') {
                    						// Get just the first letter of the label, convert to number in alphabet, use as sorting number
                    						$alphabet = range('A', 'Z');
                    						$only_letter = substr($attr->attribute_value, 0);
                    						
                    						$new_option->sorting = $alphabet[$only_letter];
                    					}
    
                    					$new_option->save();
                    					
                    					$option_ids[] = $new_option->id;
                						fwrite($myfile, "New Option Created: ".$new_option->id.", adding to option_ids array \n");
                    					
                    					//$attribute->linked_isotope_attribute_option = $new_option->id;
                    					//fwrite($myfile, "New Option Created and Linked \n");
                    				}
		                            
		                        }
		                        
		                        
                                $attribute->linked_isotope_attribute_option = serialize($option_ids);
            					fwrite($myfile, "Saving Linked Attribute Option serialized array \n");

 
		                    }
		                    
		                    
		                    
                            $save = true;
		                }
	                }
	                
	                
	                
	                
    		        
    		        // GROUPING - Spread to matching 'attribute_key'
    		        if($dc->activeRecord->is_grouping && $dc->activeRecord->isotope_product_type != null && $dc->activeRecord->isotope_product_type_variant != null) {

                        if($isotope_product_type == '') {
                            $isotope_product_type = $dc->activeRecord->isotope_product_type;
                            $isotope_product_type_variant = $dc->activeRecord->isotope_product_type_variant;
                        }
                        
    		            // Apply the same settings to this matching SalsifyAttribute
    		            $attribute->is_grouping = 1;
    		            $attribute->isotope_product_type = $dc->activeRecord->isotope_product_type;
    		            $attribute->isotope_product_type_variant = $dc->activeRecord->isotope_product_type_variant;

                        // Add a +1 count to the value stored
                        $group_counter[$attribute->attribute_value] = $group_counter[$attribute->attribute_value] + 1;
    		            
    		            // Write to log
	                    fwrite($myfile, "Grouping applied to SalsifyAttribute ID: " . $attribute->id . "\n");
    		            
    		            // Update the SalsifyProduct parent
                        $salsify_product = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attribute->pid]);
                        if($salsify_product != null) {
                            $salsify_product->variant_group = $attribute->attribute_value;
                            $salsify_product->save();
                            
                            // Write to log
	                        fwrite($myfile, "Updating Parent SalsifyProduct ID: " . $salsify_product->id . "\n");
                        }
    		            
    		            // Flag for saving
    		            $save = true;
    		        }
    		        
    		        
    		        
    		        // CATEGORY
    		        if($dc->activeRecord->is_cat) {
    		            $attribute->is_cat = 1;
    		            $save = true;
    		            
    		            // Write to log
	                    fwrite($myfile, "Category applied to SalsifyAttribute ID: " . $attribute->id . "\n");
    		            
    		        }
    		        
    		        
    		        // CONTROL PUBLISHED
    		        if($dc->activeRecord->controls_published) {
    		            $attribute->controls_published = 1;
    		            $publish_tracker[$attribute->pid] = $attribute->attribute_value;
    		            
    		            
    		            $save = true;
    		            
    		            // Write to log
	                    fwrite($myfile, "Controls Published applied to SalsifyAttribute ID: " . $attribute->id . "\n");
    		            
    		        }
    		        
    		        
		            
		            // SAVE IF UPDATED
    		        if($save) {
    		            $attribute->save();
    		            // Write to log
    	               fwrite($myfile, "Saving SalsifyAttribute ID: " . $attribute->id . "\n\n");
    		        }
		        
		        }
		        
		        

		    }
		    
		   
		   
            // Update Grouping values once all other updates have processed
            if($group_counter != null) {
                
                fwrite($myfile, "Grouping SalsifyProducts \n\n");
                
                $salsify_products = SalsifyProduct::findAll();
                foreach($salsify_products as $prod) {
                    
                    if($group_counter[$prod->variant_group] == 1) {
                        $prod->isotope_product_variant_type = 'single';
                        $prod->isotope_product_type = $isotope_product_type;
                        
                        fwrite($myfile, "SalsifyProduct ID: " . $prod->id . " set as 'single' using Isotope Product Type ID: " . $isotope_product_type . "\n\n");
                        
                    } else {
                        $prod->isotope_product_variant_type = 'variant';
                        $prod->isotope_product_type = $isotope_product_type_variant;
                        
                        fwrite($myfile, "SalsifyProduct ID: " . $prod->id . " set as 'variant' using Isotope Product Type ID: " . $isotope_product_type_variant . "\n\n");
                        
                    }
                    $prod->isotope_product_type_linked = 'linked';
                    $prod->save();
                }
                
            }
            
            
            
            // Update SalsifyProducts, unpublish when necessary
            foreach($publish_tracker as $key => $val) {
                if($val == 'false' || $val == '') {
                    $prod_to_unpublish = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$key]);
                    if($prod_to_unpublish != null) {
                        $prod_to_unpublish->published = '';
                        $prod_to_unpublish->save();
                        
                        fwrite($myfile, "SalsifyProduct Un-Published ID: " . $prod_to_unpublish->id . "\n");
                        
                    }
                }
            }
            
            // Close our log file
	        fclose($myfile);

            // Redirect back to the list view
		    $this->redirect($this->getReferer());
		}
	    
	    // Create and add our button
	    $arrButtons['link_similar'] = '<input type="submit" name="link_similar" id="link_similar" class="tl_submit" accesskey="a" value="'.$GLOBALS['TL_LANG']['tl_salsify_attribute']['link_similar'].'"> ';
		return $arrButtons;
	}


    
    // Display error until all 'flags' are 
    public function generateStatusLabel($row, $label, $dc, $args)
    {
        $site_category_field = '';
        $is_sku = '';
        $cat = '';
           
        if($row['site_category_field'] == 1) {
            $site_category_field = "Alias: <span style='color: green;'>TRUE</span> - ";
        }
        if($row['is_sku'] == 1) {
            $site_category_field = "SKU: <span style='color: green;'>TRUE</span> - ";
        }
        
        if($row['is_grouping'] == 1) {
            $site_category_field = "GROUP: <span style='color: green;'>TRUE</span> - ";
        }
        
        if($row['controls_published'] == 1) {
            $site_category_field = "CONTROL PUBLISH: <span style='color: green;'>TRUE</span> - ";
        }
        
        if($row['is_cat'] != '') {
            if(!$row['category_page'])
                $cat = "CAT: <span style='color: red;'>UNSET</span> - ";
            else
                $cat = "CAT: <span style='color: green;'>".$row['category_page']."</span> - ";
        }
        

        if($row['status'] == 'pass')
            return "Status: <span style='color: green;'>PASS</span> - " . $site_category_field . $cat .  $label;
        else
            return "Status: <span style='color: red;'>FAIL</span> - " . $site_category_field . $cat .  $label;
            
    }

    // Build an array with the KEY being the ID of the Isotope Attribute and the VALUE is the text-readable name
    public function getIsotopeAttributes(DataContainer $dc)
    {
        
        $linked_attributes = array();
        
        // first check if salsify product has product type selected
        $sp = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$dc->activeRecord->pid]);
        if($sp != null) {
            
            if($sp->isotope_product_type != null) {

                $pt = ProductType::findOneBy(['tl_iso_producttype.id=?'],[$sp->isotope_product_type]);
                if($pt != null) {
                    
                    
                    if($pt->variant_attributes != null) {
                        foreach($pt->variant_attributes as $key => $attr) {
                            
                            if($attr['enabled'] == '1') {
                                $linked_attributes[] = $key;
                            }
                        }
                    } else {
                        foreach($pt->attributes as $key => $attr) {
                            if($attr['enabled'] == '1') {
                                $linked_attributes[] = $key;
                            }
                        }
                    }
                    
                    
                }
                
            }
            
        }
        $opt = [
            'order' => 'name ASC'
        ];
        $options = array();
        $attributes = Attribute::findAll($opt);
        while($attributes->next()) {
            $attr = $attributes->row();
            if(in_array($attr['field_name'], $linked_attributes)) {
                $options[$attr['id']] = $attr['name'] . " (" . $attr['field_name'] . ")";
            }
        }
        return $options;
    }

    // Build an array with the KEY being the ID of the Isotope Attribute Option and the VALUE is the text-readable label
    public function getIsotopeAttributeOptions()
    {
        $opt = [
            'order' => 'label ASC'
        ];
        $options = array();
        $attributes = AttributeOption::findAll($opt);
        if($attributes) {
            while($attributes->next()) {
                $attr = $attributes->row();
                $options[$attr['id']] = $attr['label'];
            }
        }
        return $options;
    }

    // Build an array with the KEY being the ID of the Isotope Attribute and the VALUE is the text-readable name
    public function getIsotopeProductTypes()
    {
        $options = array();
        $attributes = ProductType::findAll();
        while($attributes->next()) {
            $attr = $attributes->row();
            $options[$attr['id']] = $attr['name'];
        }
        return $options;
    }
    
    
    
    
    
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
        if (strlen(Input::get('tid')))
		{
			$this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1), (@func_get_arg(12) ?: null));
			$this->redirect($this->getReferer());
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

		if (!$row['published'])
		{
			$icon = 'invisible.gif';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
	}	
	
	public function toggleVisibility($intId, $blnVisible, DataContainer $dc=null)
	{
		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_salsify_attribute']['fields']['published']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_salsify_attribute']['fields']['published']['save_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$blnVisible = $this->$callback[0]->$callback[1]($blnVisible, ($dc ?: $this));
				}
				elseif (is_callable($callback))
				{
					$blnVisible = $callback($blnVisible, ($dc ?: $this));
				}
			}
		}

		// Update the database
		$this->Database->prepare("UPDATE tl_salsify_attribute SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")->execute($intId);
		System::getContainer()->get('monolog.logger.contao.cron')->info('A new version of record "tl_transactions.id='.$intId.'" has been created'.$this->getParentEntries('tl_listing', $intId));
	}
	
	public function generateAlias($varValue, DataContainer $dc)
	{
		$autoAlias = false;
		
		// Generate an alias if there is none
		if ($varValue == '')
		{
			$autoAlias = true;
			$varValue = standardize(StringUtil::restoreBasicEntities($dc->activeRecord->name));
		}

		$objAlias = $this->Database->prepare("SELECT id FROM tl_salsify_attribute WHERE id=? OR alias=?")->execute($dc->id, $varValue);

		// Check whether the page alias exists
		if ($objAlias->numRows > 1)
		{
			if (!$autoAlias)
			{
				throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
			}

			$varValue .= '-' . $dc->id;
		}

		return $varValue;
	}
	
	function csvStringToArray(string $csvString, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array
    {
        $rows = [];
        $lines = explode("\n", trim($csvString));
    
        foreach ($lines as $line) {
            $row = str_getcsv($line, $delimiter, $enclosure, $escape);
            if ($row !== false) {
                $rows[] = $row;
            }
        }
    
        return $rows;
    }
	
	
}
