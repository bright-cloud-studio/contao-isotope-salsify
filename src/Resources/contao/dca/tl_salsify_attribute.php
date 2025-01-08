<?php

/* Salsify Attribute - Child to Salsify Request */

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
        'ptable'                      => 'tl_assignment',
        'switchToEdit'                => false,
		    'enableVersioning'            => true,
		    'markAsCopy'                  => 'title',
        'onsubmit_callback' => array
		    (
			    array('Bcs\Backend\TransactionBackend', 'createTransaction')
		    ),
        'sql' => array
        (
            'keys' => array
            (
                'id' 	=> 	'primary',
                'alias' =>  'index',
                'pid'   =>  'index'
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
            'icon'                    => 'pagemounts.svg',
            'defaultSearchField'      => 'date_submitted',
            'flag'                    => DataContainer::SORT_DESC,
            'fields'                  => array('date_submitted DESC'),
            'panelLayout'             => 'filter;sort,search,limit'
        ),
        'label' => array
        (
            'fields'                  => array('date_submitted', 'psychologist'),
			      'format'                  => '%s -  %s',
			      'label_callback'          => array('tl_transaction', 'addIcon')
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
                'label'               => &$GLOBALS['TL_LANG']['tl_transaction']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'toggle' => array
			      (
				      'label'               => &$GLOBALS['TL_LANG']['tl_transaction']['toggle'],
				      'icon'                => 'visible.gif',
				      'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				      'button_callback'     => array('Bcs\Backend\TransactionBackend', 'toggleIcon')
			      ),
            'delete' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_transaction']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_transaction']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
 
    // Palettes
    'palettes' => array
    (
        'default'                     => '{assignment_details_legend}, assignment_details;{transaction_legend},date_submitted, psychologist, service, price;{meeting_legend}, meeting_date, meeting_start, meeting_end, meeting_duration;{notes_legend},notes;{publish_legend},published;{internal_legend:hide}, lasid, sasid, originally_submitted;'
    ),
 
    // Fields
    'fields' => array
    (
        /* ******************* */
        // Contao Fields
        /* ******************* */
        'id' => array
        (
		      'sql'                     	=> "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array
        (
		      'foreignKey'              => 'tl_assignment.id',
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
        'alias' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transaction']['alias'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'search'                  => false,
            'eval'                    => array('unique'=>true, 'rgxp'=>'alias', 'doNotCopy'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
            'save_callback' => array
            (
                array('Bcs\Backend\TransactionBackend', 'generateAlias')
            ),
            'sql'                     => "varchar(128) COLLATE utf8mb3_bin NOT NULL default ''"

        ),
        'published' => array
        (
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['published'],
            'inputType'               => 'checkbox',
            'eval'                    => array('submitOnChange'=>false, 'doNotCopy'=>true),
            'sql'                     => "char(1) NOT NULL default ''"
        ),


        /* ***************** */
        // Assignment Details |
        /* ***************** */
        
        'assignment_details' => array
        (
            'input_field_callback'  => array('Bcs\Backend\TransactionBackend', 'getAssignmentDetails'),
            'eval'                  => array('doNotShow'=>true),
        ),

        
        
        /* ******************* */
        // Transaction Fields
        /* ******************* */
        
        'date_submitted' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transaction']['date_submitted'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => false,
            'search'                  => false,
            'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default 0",
            'default'                 => date("m/d/y")
        ),
        'psychologist' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transaction']['psychologist'],
            'inputType'               => 'select',
            'filter'                  => true,
            'search'                  => true,
            'flag'                    => DataContainer::SORT_ASC,
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50', 'chosen'=>true),
            'options_callback'	      => array('Bcs\Backend\TransactionBackend', 'getPsychologists'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'service' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['service'],
            'inputType'               => 'select',
            'filter'                  => true,
            'search'                  => false,
            'flag'                    => DataContainer::SORT_ASC,
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50', 'chosen'=>true),
            'options_callback'	      => array('Bcs\Backend\TransactionBackend', 'getServices'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'price' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['price'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => false,
            'search'                  => false,
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        
        'meeting_date' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['meeting_date'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => false,
            'search'                  => false,
            'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(20) NOT NULL default ''",
            'default'                 => date("m/d/y")
        ),
        'meeting_start' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['meeting_start'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => true,
            'search'                  => true,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'meeting_end' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['meeting_end'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => true,
            'search'                  => true,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'meeting_duration' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['meeting_duration'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => false,
            'search'                  => false,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'notes' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['notes'],
            'inputType'               => 'text',
            'default'                 => '',
            'search'                  => true,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'clr', 'allowHtml'=>false),
            'sql'                     => "text NOT NULL default ''"
        ),



        // Hidden fields for search purposes
        'lasid' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['lasid'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => true,
            'search'                  => true,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'sasid' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transactions']['sasid'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => true,
            'search'                  => true,
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'originally_submitted' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_transaction']['originally_submitted'],
            'inputType'               => 'text',
            'default'                 => '',
            'filter'                  => false,
            'search'                  => false,
            'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned",
            'default'                 => ''
        ),
        
    )
);





class tl_transaction extends Backend
{

    /** @return string */
	public function compile()
	{
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
		if($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
            $GLOBALS['TL_CSS'][] = 'bundles/bcspaymentdashboard/css/be_coloring.css';
		}
	}
    
	public function addIcon($row, $label)
	{
        // Clear out our current label
        $label = '';

        // Add our formatted date and a dash
        $label .= date('m/d/Y', $row['date_submitted']) . " - ";

        // Add the Psy's name
        $psy = MemberModel::findBy('id', $row['psychologist']);
        $label .= $psy->firstname . " " . $psy->lastname . " - ";

        // Add Assignments District
        $assignment = Assignment::findBy('id', $row['pid']);
        $district = District::findBy('id', $assignment->district);
        $label .= $district->district_name . " - ";

        // Add Service
        $service = Service::findBy('service_code', $row['service']);
        $label .= $service->name . " - ";

        // Add LASID / SASID
        $student = Student::findBy('id', $assignment->student);
        
        $label .= $student->name . " - ";
        
        if($student->lasid != '' && $student->sasid != '') {
            $label .= $student->lasid . " / " . $student->sasid;
        } else {
            if($student->lasid != '')
                $label .= $student->lasid;
            if($student->sasid != '')
                $label .= $student->sasid;
        }
        
        
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

		$href = System::getContainer()->get('router')->generate('contao_backend_preview', array('page'=>$row['pid'], 'article'=>($row['alias'] ?: $row['id'])));

		return '<a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['view']) . '" target="_blank">' . Image::getHtml($image, '', $attributes) . '</a> ' . $label;
	}
}
