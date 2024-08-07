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

            

            $reader = new JsonReader();
            $reader->open("../salsify/product-feed.json");
            
            while ($reader->read("salsify:id")) {
                echo $reader->value(), "<br>";
            }
            
            $reader->close();
            
            die();

            
        }
        
    }
}
