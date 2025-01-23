<?php

namespace Bcs\Backend;

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
        
    }

    
    // Display error until all 'flags' are 
    public function generateStatusLabel($row, $label, $dc, $args)
    {
        return $label;
    }

    // Build an array with the KEY being the ID of the Isotope Attribute and the VALUE is the text-readable name
    public function getIsotopeAttributes()
    {

        $options = array();
        
        $attributes = Attribute::findAll();
        while($attributes->next()) {
            $attr = $attributes->row();

            $options[] = [$attr['id'] => $attr['name']];
        }
        
        return $options;
        
    }

}
