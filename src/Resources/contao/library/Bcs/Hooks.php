<?php

namespace Bcs;

use pcrov\JsonReader\JsonReader;

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


            // Steps
            // 1 - Unzip assets
            // parse 'manifest' for assets


            

            $reader = new JsonReader();
            $reader->open("../salsify/product-feed.json");

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
