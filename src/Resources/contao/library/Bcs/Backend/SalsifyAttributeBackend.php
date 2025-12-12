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
    
    private $debug_mode = true;

    public function addLinkMatchingAttributes($arrButtons, DataContainer $dc)
	{

	    // If we have submiited the page
		if (Input::post('link_similar') !== null && Input::post('FORM_SUBMIT') == 'tl_salsify_attribute')
		{
		    // Create log file
		    if($this->debug_mode)
	            $log = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/link_matching_attributes_'.date('m_d_Y').'.txt', "a+") or die("Unable to open file!");
	    
            // Stores counts of the uses of the grouping value
            $group_counter = array();
            $publish_tracker = array();
            
            $isotope_product_type = '';
            $isotope_product_type_variant = '';
            
            // If our kickoff has 'controls_published' ticked, add to our publish tracker
            if($dc->activeRecord->controls_published) {
                $publish_tracker[$dc->activeRecord->pid] = $dc->activeRecord->attribute_value;
            }
            
            $this->debug($this->debug_mode, $log, "[Kickoff Salsify Attribute ID: " . $dc->activeRecord->id . "] [KEY: " . $dc->activeRecord->attribute_key . "] Kicking off Linking for matching Salsify Attributes");
            $this->debug($this->debug_mode, $log, "- - - - - - - - - - - - - - - - - -");
            
		    // Find all Salsify Attributes from this Salsify Request where they key is the same but has no linked Isotope Attribute
		    $matching_attributes = SalsifyAttribute::findBy(['attribute_key = ?', 'request = ?'], [$dc->activeRecord->attribute_key, $dc->activeRecord->request]);
		    if($matching_attributes) {
		        foreach($matching_attributes as $attribute) {

		            $save = false;
	                
	                $this->debug($this->debug_mode, $log, "\t[Salsify Attribute ID: " . $attribute->id . "] Salsify Attribute with matching Salsify Request, Key and no Linked Isotope Attribute");
	                
	                // Check if the parent Salsify Products for our Kickoff and our Matched are the same Isotope Product Type
	                $kickoff_parent = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$dc->activeRecord->pid]);
	                $matching_parent = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attribute->pid]);
	                if($kickoff_parent->isotope_product_variant_type == $matching_parent->isotope_product_variant_type) {
	                    
	                    $this->debug($this->debug_mode, $log, "\t[Salsify Attribute ID: " . $attribute->id . "] Isotope Product Type match");
	                    
	                    if($dc->activeRecord->linked_isotope_attribute == null) {
	                        $this->debug($this->debug_mode, $log, "\tKickoff Salsify Attribute has no Linked Isotope Attribute, skipping linking");
	                    } else {
    	                    
    	                    // Apply the linked attribute
    	                    $attribute->linked_isotope_attribute = $dc->activeRecord->linked_isotope_attribute;
    	                    $attribute->attribute_option_sorting = $dc->activeRecord->attribute_option_sorting;
    	                    $attribute->status = 'pass';
    	                    
    	                    $this->debug($this->debug_mode, $log, "\t[Salsify Attribute ID: " . $attribute->id . "] Kickoff's Salsify Attribute's Linked Isotope Attribute applied");
    	                    
    	                    // Link or Create Option
    	                    $iso_attr = Attribute::findBy(['id = ?'], [$attribute->linked_isotope_attribute]);
    	                    if($iso_attr->type == 'select' || $iso_attr->type == 'radio') {
    	                        
    	                        $this->debug($this->debug_mode, $log, "\t[Isotope Attribute ID: " . $iso_attr->id . "] Type detected as SELECT or RADIO, Isotope Attribute Option required");
    	                        
    	                        // Loop through comma separated attribute values
    	                        $option_ids = array();
    	                        $attribute_values = explode(", ", $attribute->attribute_value);
    	                        foreach($attribute_values as $val) {
    	                            
    	                            // Try and find an existing Attribute Option
    	                            $existing_option = AttributeOption::findOneBy(['tl_iso_attribute_option.pid=?', 'tl_iso_attribute_option.label=?'],[$attribute->linked_isotope_attribute, $val]);
    	                            if($existing_option) {
    	                                
    	                                $option_ids[] = $option->id;
    	                                $this->debug($this->debug_mode, $log, "\t[Isotope Attribute Option ID: " . $option->id . "] Existing Isotope Attribute Option for this Isotope Attribute found");
    	                                
    	                            } else {
    	                                
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
                    					$this->debug($this->debug_mode, $log, "\t[Isotope Attribute Option ID: " . $new_option->id . "] New Isotope Attribute Option created");
    	                            }

    	                            
    	                        }
    
                                $attribute->linked_isotope_attribute_option = serialize($option_ids);
    	                    }
                            $save = true;
	                    }
	                }
	                

	                /** GROUPING HERE **/
	                // If the Kickoff attribute has "grouping" checked and isotope product types assigned
    		        if($dc->activeRecord->is_grouping && $dc->activeRecord->isotope_product_type != null && $dc->activeRecord->isotope_product_type_variant != null) {
                        
                        // Save our Product Types for later
                        $isotope_product_type = $dc->activeRecord->isotope_product_type;
                        $isotope_product_type_variant = $dc->activeRecord->isotope_product_type_variant;
                        
                        // Apply the is_grouping and product type values
                        $attribute->is_grouping = 1;
                        $attribute->isotope_product_type = $dc->activeRecord->isotope_product_type;
    		            $attribute->isotope_product_type_variant = $dc->activeRecord->isotope_product_type_variant;
    		            
                        $save = true;
                        $this->debug($this->debug_mode, $log, "\t[Salsify Attribute ID: ".$attribute->id."] 'is_grouping' applied");
                        
                        // Add a +1 count to the value stored
                        $group_counter[$attribute->attribute_value] = $group_counter[$attribute->attribute_value] + 1;
                        
                        // Apply Variant Group to the parent Salsify Product
                        $salsify_product = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attribute->pid]);
                        if($salsify_product != null) {
                            $salsify_product->variant_group = $attribute->attribute_value;
                            $salsify_product->save();
                            
                            $this->debug($this->debug_mode, $log, "\t[Parent Salsify Product ID: ".$salsify_product->id."] Variant Group applied");

                        }

    		        }

    		        // CATEGORY
    		        if($dc->activeRecord->is_cat) {
    		            $attribute->is_cat = 1;
    		            $save = true;
    		            
    		            $this->debug($this->debug_mode, $log, "\t[Salsify Attribute ID: ".$attribute->id."] 'is_cat' applied");
    		        }
    		        
    		        // CONTROL PUBLISHED
    		        if($dc->activeRecord->controls_published) {
    		            $attribute->controls_published = 1;
    		            $publish_tracker[$attribute->pid] = $attribute->attribute_value;

    		            $save = true;
    		            
    		            $this->debug($this->debug_mode, $log, "\t[Salsify Attribute ID: ".$attribute->id."] 'controls_published' applied");
    		        }
		            
		            // SAVE IF UPDATED
    		        if($save) {
    		            $attribute->save();
    		            
    		            $this->debug($this->debug_mode, $log, "\t[Salsify Attribute ID: ".$attribute->id."] saved");
    		        }
		        
		            // Divider between products to make the log more readable
		            $this->debug($this->debug_mode, $log, "- - - - - - - - - - - - - - - - - -");
		        }
		    }
		    

		   /** GROUPING HERE **/
		   
		   // If 'is_grouping' was applied to a Salsify Attribute, we should regroup all products
            if($isotope_product_type && $isotope_product_type) {

                $this->debug($this->debug_mode, $log, "Regrouping Updated Salsify Products");

                
                foreach($group_counter as $key => $val) {
                    //echo "Key: " . $key . "<br>";
                    //echo "Val: " . $val . "<br>";
                    
                    $salsify_products = SalsifyProduct::findBy(['tl_salsify_product.variant_group=?'],[$key]);
                    if($salsify_products) {
                        foreach($salsify_products as $salsify_product) {
                            if($group_counter[$salsify_product->variant_group] == 1) {
                                $salsify_product->isotope_product_variant_type = 'single';
                                $salsify_product->isotope_product_type = $isotope_product_type;
                                $this->debug($this->debug_mode, $log, "[Salsify Product ID: " . $salsify_product->id . "] Set as 'single' using Isotope Product Type ID: " . $isotope_product_type);
                            } else {
                                $salsify_product->isotope_product_variant_type = 'variant';
                                $salsify_product->isotope_product_type = $isotope_product_type_variant;
                                $this->debug($this->debug_mode, $log, "[Salsify Product ID: " . $salsify_product->id . "] Set as 'variant' using Isotope Product Type ID: " . $isotope_product_type_variant);
                            }
                            
                            $salsify_product->isotope_product_type_linked = 'linked';
                            $salsify_product->save();
                        
                        }
                    }
                    
                }
                //die();
            }
            
            /** GROUPING HERE - END **/
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            // Update SalsifyProducts, unpublish when necessary
            foreach($publish_tracker as $key => $val) {
                if($val == 'false' || $val == '') {
                    $prod_to_unpublish = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$key]);
                    if($prod_to_unpublish != null) {
                        $prod_to_unpublish->published = '';
                        $prod_to_unpublish->save();
                        
                        //$this->debug($this->debug_mode, $log, "SalsifyProduct Un-Published ID: " . $prod_to_unpublish->id);
                    }
                }
            }
            
            if($this->debug_mode)
	            fclose($log);

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
	
	
	
	
	
	
	/** HELPER FUNCTIONS **/
    function debug($debug_mode, $log, $message) {
        if($debug_mode)
            fwrite($log, $message . "\n");
    }
	
	/*
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
    */
	
	
}
