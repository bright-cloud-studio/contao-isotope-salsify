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
    
    // Loop through all of our Attributes, find ones that are identical to this, and link them to the same Isotope Attribute
    public function linkSimilarAttributes()
    {
        // Stores our linked attribute values so we can apply them on the second loop
        $linked = array();
        // Stores the KEY of whatever attribute that is checked as a category field
        $cat_field_key = '';
        // Stores the KEY of whatever attribute that is checked as being used for the SKU
        $sku_field_key = '';
        // Stores the KEY of whatever attribute that is checked is being used for the Product Name
        $product_name_field_key = '';

        // Stores the KEY of whatever attribute that is checked as being use for grouping
        $grouping_field_key = '';
        $isotope_product_type = '';
        $isotope_product_type_variant = '';
        $group_counter = array();

        $category_parent_page = '';
        $category_parent_key = '';
        $category_parent_value = '';
        
        // Get all SalsifyAttributes with the same key
        $salsify_attributes = SalsifyAttribute::findAll();



        // LOOP ONE - STAGE DATA
        foreach($salsify_attributes as $attr) {
            
            
            // If we have an isotope attribute assigned, save it
            if($attr->linked_isotope_attribute != null) {
                
                // Get the Isotope Attribute
                $iso_attr = Attribute::findBy(['id = ?'], [$attr->linked_isotope_attribute]);
                
                // Get our parents variant type
                $parent = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attr->pid]);
                if($parent != null) {

                    // Store universal settings
                    $linked[$attr->attribute_key][$parent->isotope_product_variant_type]['isotope_attribute'] = $attr->linked_isotope_attribute;
                    $linked[$attr->attribute_key][$parent->isotope_product_variant_type]['isotope_attribute_type'] = $iso_attr->type;
                    
                    // Store 'Select' settings
                    if($iso_attr->type == 'select' || $iso_attr->type == 'radio') {
                        // Find all Options for this Attribute
                        $iso_attr_opts = AttributeOption::findByPid($attr->linked_isotope_attribute);
                        $opt_found = false;
                        foreach($iso_attr_opts as $iso_attr_opt) {
                            // If an Option's label matches our Attribute Value, it already exists
                            if($iso_attr_opt->label == $attr->attribute_value) {
                                $opt_found = true;
                                $linked[$attr->attribute_key][$parent->isotope_product_variant_type]['options'][$attr->attribute_value]['isotope_attribute_option'] = $iso_attr_opt->id;
                                $attr->linked_isotope_attribute_option = $iso_addr_opt->id;
                                $attr->save();
                            }
                        }
                        // If no Attribute Option is found, create it
                        if($opt_found != true) {
                            $new_attr_opt = new AttributeOption();
                            $new_attr_opt->pid = $attr->linked_isotope_attribute;
                            $new_attr_opt->label = $attr->attribute_value;
                            $new_attr_opt->tstamp = time();
                            $new_attr_opt->published = 1;
                            $new_attr_opt->ptable = 'tl_iso_attribute';
                            $new_attr_opt->type = 'option';
                            $new_attr_opt->save();
                            $linked[$attr->attribute_key][$parent->isotope_product_variant_type]['options'][$attr->attribute_value]['isotope_attribute_option'] = $new_attr_opt->id;
                            
                            $attr->linked_isotope_attribute_option = $new_attr_opt->id;
                            $attr->save();
                        }
                    }
                    
                }
 
            }
            
            
            // If we have a parent page and a reader page set
            if($attr->category_parent_page != null && $attr->category_reader_page != null) {
                
                // Try to find page, if not create it
                $pid = unserialize($attr->category_parent_page);
                $pid_r = unserialize($attr->category_reader_page);
                $page = PageModel::findBy(['pid = ?', 'title = ?'], [$pid[0], $attr->attribute_value]);
                
                if($page != null) {
                    
                    $linked[$attr->attribute_key][$attr->attribute_value]['category_parent_page'] = $pid;
                    $linked[$attr->attribute_key][$attr->attribute_value]['category_reader_page'] = $pid_r;
                    $linked[$attr->attribute_key][$attr->attribute_value]['category_page'] = $page->id;
                    
                } else {
                    
                    // Generate Page
                    $new_page = new PageModel();
                    $new_page->pid = $pid[0];
                    $new_page->title = $attr->attribute_value;
                    $new_page->alias = strtolower($attr->attribute_value);
                    
                    $new_page->robots = "noindex,nofollow";
                    $new_page->enableCanonical = 1;
                    $new_page->sitemap = "map_default";
                    
                    $new_page->iso_readerJumpTo = $pid_r[0];
                    $new_page->iso_readerMode = "page";
                    
                    // Get Page Layout from parent page
                    $new_page->includeLayout = 1;
                    $new_page->layout = 29;
                    
                    $new_page->published = 1;
                    $new_page->tstamp = time();
                    $new_page->save();

                    $linked[$attr->attribute_key][$attr->attribute_value]['category_parent_page'] = $pid;
                    $linked[$attr->attribute_key][$attr->attribute_value]['category_reader_page'] = $pid_r;
                    $linked[$attr->attribute_key][$attr->attribute_value]['category_page'] = $new_page->id;
                }
                
            }
            
            
                
            // If this is checked as a category field, save it for the next full loop
            if($attr->is_sku)
                $sku_field_key = $attr->attribute_key;

            if($attr->is_name)
                $product_name_field_key = $attr->attribute_key;

            if($attr->category_parent_page != null) {
                $cat = unserialize($attr->category_parent_page);
                $category_parent_page = $cat[0];
                $category_parent_key = $attr->attribute_key;
                $category_parent_value = $attr->attribute_value;
            }
            
            
            // If all thee grouping settings are filled in
            if($attr->is_grouping && $attr->isotope_product_type != null && $attr->isotope_product_type_variant != null) {
                
                $grouping_field_key = $attr->attribute_key;
                $isotope_product_type = $attr->isotope_product_type;
                $isotope_product_type_variant = $attr->isotope_product_type_variant;

                //$isotope_product_type_key = $attr->attribute_key;
                //$isotope_product_type_value = $attr->attribute_value;
            }
            
        }
        
        
        
        // LOOP TWO: Link matching SalsifyAttributes
        foreach($salsify_attributes as $attr) {
            
            // Tracks if a change was made, and if we need to save() or not at the end
            $save = false;

            $parent = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attr->pid]);
            if($parent != null) {
                
                if($linked[$attr->attribute_key][$parent->isotope_product_variant_type]) {

                    $attr->linked_isotope_attribute = $linked[$attr->attribute_key][$parent->isotope_product_variant_type]['isotope_attribute'];
                    $attr->linked_isotope_attribute_option = $linked[$attr->attribute_key][$parent->isotope_product_variant_type]['options'][$attr->attribute_value]['isotope_attribute_option'];
                    $save = true;
                }
                
            }


            // Apply 'Site Category' value to similar SalsifyAttributes
            if($attr->attribute_key == $cat_field_key) {
                $attr->site_category_field = 1;
                $save = true;
            }

            // Product SKU
            if($attr->attribute_key == $sku_field_key) {
                $attr->is_sku = 1;

                // Find the parent SalsifyProduct and update the SKU to match this
                $salsify_product = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attr->pid]);
                if($salsify_product != null) {
                    $salsify_product->product_sku = $attr->attribute_value;
                    $salsify_product->save();
                }
                $save = true;
            }

            // Product Name
            if($attr->attribute_key == $product_name_field_key) {
                $attr->is_name = 1;

                // Find the parent SalsifyProduct and update the SKU to match this
                $salsify_product = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attr->pid]);
                if($salsify_product != null) {
                    $salsify_product->product_name = $attr->attribute_value;
                    $salsify_product->save();
                }
                $save = true;
            }
            
            // Variant Grouping
            if($attr->attribute_key == $grouping_field_key) {
                
                $attr->is_grouping = 1;
                $attr->isotope_product_type = $isotope_product_type;
                $attr->isotope_product_type_variant = $isotope_product_type_variant;
                
                $group_counter[$attr->attribute_value] = $group_counter[$attr->attribute_value] + 1;
                

                // Find the parent SalsifyProduct and update the 'variant_group' to match this
                $salsify_product = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attr->pid]);
                if($salsify_product != null) {
                    $salsify_product->variant_group = $attr->attribute_value;
                    $salsify_product->save();
                }
                
                $save = true;
            }

            // Product Type
            /*
            if($attr->attribute_key == $isotope_product_type_key) {
                if($attr->attribute_value == $isotope_product_type_value) {
                    $attr->isotope_product_type = $isotope_product_type;
                    $salsify_product = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attr->pid]);
                    if($salsify_product != null) {
                        $salsify_product->isotope_product_type = $isotope_product_type;
                        $salsify_product->isotope_product_type_linked = 'linked';
                        $salsify_product->save();
                    }
                    $save = true;
                }
            }
            */
            
            
            
            // Check if our attribute_key/attribute_value has stored info in $linked
            if($linked[$attr->attribute_key][$attr->attribute_value]['category_page'] != null) {
                
                $attr->category_parent_page = $linked[$attr->attribute_key][$attr->attribute_value]['category_parent_page'];
                $attr->category_reader_page = $linked[$attr->attribute_key][$attr->attribute_value]['category_reader_page'];
                $attr->category_page = $linked[$attr->attribute_key][$attr->attribute_value]['category_page'];
                $salsify_product = SalsifyProduct::findOneBy(['tl_salsify_product.id=?'],[$attr->pid]);
                if($salsify_product != null) {
                    $salsify_product->category_page = $linked[$attr->attribute_key][$attr->attribute_value]['category_page'];
                    $salsify_product->save();
                }
                $save = true;
            }
            
            // If $save has been flagged anywhere, save this bad boy
            if($save)
                $attr->save();
                
        }
        
        
        
        // Third Grouping Loop
        if($group_counter != null) {
            
            // Get all SalsifyAttributes with the same key
            $salsify_products = SalsifyProduct::findAll();
            foreach($salsify_products as $prod) {
                
                if($group_counter[$prod->variant_group] == 1) {
                    
                    $prod->isotope_product_variant_type = 'single';
                    $prod->isotope_product_type = $isotope_product_type;
                    
                    
                } else {
                    $prod->isotope_product_variant_type = 'variant';
                    $prod->isotope_product_type = $isotope_product_type_variant;
                }
                
                $prod->isotope_product_type_linked = 'linked';
                $prod->save();
            }
            
        }
        
        
    }

    
    // Display error until all 'flags' are 
    public function generateStatusLabel($row, $label, $dc, $args)
    {
        $site_category_field = '';
        $is_sku = '';
           
        if($row['site_category_field'] == 1) {
            $site_category_field = "Alias: <span style='color: green;'>TRUE</span> - ";
        }
        if($row['is_sku'] == 1) {
            $site_category_field = "SKU: <span style='color: green;'>TRUE</span> - ";
        }
        
        if($row['is_grouping'] == 1) {
            $site_category_field = "GROUP: <span style='color: green;'>TRUE</span> - ";
        }

        if($row['linked_isotope_attribute'] == null)
            return "Status: <span style='color: red;'>FAIL</span> - " . $site_category_field . $label;
        else
            return "Status: <span style='color: green;'>PASS</span> - " . $site_category_field . $label;
            
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
        $options = array();
        $attributes = Attribute::findAll();
        while($attributes->next()) {
            $attr = $attributes->row();
            if(in_array($attr['field_name'], $linked_attributes)) {
                $options[$attr['id']] = $attr['name'];
            }
        }
        return $options;
    }

    // Build an array with the KEY being the ID of the Isotope Attribute Option and the VALUE is the text-readable label
    public function getIsotopeAttributeOptions()
    {
        $options = array();
        $attributes = AttributeOption::findAll();
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
	
}
