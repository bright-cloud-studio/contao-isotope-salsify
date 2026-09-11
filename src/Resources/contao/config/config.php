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
$GLOBALS['BE_MOD']['salsify']['salsify_product'] = array( 'tables' => array('tl_salsify_product') );
$GLOBALS['BE_MOD']['salsify']['salsify_attribute'] = array( 'tables' => array('tl_salsify_attribute') );

/* Front End modules */
$GLOBALS['FE_MOD']['salsify']['mod_salsify_importer']         = 'Bcs\Module\ModSalsifyImporter';
$GLOBALS['FE_MOD']['salsify']['mod_salsify_status_update']    = 'Bcs\Module\ModSalsifyStatusUpdate';

/* Models */
$GLOBALS['TL_MODELS']['tl_salsify_request']         = 'Bcs\Model\SalsifyRequest';
$GLOBALS['TL_MODELS']['tl_salsify_product']         = 'Bcs\Model\SalsifyProduct';
$GLOBALS['TL_MODELS']['tl_salsify_attribute']       = 'Bcs\Model\SalsifyAttribute';

/* Notification Center - alert sent by step seven when a Salsify Request stalls */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['salsify']['salsify_stalled'] = array
(
    'recipients'           => array('admin_email'),
    'email_subject'        => array('request_id', 'request_name', 'request_status', 'request_file', 'stalled_minutes'),
    'email_text'           => array('request_id', 'request_name', 'request_status', 'request_file', 'stalled_minutes'),
    'email_html'           => array('request_id', 'request_name', 'request_status', 'request_file', 'stalled_minutes'),
    'email_sender_name'    => array('admin_email'),
    'email_sender_address' => array('admin_email'),
    'email_recipient_cc'   => array('admin_email'),
    'email_recipient_bcc'  => array('admin_email'),
    'email_replyTo'        => array('admin_email')
);
