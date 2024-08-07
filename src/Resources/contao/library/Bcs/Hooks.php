<?php

namespace Bcs;

class Hooks
{
    public function generatePage(&$objPageModel, $objLayout, &$objPage)
    {
        echo "<pre>";
        print_r($objPage);
        echo "</pre>";
        die();
    }
}
