<?php

/* Isotope Salsify - Parent to Salsify Attribute */

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
        'ctable'                      => array('tl_salsify_attribute'),
        'switchToEdit'                => false,
        'enableVersioning'            => true,
        'onload_callback' => array
		(
			array('tl_salsify_request', 'setRootType')
		),
        'sql' => array
        (
            'keys' => array
            (
                'id' 	=> 	'primary',
                'alias' =>  'index',
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
            'fields'                  => array('date_created DESC'),
            'panelLayout'             => 'filter;sort,search,limit'
        ),
        'label' => array
        (
            'fields'                  => array('date_created', 'district', 'school', 'psychologist', 'student'),
			'format'                  => '%s %s %s %s %s',
			'label_callback'          => array('tl_assignment', 'addIcon')
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
            'transactions' => array
            (
                'href'                => 'do=transaction',
                'icon'                => 'articles.svg'
            ),
            'edit' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_salsify_request']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_salsify_request']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('Bcs\Backend\SalsifyRequestBackend', 'toggleIcon')
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
        'default'                     => '{salsify_request_legend}, product_sku, email;{publish_legend},published;'
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
        'alias' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['alias'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'search'                  => false,
            'filter'                  => false,
            'eval'                    => array('unique'=>true, 'rgxp'=>'alias', 'doNotCopy'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
            'save_callback' => array
            (
                array('Bcs\Backend\SalsifyRequestBackend', 'generateAlias')
            ),
            'sql'                     => "varchar(128) COLLATE utf8mb3_bin NOT NULL default ''"
        ),
        'published' => array
        (
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['published'],
            'inputType'               => 'checkbox',
            'eval'                    => array('submitOnChange'=>false, 'doNotCopy'=>true),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        
        // Salsify Request Fields
        'product_sku' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['product_sku'],
            'inputType'               => 'text',
            'default'                 => '',
            'search'                  => false,
            'filter'                  => false,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'email' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['email'],
            'inputType'               => 'text',
            'default'                 => '',
            'search'                  => false,
            'filter'                  => false,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
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
  
}
