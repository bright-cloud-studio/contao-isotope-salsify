<?php

namespace Bcs\Backend;

use Contao\Backend;
use Contao\Image;
use Contao\Input;
use Contao\DataContainer;
use Contao\StringUtil;

use Bcs\Model\SalsifyProduct;
use Bcs\Model\SalsifyAttribute;

use Isotope\Model\Product;
use Isotope\Model\ProductType;

class SalsifyRequestBackend extends Backend
{
    
    
    // Delete everything spawned from this SalsifyRequest
    public function onDeleteSalsifyRequest(DataContainer $dc) {

        // Loop through all SalsifyProducts that belong to this SalsifyRequest
        $options = ['order' => 'id ASC'];
        $salsify_products = SalsifyProduct::findBy('pid', $dc->id, $options);
		if ($salsify_products)
		{
			foreach ($salsify_products as $product)
		    {
		        
		        // Loop through each SalsifyAttribute that belongs to this SalsifyProduct
		        $salsify_attributes = SalsifyAttribute::findBy('pid', $product->id);
        		if ($salsify_attributes)
        		{
        			foreach ($salsify_attributes as $attribute)
        		    {
        		        // Delete this SalsifyAttribute
        		        $attribute->delete();
        		        //echo "DELETE SalsifyAttribute: " . $attribute->id . "<br>";
        		    }
        		}
        		
        		// Try to delete the Isotope Product in the event it was generated
        		$isotope_product = Product::findOneBy(['tl_iso_product.sku=?'],[$product->product_sku]);
                if($isotope_product != null) {
                    
                    $isotope_product->delete();
                    //echo "DELETE Isotope Product: " . $isotope_product->sku . "<br>";
                }
                
                // NOW, Try to delete and SKU + '_parent' product that we may have generated as a parent holder product
                $isotope_product = Product::findOneBy(['tl_iso_product.sku=?'],[$product->product_sku . "_parent"]);
                if($isotope_product != null) { 
                    $isotope_product->delete();
                    echo "DELETE Isotope Generated Product: " . $isotope_product->sku . "<br>";
                }
    		
        		// Delete this SalsifyProduct
        		$product->delete();
        		//echo "DELETE Salsify Product: " . $product->id . "<br><hr><br>";
		    }
		}

        //die();
    }


    // Generates a custom label for displaying entries on the Salsify Request section of the Contao Backend
    public function generateLabel($row, $label, $dc, $args)
    {
        $new_label = '';
        $new_label .= " [ID: <span style='font-weight: 600;'>" . $row['id'] . "</span>] ";
        $new_label .= " [NAME: <span style='font-weight: 600;'>" . $row['request_name'] . "</span>] ";
        $new_label .= " [STATUS: <span style='font-weight: 600;'>'" . $row['status'] . "'</span>] ";
        
        return $new_label;
    }



    // Get Isotope Products as Checkbox array
    public function getIsotopeProducts(DataContainer $dc) { 
        
        $chekbox_options = array();
        
        // Loop through stored IDs, find their product, add it to the array
        $selected_products = unserialize($dc->activeRecord->generated_isotope_products);
        foreach($selected_products as $product_id) {
            $product = Product::findOneBy(['tl_iso_product.id = ?'], [$product_id]);
            if($product)
                $chekbox_options = $chekbox_options + array($product->id => $product->name);
        }
        
		return $chekbox_options;
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
		if (is_array($GLOBALS['TL_DCA']['tl_listing']['fields']['published']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_listing']['fields']['published']['save_callback'] as $callback)
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
		$this->Database->prepare("UPDATE tl_salsify_request SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")->execute($intId);
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

		$objAlias = $this->Database->prepare("SELECT id FROM tl_salsify_request WHERE id=? OR alias=?")->execute($dc->id, $varValue);

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
