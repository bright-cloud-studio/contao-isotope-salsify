<?php

/**
 * @copyright  Bright Cloud Studio
 * @author     Bright Cloud Studio
 * @package    Contao Isotope Salsify
 * @license    LGPL-3.0+
 * @see        https://github.com/bright-cloud-studio/contao-isotope-salsify
 */

/* Notification Types */
$GLOBALS['TL_LANG']['tl_nc_notification']['type']['salsify']            = 'Salsify';
$GLOBALS['TL_LANG']['tl_nc_notification']['type']['salsify_stalled']    = array('Salsify Request stalled', 'Sent when a Salsify Request stays on the same step for too long, usually because a step failed partway through and keeps retrying.');
$GLOBALS['TL_LANG']['tl_nc_notification']['type']['salsify_completed']  = array('Salsify Request completed', 'Sent when a Salsify Request finishes importing a file, with the file name and product counts.');
