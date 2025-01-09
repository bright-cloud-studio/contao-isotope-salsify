<?php

namespace Bcs\Module;

use Bcs\Model\SalsifyRequest;
use Bcs\Model\SalsifyAttribute;

use pcrov\JsonReader\JsonReader;

use Contao\BackendTemplate;
use Contao\System;
use Contao\FrontendUser;


class ModSalsifyImporter extends \Contao\Module
{

    /* Default Template */
    protected $strTemplate = 'mod_salsify_importer';

    /* Construct function */
    public function __construct($objModule, $strColumn='main')
    {
        parent::__construct($objModule, $strColumn);
    }

    /* Generate function */
    public function generate()
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
 
            $objTemplate->wildcard = '### ' . mb_strtoupper($GLOBALS['TL_LANG']['FMD']['salsify_importer'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&table=tl_module&act=edit&id=' . $this->id;
 
            return $objTemplate->parse();
        }
 
        return parent::generate();
    }


    protected function compile()
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
                $salsify_request = new SalsifyRequest();
                $salsify_request->tstamp = time();
                $salsify_request->product_sku = '123';
                $salsify_request->save();
                
                foreach($array_child as $key => $val) {
                    
                    echo "<strong>" . $key . "</strong> - " . $val[0];
                    echo "<br>";
                    
                    $salsify_attribute = new SalsifyAttribute();
                    $salsify_attribute->pid = $salsify_request->id;
                    $salsify_attribute->attribute_key = $key;
                    $salsify_attribute->attribute_value = $val[0];
                    $salsify_attribute->tstamp = time();
                    $salsify_attribute->save();
                    
                }
                echo "<hr>";
            }

        } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
        
        $reader->close();
        
    }
  
}
