<?php

namespace Bcs;

use pcrov\JsonReader\JsonReader;
use Isotope\Model\Attribute;
use Isotope\Model\AttributeOption;

use Contao\Database;

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
            //echo "<pre>";
            //print_r($objLayout);
            //echo "</pre>";
            //die();

            $reader = new JsonReader();
            $reader->open("../salsify/product-feed-paper_2024_04_12_16_45_01_UTC.json");




            
            /************************/
            /* Process "Attributes" */
            /************************/
            
            $reader->read("attributes");
            $depth = $reader->depth(); // Check in a moment to break when the array is done.
            
            $reader->read(); // Step to the first element.
            do {
                $attr = $reader->value();


                // First, see if this attribute exists
                $existing_attr = AttributeOption::findOneBy(['tl_iso_attribute_option.label=?'],[$attr["salsify:id"]])->id;

                if(!$existing_attr)
                {
                    echo "DOESNT EXIST: " . $attr["salsify:id"] . "<br>";

                    $new_attr = new AttributeOption();
                    $new_attr->label = $attr["salsify:id"];
                    $new_attr->tstamp = time();
                    $new_attr->published = 1;
                    $new_attr->save();
                    
                    
                } else {
                    echo "DOES EXIST: " . $attr["salsify:id"] . "<br>";
                }

            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.







            
            $reader->close();
            
            die();
            
        }
        
    }
}
