<?php

namespace Bcs\Module;

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
            
        // Include Datatables JS library and CSS stylesheets
        //$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/bcspaymentdashboard/js/datatables.min.js';
        //$GLOBALS['TL_CSS'][]        = 'bundles/bcspaymentdashboard/css/datatables.min.css';
      
    }
  
}
