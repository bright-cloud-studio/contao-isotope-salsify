<?php

/* Salsify Request - Parent to Salsify Product */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\MemberModel;

use Contao\System;

/* Table tl_price_chart */
$GLOBALS['TL_DCA']['tl_salsify_request'] = array
(
 
    // Config
    'config' => array
    (
        'dataContainer'               => DC_Table::class,
        'switchToEdit'                => false,
        'enableVersioning'            => true,
        'onload_callback' => array
		(
			array('tl_salsify_request', 'setRootType')
		),
        'ondelete_callback' => array
		(
			array('Bcs\Backend\SalsifyRequestBackend', 'onDeleteSalsifyRequest')
		),
        'sql' => array
        (
            'keys' => array
            (
                'id' 	=> 	'primary',
                'pid'   => 'index'
            )
        )
    ),
 
    // List
    'list' => array
    (
        'sorting' => array
        (
            'mode'                    => DataContainer::MODE_UNSORTED,
            'rootPaste'               => false,
            'showRootTrails'          => false,
            'icon'                    => 'pagemounts.svg',
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'fields'                  => array('id DESC'),
            'panelLayout'             => 'filter;sort,search,limit'
        ),
        'label' => array
        (
            'fields'                  => array('id', 'request_name'),
			'format'                  => 'ID: %s | %s',
			'label_callback' 		  => array('Bcs\Backend\SalsifyRequestBackend', 'generateLabel')
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
                'label'               => &$GLOBALS['TL_LANG']['tl_salsify_request']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'delete' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_salsify_request']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_salsify_request']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
 
    // Palettes
    'palettes' => array
    (
        'default' => '{salsify_request_legend}, request_name, source_folder, file_url, file_date; {isotope_details_legend}, isotope_name_key, isotope_sku_key, isotope_publish_key, isotope_category_key, website_root, isotope_grouping_key, isotope_product_type, isotope_product_type_variant; {status_legend}, status, initial_linking_completed; {generated_products_legend}, generated_isotope_products;'
    ),
 
    // Fields
    'fields' => array
    (   
        // Contao Fields
        'id' => array
        (
		    'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
        'tstamp' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['date'],
            'inputType'               => 'text',
		    'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'sorting' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        
        // Salsify Request Fields
        'request_name' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['request_name'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) default ''"
        ),
        'source_folder' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['source_folder'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) default ''"
        ),

        'file_url' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['file_url'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w100 clr'),
            'sql'                     => "varchar(255) default ''"
        ),
        'file_date' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_assignment']['file_date'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => true,
            'search'                  => true,
            'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(20) NOT NULL default ''",
        ),

        
        'isotope_name_key' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['isotope_name_key'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) default ''"
        ),
        'isotope_sku_key' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['isotope_sku_key'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) default ''"
        ),

        'isotope_publish_key' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['isotope_publish_key'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) default ''"
        ),

        
        'isotope_grouping_key' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['isotope_grouping_key'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) default ''"
        ),
        'isotope_product_type' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['isotope_product_type'],
            'inputType'               => 'select',
            'flag'                    => DataContainer::SORT_ASC,
            'default'                 => NULL,
            'eval'                    => array('mandatory'=>false, 'multiple'=>false, 'tl_class'=>'w100 clr', 'includeBlankOption'=>true, 'blankOptionLabel'=>''),
            'options_callback'	      => array('Bcs\Backend\SalsifyRequestBackend', 'getIsotopeProductTypes'),
            'sql'                     => "int(10) unsigned default NULL"
        ),
        'isotope_product_type_variant' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['isotope_product_type_variant'],
            'inputType'               => 'select',
            'flag'                    => DataContainer::SORT_ASC,
            'default'                 => NULL,
            'eval'                    => array('mandatory'=>false, 'multiple'=>false, 'tl_class'=>'w100 clr', 'includeBlankOption'=>true, 'blankOptionLabel'=>''),
            'options_callback'	      => array('Bcs\Backend\SalsifyRequestBackend', 'getIsotopeProductTypes'),
            'sql'                     => "int(10) unsigned default NULL"
        ),
        
        
        

        'isotope_category_key' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['isotope_category_key'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) default ''"
        ),
        'website_root' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['website_root'],
            'inputType'               => 'pageTree',
            'eval'                    => array('files'=>false, 'fieldType'=>'radio', 'multiple'=>true, 'tl_class'=>'w50'),
			'sql'                     => "blob NULL",
            'relation'                => array('table'=>'tl_page', 'type'=>'hasMany', 'load'=>'lazy')
        ),
        
        
        'status' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['status'],
            'inputType'               => 'select',
            'default'                 => 'awaiting_new_file',
            'options'                 => array(
                'awaiting_new_file'           => 'Awaiting New File',
                'awaiting_auto_linking'       => 'Awaiting Auto-Linking',
                'awaiting_cat_linking'        => 'Awaiting Category Linking',
                'awaiting_iso_generation'     => 'Awaiting Isotope Generation',
                'awaiting_related_linking'    => 'Awaiting Related Product Linking',
                'awaiting_initial_linking'    => 'Awaiting Initial Linking'
            ),
            'eval'                     => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                      => "varchar(30) NOT NULL default 'awaiting_new_file'"
        ),
        'initial_linking_completed' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['initial_linking_completed'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),


        'generated_isotope_products' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['generated_isotope_products'],
            'inputType'               => 'checkbox',
            'filter'                  => false,
            'search'                  => false,
            'flag'                    => DataContainer::SORT_ASC,
            'eval'                    => array('multiple'=> true, 'mandatory'=>false, 'tl_class'=>'long'),
            'options_callback'	      => array('Bcs\Backend\SalsifyRequestBackend', 'getIsotopeProducts'),
            'sql' => "blob NULL"
        )
        
    )
);


class tl_salsify_request extends Backend
{
  
	public function setRootType(DataContainer $dc)
	{
		if (Input::get('act') != 'create')
		{
			return;
		}
		if (Input::get('pid') == 0)
		{
			$GLOBALS['TL_DCA']['tl_salsify_request']['fields']['type']['default'] = 'root';
		}
		elseif (Input::get('mode') == 1)
		{
			$objPage = Database::getInstance()
				->prepare("SELECT * FROM " . $dc->table . " WHERE id=?")
				->limit(1)
				->execute(Input::get('pid'));

			if ($objPage->pid == 0)
			{
				$GLOBALS['TL_DCA']['tl_salsify_request']['fields']['type']['default'] = 'root';
			}
		}
	}

    public function addIcon($row, $label)
	{
		$sub = 0;
		$unpublished = ($row['start'] && $row['start'] > time()) || ($row['stop'] && $row['stop'] <= time());

		if ($unpublished || !$row['published'])
		{
			++$sub;
		}

		if ($row['protected'])
		{
			$sub += 2;
		}

		$image = 'articles.svg';

		if ($sub > 0)
		{
			$image = 'articles_' . $sub . '.svg';
		}

		$attributes = sprintf(
			'data-icon="%s" data-icon-disabled="%s"',
			$row['protected'] ? 'articles_2.svg' : 'articles.svg',
			$row['protected'] ? 'articles_3.svg' : 'articles_1.svg',
		);

		$href = System::getContainer()->get('router')->generate('contao_backend_preview', array('page'=>$row['pid'], 'article'=>($row['id'])));

		return '<a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['view']) . '" target="_blank">' . Image::getHtml($image, '', $attributes) . '</a> ' . $label;
	}
  
}
