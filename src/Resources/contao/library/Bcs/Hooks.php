<?php

namespace Bcs;

class Hooks
{
    public function generatePage(&$objPageModel, $objLayout, &$objPage)
    {
        
        if($objPageModel->id == 58)
        {
            echo "<pre>";
            print_r($objLayout);
            echo "</pre>";
            die();
        }
        
    }
}
