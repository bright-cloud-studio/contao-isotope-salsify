<?php

/* Salsify Attribute - Child to Salsify Product */

use Contao\MemberModel;

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

/* Table tl_price_chart */
$GLOBALS['TL_DCA']['tl_salsify_attribute'] = array
(
 
    // Config
    'config' => array
    (
        'dataContainer'               => DC_Table::class,
        'ptable'                      => 'tl_salsify_product',
        'switchToEdit'                => false,
        'enableVersioning'            => true,
        'markAsCopy'                  => 'title',
        'sql' => array
        (
            'keys' => array
            (
                'id' 	=> 	'primary',
                'pid'   =>  'index'
            )
        )
    ),
 
    // List
    'list' => array
    (
        'sorting' => array
        (
            'mode'                    => DataContainer::MODE_SORTED,
            'fields'                  => array('pid'),
            'panelLayout'             => 'filter;sort,search,limit',
            'defaultSearchField'      => 'attribute_key',
            'headerFields'            => array('id, product_sku')
            //'child_record_callback'   => array('tl_salsify_attribute', 'listSalsifyAttribute'),
        ),
        'label' => array
        (
            'fields'                  => array('attribute_key'),
			'format'                  => '%s',
            'label_callback' 		  => array('Bcs\Backend\SalsifyAttributeBackend', 'generateStatusLabel')
        ),
        'global_operations' => array
        (
            'all' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations' => array
        (
            'edit' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('Bcs\Backend\SalsifyAttributeBackend', 'toggleIcon')
			),
            'show' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),

    // Edit
    'edit' => array
    (
        'buttons_callback' => array
        (
            array('Bcs\Backend\SalsifyAttributeBackend', 'addLinkMatchingAttributes')
        )
    ),
 
    // Palettes
    'palettes' => array
    (
        'default'                     => '{salsify_attribute_legend}, attribute_key, attribute_value, request; {options_legend}, linked_isotope_attribute, linked_isotope_attribute_option, attribute_option_sorting;{grouping_legend}, is_grouping, isotope_product_type, isotope_product_type_variant;{options_legend}, is_cat, category_page; {controls_published_legend}, controls_published; {status_legend}, status; {publish_legend},published; {error_log_legend}, error_log;'
    ),
 
    // Fields
    'fields' => array
    (
        
        // Contao Fields
        'id' => array
        (
		    'sql'                   => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array
        (
            'foreignKey'              => "tl_salsify_product.CONCAT(product_sku, ' - ', isotope_product_variant_type)",
		    'sql'                     => "int(10) unsigned NOT NULL default 0",
		    'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
        ),

        // TESTING PULLING IN SALSIFY REQUEST
        'request' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['request'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => true,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w100', 'rte'=>'tinyMCE'),
            'sql'                     => "text default ''",
            'relation'              => array('type'=>'hasOne', 'load'=>'lazy', 'table'=>'tl_salsify_product', 'field'=>'pid'),
        ),
        
        'tstamp' => array
        (
		    'sql'                     	=> "int(10) unsigned NOT NULL default '0'"
        ),
        'sorting' => array
        (
            'sql'                    	=> "int(10) unsigned NOT NULL default '0'"
        ),
        
        // Salsify Attribute Fields
        'attribute_key' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['attribute_key'],
            'inputType'               => 'text',
            'default'                 => '',
            'search'                  => false,
            'filter'                  => false,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w100'),
            'sql'                     => "varchar(255) default ''"
        ),
        'attribute_value' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['attribute_value'],
            'inputType'               => 'textarea',
            'default'                 => '',
            'search'                  => false,
            'filter'                  => false,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w100'),
            'sql'                     => "text default ''"
        ),


        
        // Salsify Attribute Fields
        'linked_isotope_attribute' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['linked_isotope_attribute'],
            'inputType'               => 'select',
            'flag'                    => DataContainer::SORT_ASC,
            'default'                 => NULL,
            'eval'                    => array('mandatory'=>false, 'multiple'=>false, 'tl_class'=>'w100', 'includeBlankOption'=>true, 'blankOptionLabel'=>''),
            'options_callback'	      => array('Bcs\Backend\SalsifyAttributeBackend', 'getIsotopeAttributes'),
            'sql'                     => "int(10) unsigned default NULL"
        ),
        'linked_isotope_attribute_option' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['linked_isotope_attribute_option'],
            'inputType'               => 'select',
            'flag'                    => DataContainer::SORT_ASC,
            'default'                 => NULL,
            'eval'                    => array('mandatory'=>false, 'multiple'=>true, 'tl_class'=>'w100', 'includeBlankOption'=>true, 'blankOptionLabel'=>''),
            'options_callback'	      => array('Bcs\Backend\SalsifyAttributeBackend', 'getIsotopeAttributeOptions'),
            'sql'                     => 'blob NULL'
        ),
        'attribute_option_sorting' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['attribute_option_sorting'],
            'inputType'               => 'select',
            'default'                 => NULL,
            'filter'                  => true,
            'search'                  => true,
            'options'                  => array('sort_alphabetical' => 'Sort Alphabetical', 'sort_numerical' => 'Sort Numerical'),
            'eval'                     => array('mandatory'=>false, 'multiple'=>false, 'tl_class'=>'w50', 'includeBlankOption'=>true, 'blankOptionLabel'=>''),
            'sql'                      => "varchar(20) default NULL"
        ),


        
        'is_grouping' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['is_grouping'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'isotope_product_type' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['isotope_product_type'],
            'inputType'               => 'select',
            'flag'                    => DataContainer::SORT_ASC,
            'default'                 => NULL,
            'eval'                    => array('mandatory'=>false, 'multiple'=>false, 'tl_class'=>'w100 clr', 'includeBlankOption'=>true, 'blankOptionLabel'=>''),
            'options_callback'	      => array('Bcs\Backend\SalsifyAttributeBackend', 'getIsotopeProductTypes'),
            'sql'                     => "int(10) unsigned default NULL"
        ),
        'isotope_product_type_variant' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['isotope_product_type_variant'],
            'inputType'               => 'select',
            'flag'                    => DataContainer::SORT_ASC,
            'default'                 => NULL,
            'eval'                    => array('mandatory'=>false, 'multiple'=>false, 'tl_class'=>'w100 clr', 'includeBlankOption'=>true, 'blankOptionLabel'=>''),
            'options_callback'	      => array('Bcs\Backend\SalsifyAttributeBackend', 'getIsotopeProductTypes'),
            'sql'                     => "int(10) unsigned default NULL"
        ),

        
        // Isotope Configuration - Use as SKU
        'is_sku' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['is_sku'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        // Isotope Configuration - Use as Name
        'is_name' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['is_name'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),


        // Status
        'is_cat' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['is_cat'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'status' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['status'],
            'inputType'               => 'select',
            'default'                 => 'fail',
            'filter'                  => true,
            'search'                  => true,
            'options'                  => array('fail' => 'Fail', 'pass' => 'Pass'),
            'eval'                     => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                      => "varchar(15) NOT NULL default 'fail'"
        ),
        


        // PAGE GENERATION STUFFS
        'category_parent_page' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['category_parent_page'],
            'inputType'               => 'pageTree',
            'eval'                    => array('files'=>false, 'fieldType'=>'radio', 'multiple'=>true, 'tl_class'=>'w50'),
			'sql'                     => "blob NULL",
            'relation'                => array('table'=>'tl_page', 'type'=>'hasMany', 'load'=>'lazy')
        ),
        'category_reader_page' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['category_reader_page'],
            'inputType'               => 'pageTree',
            'eval'                    => array('files'=>false, 'fieldType'=>'radio', 'multiple'=>true, 'tl_class'=>'clr w50'),
			'sql'                     => "blob NULL",
            'relation'                => array('table'=>'tl_page', 'type'=>'hasMany', 'load'=>'lazy')
        ),
        'category_page' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['category_page'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) default ''"
        ),

        'controls_published' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['controls_publoshed'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),


        'published' => array
        (
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['published'],
            'inputType'               => 'checkbox',
            'default'                 => '1',
            'eval'                    => array('submitOnChange'=>false, 'doNotCopy'=>true),
            'sql'                     => "char(1) NOT NULL default '1'"
        ),


        // Salsify Attribute Fields
        'error_log' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['error_log'],
            'inputType'               => 'text',
            'default'                 => '',
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w100', 'rte'=>'tinyMCE'),
            'sql'                     => "text default ''"
        ),
        
    )
);



class tl_salsify_attribute extends Backend
{
    public function listSalsifyAttribute($row)
	{
		return '<div class="tl_content_left">' . $row['attribute_key'] . ' <span class="label-info">ASDF</span></div>';
    }
}
