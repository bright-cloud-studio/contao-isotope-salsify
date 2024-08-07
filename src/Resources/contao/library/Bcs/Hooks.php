<?php

namespace Bcs;

class Hooks
{
    public function generatePage(&$objPageModel, $objLayout, &$objPage)
    {
        echo "HOOK HIT";
        die();
    }
}
