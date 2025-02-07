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
        'onsubmit_callback' => array
		(
			array('Bcs\Backend\SalsifyAttributeBackend', 'linkSimilarAttributes')
		),
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
            'mode'                    => DataContainer::MODE_TREE_EXTENDED,
            'headerFields'            => array('product_sku'),
            'rootPaste'               => false,
            'icon'                    => 'pagemounts.svg',
            'defaultSearchField'      => 'attribute_key',
            'flag'                    => DataContainer::SORT_ASC,
            'fields'                  => array('attribute_key ASC'),
            'panelLayout'             => 'filter;sort,search,limit'
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
            'show' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
 
    // Palettes
    'palettes' => array
    (
        'default'                     => '{salsify_attribute_legend}, attribute_key, attribute_value; {options_legend}, linked_isotope_attribute, linked_isotope_attribute_option, isotope_product_type, is_sku, is_name ,is_grouping; {options_legend}, category_parent_page, category_reader_page, category_page;{error_log_legend}, error_log;'
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
		    'foreignKey'              => 'tl_salsify_product.id',
		    'sql'                     => "int(10) unsigned NOT NULL default 0",
		    'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
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
            'eval'                    => array('mandatory'=>false, 'multiple'=>false, 'tl_class'=>'w100', 'includeBlankOption'=>true, 'blankOptionLabel'=>''),
            'options_callback'	      => array('Bcs\Backend\SalsifyAttributeBackend', 'getIsotopeAttributeOptions'),
            'sql'                     => "int(10) unsigned default NULL"
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
        // Salsify Attribute Fields
        'is_sku' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['is_sku'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        // Salsify Attribute Fields
        'is_name' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['is_name'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'is_grouping' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_attribute']['is_grouping'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
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
            'inputType'               => 'pageTree',
            'eval'                    => array('files'=>false, 'fieldType'=>'radio', 'multiple'=>true, 'tl_class'=>'w50'),
			'sql'                     => "blob NULL",
            'relation'                => array('table'=>'tl_page', 'type'=>'hasMany', 'load'=>'lazy')
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
    
}
