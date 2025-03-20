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
			'label_callback'          => array('tl_salsify_request', 'addIcon')
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
        'default'                     => '{salsify_request_legend}, request_name, source_folder, isotope_name_key, isotope_sku_key; {latest_file_legend}, file_url, file_date; {customization_legend}, autolink_isotope_attributes; {internal_information:hide}, flag_update;'
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
        

        // Customization Options
        'autolink_isotope_attributes' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['autolink_isotope_attributes'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),


        'flag_update' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_salsify_request']['flag_update'],
            'inputType'               => 'checkbox',
            'default'				  => '',
            'eval'                    => array('multiple'=>false, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        
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
