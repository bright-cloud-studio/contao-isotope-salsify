<?php

namespace Bcs;

use ZipArchive;

use Bcs\Model\SalsifyAttribute;
use Bcs\Model\SalsifyProduct;
use Bcs\Model\SalsifyRequest;

use pcrov\JsonReader\JsonReader;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\Database;
use Contao\System;
use Contao\FrontendUser;

use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\DatabaseUpdater;

use Isotope\Model\Attribute;
use Isotope\Model\AttributeOption;
use Isotope\Model\Attribute\TextField;
use Isotope\Model\Product;

use Isotope\Backend\Attribute\DatabaseUpdate;


class Hooks
{
    public function generatePage(&$objPageModel, $objLayout, &$objPage)
    {
        
        if($objPageModel->id == 249)
        {
            // Open and process file
            $reader = new JsonReader();
            $reader->open("../files/salsify/salsify_product_feed_2025_03_10_16_47_07_950_UTC.json");
            $depth = $reader->depth();
            $reader->read();
            
            $do_loop = 0;
            // Process loaded XML data
            do
            {
            	$do_loop++;
            
            	// Load the first array, which is the overall wrapper of arrays
            	$array_parent = $reader->value();
            
            	$prod_count = 0;
            	// Loop through children arrays, these are what store the actual values here
            	foreach($array_parent as $array_child) {
            		$prod_count++;
            		
            		// Create a Salsify Product to hold our Salsify Attributes
            		$salsify_product = new SalsifyProduct();
            		$salsify_product->tstamp = time();
            		$salsify_product->product_sku = $do_loop . '_' . $prod_count;
            		$salsify_product->name = 'product_' . $do_loop . '_' . $prod_count;
            		$salsify_product->save();

                    $attributes = array();
                
                    $prod_values = array();



                    foreach($array_child as $key => $val) {
                        
                        $prod_values[$key] = $val[0];
                        
                        
                        $salsify_attribute = new SalsifyAttribute();
                        $salsify_attribute->pid = $salsify_product->id;
                        $salsify_attribute->attribute_key = $key;
                        $salsify_attribute->attribute_value = $val[0];
                        $salsify_attribute->isotope_linked_attribute = null;
                        $salsify_attribute->tstamp = time();
                        $salsify_attribute->save();
                        
                        $attributes[$salsify_attribute->id]['key'] = $key;
                        $attributes[$salsify_attribute->id]['value'] = $val[0];
                        $log[$salsify_product->id]['attributes'] = $attributes;
                        
                    }

            	}
            
            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
            
            $reader->close();

        }
        
        
    }
}
