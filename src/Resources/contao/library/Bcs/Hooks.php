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

            echo "generatePage CALLED <br>";


            // Open and process file
            $reader = new JsonReader();
            $reader->open("../files/salsify/salsify_product_feed_2025_01_22_18_15_52_428_UTC.json");
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
            
            	}
            
            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
            
            $reader->close();




            
            
            
        }
        
    }
}
