<?php

/* System Buttons */
$GLOBALS['TL_LANG']['tl_salsify_request']['new']                          = array('New Salsify Request', 'Add a new record');
$GLOBALS['TL_LANG']['tl_salsify_request']['show']                         = array('Salsify Request Details', 'Show the details of record ID %s');
$GLOBALS['TL_LANG']['tl_salsify_request']['edit']                         = array('Edit Salsify Request', 'Edit record ID %s');
$GLOBALS['TL_LANG']['tl_salsify_request']['copy']                         = array('Copy Salsify Request', 'Copy record ID %s');
$GLOBALS['TL_LANG']['tl_salsify_request']['delete']                       = array('Delete Salsify Request', 'Delete record ID %s');
$GLOBALS['TL_LANG']['tl_salsify_request']['toggle']                       = array('Toggle Salsify Request', 'Toggle record ID %s');

/* Fields */
$GLOBALS['TL_LANG']['tl_salsify_request']['salsify_request_legend']       = 'Salsify Request Details';
$GLOBALS['TL_LANG']['tl_salsify_request']['request_name']                 = array('Request Name', 'An internal-only name used to identify in the Backend');
$GLOBALS['TL_LANG']['tl_salsify_request']['source_folder']                = array('Source Folder', 'Starting in \'public_html/files\', enter the folder location for where to poll for updates.');
$GLOBALS['TL_LANG']['tl_salsify_request']['isotope_name_key']             = array('Isotope Name Key', 'Enter the \'key\' for the Attribute we will use as our Isotope Product Name.');
$GLOBALS['TL_LANG']['tl_salsify_request']['isotope_sku_key']              = array('Isotope SKU Key', 'Enter the \'key\' for the Attribute we will use as our Isotope Product SKU.');

$GLOBALS['TL_LANG']['tl_salsify_request']['latest_file_legend']           = 'Latest File Details';
$GLOBALS['TL_LANG']['tl_salsify_request']['file_url']                     = array('File URL', 'The URL of the latest file we\'ve found.');
$GLOBALS['TL_LANG']['tl_salsify_request']['file_date']                    = array('File Date', 'The \'Last Modified\' date of the latest file we\'ve found');

$GLOBALS['TL_LANG']['tl_salsify_request']['website_root_legend']          = 'Website Root Details';
$GLOBALS['TL_LANG']['tl_salsify_request']['website_root']                 = array('Website Root', 'Select the Website Root, in which we will search for Product Pages.');

$GLOBALS['TL_LANG']['tl_salsify_request']['customization_legend']         = 'Customization Options';
$GLOBALS['TL_LANG']['tl_salsify_request']['autolink_isotope_attributes']  = array('Autolink Isotope Attributes', 'By checking this box, when generating Salsify Attributes an attempt will be made to autolink these to Isotope Attributes where the field name matches the Attribute Key');

$GLOBALS['TL_LANG']['tl_salsify_request']['internal_information']         = 'Internal Information';
$GLOBALS['TL_LANG']['tl_salsify_request']['flag_update']                  = array('Flag - Update', 'When a new file is found, this flag will be checked and it will process during the next update loop.');

$GLOBALS['TL_LANG']['tl_salsify_request']['status_legend']                = 'Status Details';
$GLOBALS['TL_LANG']['tl_salsify_request']['status']                       = array('Status', 'Tracks the status of this Salsify Request, tracking which step of the process we are currently in');
$GLOBALS['TL_LANG']['tl_salsify_request']['initial_linking_completed']    = array('Initial Linking Completed', 'When this is checked, we will enter the \'stay alive\' loop where we look for new json files and process them as they are detected');
