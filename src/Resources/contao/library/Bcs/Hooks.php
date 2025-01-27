<?php

namespace Bcs;

use ZipArchive;

use Bcs\Model\SalsifyRequest;
use Bcs\Model\SalsifyAttribute;

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
        
        if($objPageModel->id == 249)
        {

            echo "generatePage CALLED <br>";
            
        }
        
    }
}
