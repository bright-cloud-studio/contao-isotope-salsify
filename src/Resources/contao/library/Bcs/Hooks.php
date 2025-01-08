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

use Bcs\Model\SalsifyRequest;
use Bcs\Model\SalsifyAttribute;



class Hooks
{
    public function generatePage(&$objPageModel, $objLayout, &$objPage)
    {

        // TEMPORARY - IF OUR 'HIDDEN' TEST PAGE
        if($objPageModel->id == 58)
        {
            
            // Open and process file
            $reader = new JsonReader();
            $reader->open("../salsify/Salsify_product-feed_2025_01_02_18_31_17_UTC.json");
            $depth = $reader->depth();
            $reader->read();
            
            // Process loaded XML data
            do
            {
            
                // Load the first array, which is the overall wrapper of arrays
                $array_parent = $reader->value();
                
                // Loop through children arrays, these are what store the actual values here
                foreach($array_parent as $array_child) {

                    // Create a Salsify Request to hold everything
                    $request = new SalsifyRequest();
                    
                    foreach($array_child as $key => $val) {
                        
                        echo "<strong>" . $key . "</strong> - " . $val[0];
                        echo "<br>";

                        // Create Salsify Attributes for each loop
                        $attr = new SalsifyAttribute();
                        $attr->pid = $request->id;
                        $attr->attribute_key = $key;
                        $attr->attribute_value = $val[0];
                        $attr->save();
                        
                    }
                    $request->save();
                    echo "<hr>";
                }

            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.


            
            $reader->close();
            // TEMPORARY: Die so we can see the page with just our data on it
            die();
        }
        
    }
}
