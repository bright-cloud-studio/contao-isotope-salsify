<?php

namespace Bcs;

use ZipArchive;

use pcrov\JsonReader\JsonReader; // Json streaming library
use Contao\Database;
use Isotope\Model\Attribute;
use Isotope\Model\Attribute\TextField;
use Isotope\Model\AttributeOption;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\Model\Product;

class Hooks
{
    public function generatePage(&$objPageModel, $objLayout, &$objPage)
    {

        // If this is our "Contact - Success" test page
        if($objPageModel->id == 58)
        {
            // Manually open our 'test' file
            $reader = new JsonReader();
            $reader->open("../salsify/Salsify_product-feed_2025_01_02_18_31_17_UTC.json");



            /************************/
            /* Process "Attributes" */
            /************************/
            // Store the initial depth so we know when to end
            $depth = $reader->depth();
            // Step to the first element within "attributes"
            $reader->read();

            // Do while there is data to be read
            do
            {

                // This is our overall displays
                $array_parent = $reader->value();
                
                foreach($array_parent as $array_child) {
                    
                    foreach($array_child as $key => $val) {
                        echo "KEY: " . $key . "<br>";
                        echo "VAL: " . $val[0];
                        echo "<br>";
                    }
                    
                    echo "<hr>";
                    
                }


            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
            

            /******************************/
            /* Process "Attribute Values" */
            /******************************/
            

            /****************************/
            /* Process "Digital Assets" */
            /****************************/

            
            $reader->close();
            
            die();
            
        }
        
    }
}
