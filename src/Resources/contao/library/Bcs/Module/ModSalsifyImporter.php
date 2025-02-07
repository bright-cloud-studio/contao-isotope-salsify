<?php

namespace Bcs\Module;

use Bcs\Model\SalsifyAttribute;
use Bcs\Model\SalsifyProduct;
use Bcs\Model\SalsifyRequest;

use pcrov\JsonReader\JsonReader;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\Database;
use Contao\System;
use Contao\FrontendUser;

use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\DatabaseUpdater;

use Isotope\Model\Attribute;
use Isotope\Model\AttributeOption;
use Isotope\Model\Attribute\TextField;
use Isotope\Model\Product;

use Isotope\Backend\Attribute\DatabaseUpdate;

class ModSalsifyImporter extends \Contao\Module
{

    /* Default Template */
    protected $strTemplate = 'mod_salsify_importer';

    // Stores our messages, later displayed in the module's template
    protected static $log = array();

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
        $reader->open("../files/salsify/salsify_product_feed_2025_02_07_18_04_34_532_UTC.json");
        $depth = $reader->depth();
        $reader->read();

        $do_loop = 0;
        // Process loaded XML data
        do
        {
            $do_loop++;
        
            // Load the first array, which is the overall wrapper of arrays
            $array_parent = $reader->value();

            $prod_count = 0;
            // Loop through children arrays, these are what store the actual values here
            foreach($array_parent as $array_child) {
                $prod_count++;
                
                // Create a Salsify Product to hold our Salsify Attributes
                $salsify_product = new SalsifyProduct();
                $salsify_product->tstamp = time();
                $salsify_product->product_sku = $do_loop . '_' . $prod_count;
                $salsify_product->name = 'product_' . $do_loop . '_' . $prod_count;
                $salsify_product->save();

                $log[$salsify_product->id]['id'] = $salsify_product->id;
                
                $attributes = array();
                
                $prod_values = array();
                
                
                foreach($array_child as $key => $val) {
                    
                    $prod_values[$key] = $val[0];
                    
                    
                    $salsify_attribute = new SalsifyAttribute();
                    $salsify_attribute->pid = $salsify_product->id;
                    $salsify_attribute->attribute_key = $key;
                    $salsify_attribute->attribute_value = $val[0];
                    $salsify_attribute->isotope_linked_attribute = null;
                    $salsify_attribute->tstamp = time();
                    $salsify_attribute->save();
                    
                    $attributes[$salsify_attribute->id]['key'] = $key;
                    $attributes[$salsify_attribute->id]['value'] = $val[0];
                    $log[$salsify_product->id]['attributes'] = $attributes;
                    
                }
                
                
                /*
                // Fill in the rest of the product's information then create the product
                $prod_values['tstamp'] = time();
                $prod_values['dateAdded'] = time();
                $prod_values['type'] = 5;
                $prod_values['orderPages'] = 'a:1:{i:0;s:3:"109";}';
                $prod_values['alias'] = $prod_values['item_number'];
                $prod_values['name'] = $prod_values['specific_product_title'];
                $prod_values['sku'] = $prod_values['item_number'];
                $prod_values['description'] = $prod_values['full_description'];
                $prod_values['published'] = 1;
                $prod_values['upc'] = $prod_values['package_upc'];
                $prod_values_result = \Database::getInstance()->prepare("INSERT INTO tl_iso_product %s")
                                                 ->set($prod_values)
                                                 ->execute();
                                                 
                 // Second, create entry in the 'tl_product_price' table                    
                $price = array();
                $price['pid'] = $prod_values_result->insertId;
                $price['tstamp'] = time();
                $price['tax_class'] = 1;
                $price['config_id'] = 0;
                $price['member_group'] = 0;
                $priceResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_price %s")
                                 ->set($price)
                                 ->execute();                                           
                                                         
                
                                                         
                // First, create entry in the 'tl_product_pricetier" table
                $priceTier = array();
                $priceTier['pid'] = $priceResult->insertId;
                $priceTier['tstamp'] = time();
                $priceTier['min'] = 1;
                $priceTier['price'] = '1.00';
                $priceTierResult = \Database::getInstance()->prepare("INSERT INTO tl_iso_product_pricetier %s")
                                 ->set($priceTier)
                                 ->execute();
                
                */

            }

        } while ($reader->next() && $reader->depth() > $depth); // Read each sibling.
        
        $reader->close();

        $this->Template->salsify_log = $log;
        
    }

  
}
