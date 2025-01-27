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

use Isotope\Model\Attribute;

class SalsifyAttributeBackend extends Backend
{
  
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
        
        // Get all SalsifyAttributes with the same key
        $salsify_attributes = SalsifyAttribute::findAll();

        // Loop through all the collected Assignments
        foreach($salsify_attributes as $attr) {
            // If we have an isotope attribute assigned, save it
            if($attr->linked_isotope_attribute != null)
                $linked[$attr->attribute_key] = $attr->linked_isotope_attribute;
            // If this is checked as a category field, save it for the next full loop
            if($attr->site_category_field)
                $cat_field_key = $attr->attribute_key;
            // If this is checked as a category field, save it for the next full loop
            if($attr->is_sku)
                $sku_field_key = $attr->attribute_key;

            if($attr->is_name)
                $product_name_field_key = $attr->attribute_key;
        }
        
        // Loop through again, apply value to similar keys
        foreach($salsify_attributes as $attr) {
            
            $save = false;
            
            // If we have an isotope attribute assigned, save it
            if($attr->linked_isotope_attribute == null) {

                if($linked[$attr->attribute_key]) {
                    $attr->linked_isotope_attribute = $linked[$attr->attribute_key];
                    $save = true;
                }
            }

            // Apply 'Site Category' value to similar SalsifyAttributes
            if($attr->attribute_key == $cat_field_key) {
                $attr->site_category_field = 1;
                $save = true;
            }

            // Apply "Use as SKU" value to similar SalsifyAttributes
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
            
            if($save)
                $attr->save();
            
            
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

        if($row['linked_isotope_attribute'] == null)
            return "Status: <span style='color: red;'>FAIL</span> - " . $site_category_field . $label;
        else
            return "Status: <span style='color: green;'>PASS</span> - " . $site_category_field . $label;
            
    }

    // Build an array with the KEY being the ID of the Isotope Attribute and the VALUE is the text-readable name
    public function getIsotopeAttributes()
    {
        $options = array();
        $attributes = Attribute::findAll();
        while($attributes->next()) {
            $attr = $attributes->row();
            $options[$attr['id']] = $attr['name'];
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

}
