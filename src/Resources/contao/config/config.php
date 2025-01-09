<?php
 
/**
* @copyright  Bright Cloud Studio
* @author     Bright Cloud Studio
* @package    Contao CE Recaptcha
* @license    LGPL-3.0+
* @see	       https://github.com/bright-cloud-studio/contao-popups
*/

/** Hooks */
$GLOBALS['TL_HOOKS']['generatePage'][] 		 = array('Bcs\Hooks', 'generatePage');

/* Back end modules - Work DCAs */
$GLOBALS['TL_LANG']['MOD']['salsify'][0] = "Salsify";
$GLOBALS['BE_MOD']['salsify']['salsify_request'] = array( 'tables' => array('tl_salsify_request') );
$GLOBALS['BE_MOD']['salsify']['salsify_attribute'] = array( 'tables' => array('tl_salsify_attribute') );

/* Front End modules */
$GLOBALS['FE_MOD']['salsify']['mod_salsify_importer']         = 'Bcs\Module\ModSalsifyImporter';

/* Models */
$GLOBALS['TL_MODELS']['tl_salsify_request']         = 'Bcs\Model\SalsifyRequest';
$GLOBALS['TL_MODELS']['tl_salsify_attribute']         = 'Bcs\Model\SalsifyAttribute';
