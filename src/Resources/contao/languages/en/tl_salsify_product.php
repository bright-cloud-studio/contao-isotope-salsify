<?php

/**
 * Bright Cloud Studio's GAI Invoices
 *
 * Copyright (C) 2022-2023 Bright Cloud Studio
 *
 * @package    bright-cloud-studio/gai-invoices
 * @link       https://www.brightcloudstudio.com/
 * @license    http://opensource.org/licenses/lgpl-3.0.html
**/

/* System Buttons */
$GLOBALS['TL_LANG']['tl_salsify_product']['new']                        = array('New Salsify Product', 'Add a new record');
$GLOBALS['TL_LANG']['tl_salsify_product']['show']                       = array('Salsify Product Details', 'Show the details of record ID %s');
$GLOBALS['TL_LANG']['tl_salsify_product']['edit']                       = array('Edit Salsify Product', 'Edit record ID %s');
$GLOBALS['TL_LANG']['tl_salsify_product']['copy']                       = array('Copy Salsify Product', 'Copy record ID %s');
$GLOBALS['TL_LANG']['tl_salsify_product']['delete']                     = array('Delete Salsify Product', 'Delete record ID %s');
$GLOBALS['TL_LANG']['tl_salsify_product']['toggle']                     = array('Toggle Salsify Product', 'Toggle record ID %s');


/* Fields */
$GLOBALS['TL_LANG']['tl_salsify_product']['salsify_product_legend']     = 'Salsify Product Details';
$GLOBALS['TL_LANG']['tl_salsify_product']['product_name']               = array('Product Name', 'The Name that the Isotope Product will use');
$GLOBALS['TL_LANG']['tl_salsify_product']['product_sku']                = array('Product SKU', 'The unique SKU that the Isotope Product will use');
$GLOBALS['TL_LANG']['tl_salsify_product']['email']                      = array('Email Address', 'The Email Address that will receive notifications in the event of an unresolvable issue');
$GLOBALS['TL_LANG']['tl_salsify_product']['variant_group']              = array('Variant Group', 'If a Saislify Attribute is selected as grouping, this will store which group this product belongs to');
$GLOBALS['TL_LANG']['tl_salsify_product']['category_page']              = array('Category Page', 'This is the page this product will live on');

$GLOBALS['TL_LANG']['tl_salsify_product']['grouping_legend']        = 'Grouping Details';
$GLOBALS['TL_LANG']['tl_salsify_product']['isotope_product_type']           = array('Isotope Product Type', 'The Isotope Product Type this will turn into');
$GLOBALS['TL_LANG']['tl_salsify_product']['isotope_product_variant_type']   = array('Isotope Product Variant Type', 'Tracks if this is a Product or a Variant Product');
$GLOBALS['TL_LANG']['tl_salsify_product']['isotope_product_type_linked']    = array('Isotope Product Type Linked', 'Tracks if we have the REQUIRED Product Type attribute applied');

$GLOBALS['TL_LANG']['tl_salsify_product']['internal_details_legend']        = 'INTERNAL DETAILS';
$GLOBALS['TL_LANG']['tl_salsify_product']['import_status']                  = array('Import Status', 'Tracks the Impot Status of this product');
$GLOBALS['TL_LANG']['tl_salsify_product']['last_update']                    = array('Last Update', 'A timestamp of when the last update was processed');

$GLOBALS['TL_LANG']['tl_salsify_product']['publish_legend']                 = 'Publish Details';
$GLOBALS['TL_LANG']['tl_salsify_product']['published']                      = array('Published', 'A Salsify Product will only generate into an Isotope Product if it is published.');
