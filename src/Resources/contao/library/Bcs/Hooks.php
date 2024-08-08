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

            $reader->read("attributes");
            $depth = $reader->depth(); // Check in a moment to break when the array is done.
            
            $reader->read(); // Step to the first element.
            do {
                echo "<pre>";
                print_r($reader->value());
                echo "</pre>";
            } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
            
            $reader->close();
            
            die();
            
        }
        
    }
}
