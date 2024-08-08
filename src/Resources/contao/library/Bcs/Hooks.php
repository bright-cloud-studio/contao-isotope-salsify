<?php

namespace Bcs;

use pcrov\JsonReader\JsonReader;
use Isotope\Model\Attribute;
use Isotope\Model\AttributeOption;

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
                echo "<pre>";
                $attr = $reader->value();


                // First, see if this attribute exists
                $existing_attr = AttributeOption::findOneBy(['tl_iso_attribute_option.label=?'],[$data["id"]])->id;

                if(!$existing_attr)
                {
                    echo "DOESNT EXIST: " . $data["id"] . "<br>";

                    $attr = new AttributeOption();
                    $attr->label = $data["id"];
                    $attr->tstamp = time();
                    $attr->published = 1;
                    $attr->save();
                    
                    
                } else {
                    echo "DOES EXIST: " . $data["id"] . "<br>";
                }
                // If not, create it


                // If so, update it
                

                
                echo "</pre>";
            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.







            
            $reader->close();
            
            die();
            
        }
        
    }
}
