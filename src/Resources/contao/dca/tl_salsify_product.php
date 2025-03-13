<?php

/* Salsify Product - Parent to Salsify Attribute */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\MemberModel;

use Contao\System;

/* Table tl_price_chart */
$GLOBALS['TL_DCA']['tl_salsify_product'] = array
(
 
    // Config
    'config' => array
    (
        'dataContainer'               => DC_Table::class,
        'ptable'                      => 'tl_salsify_request',
        'ctable'                      => array('tl_salsify_attribute'),
        'switchToEdit'                => false,
        'enableVersioning'            => true,
        'onload_callback' => array
		(
			array('tl_salsify_product', 'setRootType')
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
            'mode'                    => DataContainer::MODE_SORTED,
            'rootPaste'               => false,
            'showRootTrails'          => false,
            'icon'                    => 'pagemounts.svg',
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'fields'                  => array('id ASC'),
            'panelLayout'             => 'filter;sort,search,limit'
        ),
        'label' => array
        (
            'fields'                  => array('product_name', 'product_sku'),
			'format'                  => 'NAME: %s | SKU: %s',
			'label_callback'          => array('tl_salsify_product', 'addIcon')
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
                'label'               => &$GLOBALS['TL_LANG']['tl_salsify_product']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'show' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_salsify_product']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            ),
            'salsify_attribute' => array
            (
                'href'                => 'do=salsify_attribute',
                'icon'                => 'articles.svg'
            )
        )
    ),
 
    // Palettes
    'palettes' => array
    (
        'default'                     => '{salsify_product_legend}, product_name, product_sku, email, category_page;{grouping_legend}, variant_group, isotope_product_variant_type, isotope_product_type;{internal_details_legend}, isotope_product_type_linked, import_status, last_update;'
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
			'foreignKey'              => 'tl_salsify_request.id',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
        'tstamp' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['date'],
            'inputType'               => 'text',
		    'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'sorting' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        
        // Salsify Product Fields
        'product_name' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['product_name'],
            'inputType'               => 'text',
            'default'                 => '',
            'search'                  => false,
            'filter'                  => false,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'product_sku' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['product_sku'],
            'inputType'               => 'text',
            'default'                 => '',
            'search'                  => false,
            'filter'                  => false,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'isotope_product_type' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['isotope_product_type'],
            'inputType'               => 'select',
            'flag'                    => DataContainer::SORT_ASC,
            'default'                 => NULL,
            'eval'                    => array('mandatory'=>false, 'multiple'=>false, 'tl_class'=>'w50', 'includeBlankOption'=>true, 'blankOptionLabel'=>''),
            'options_callback'	      => array('Bcs\Backend\SalsifyProductBackend', 'getIsotopeProductTypes'),
            'sql'                     => "int(10) unsigned default NULL"
        ),
        'email' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['email'],
            'inputType'               => 'text',
            'default'                 => '',
            'search'                  => false,
            'filter'                  => false,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'variant_group' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['variant_group'],
            'inputType'               => 'text',
            'default'                 => '',
            'search'                  => true,
            'filter'                  => true,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'category_page' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['category_page'],
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) default ''"
        ),
        'import_status' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['import_status'],
            'inputType'               => 'select',
            'default'                 => 'outstanding',
            'filter'                  => true,
            'search'                  => true,
            'options'                  => array('incomplete' => 'Incompleted', 'completed' => 'Completed'),
            'eval'                     => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                      => "varchar(15) NOT NULL default ''"
        ),
        'isotope_product_type_linked' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['isotope_product_type_linked'],
            'inputType'               => 'select',
            'default'                 => 'unlinked',
            'filter'                  => true,
            'search'                  => true,
            'options'                  => array('unlinked' => 'Unlinked', 'linked' => 'Linked'),
            'eval'                     => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                      => "varchar(15) NOT NULL default ''"
        ),
        'isotope_product_variant_type' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['isotope_product_variant_type'],
            'inputType'               => 'select',
            'default'                 => 'single',
            'filter'                  => true,
            'search'                  => true,
            'options'                  => array('single' => 'Single Product', 'variant' => 'Variant Product'),
            'eval'                     => array('mandatory'=>true, 'multiple'=>false, 'tl_class'=>'w50'),
            'sql'                      => "varchar(15) NOT NULL default ''"
        ),
        'last_update' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_product']['last_update'],
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(20) NOT NULL default ''",
            'default'                 => date("m/d/y")
        ),
        
        
    )
);


class tl_salsify_product extends Backend
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
