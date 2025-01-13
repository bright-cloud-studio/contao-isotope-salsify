<?php

namespace Bcs\Module;

use Bcs\Model\SalsifyAttribute;
use Bcs\Model\SalsifyProduct;
use Bcs\Model\SalsifyRequest;

use pcrov\JsonReader\JsonReader;

use Contao\BackendTemplate;
use Contao\System;
use Contao\FrontendUser;


class ModSalsifyStatusUpdate extends \Contao\Module
{

    /* Default Template */
    protected $strTemplate = 'mod_salsify_status_update';

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

        $postData = file_get_contents('php://input');
        $salsify_json = json_decode($postData, true);
        
        // Write to file so we know its working
        $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . '/../salsify_logs/salsify_status_update_'.strtolower(date('m_d_y_H:m:s')).".txt", "w") or die("Unable to open file!");
        //fwrite($myfile, $publicationNotification['product_feed_export_url'] . "\n");
        
        foreach($salsify_json as $key => $salsify) {
            fwrite($myfile, "Key: " . $key . "  | Value: " . $salsify . "\n");
        }
        
        // were done logging, close the file we just created
        fclose($myfile);
      
    }
  
}
