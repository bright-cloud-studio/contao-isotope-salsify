<?php

use Contao\Config;

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = str_replace('{files_legend', '{salsify_legend}, category_parent_page, salsify_category_field;{files_legend', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);


$GLOBALS['TL_DCA']['tl_settings']['fields'] += [
    
    // Add an input box for the prompt
    'category_parent_page' => [
        'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['category_parent_page'],
        'inputType'               => 'pageTree',
        'eval'                    => array('files'=>false, 'fieldType'=>'radio', 'multiple'=>true, 'tl_class'=>'w50'),
        'sql'                     => "blob NULL",
        'relation'                => array('table'=>'tl_page', 'type'=>'hasMany', 'load'=>'lazy')
    ],

    // Add a Radio option to choose if we should automatically generate or not
    'salsify_category_field' => [
        'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['salsify_category_field'],
        'inputType'               => 'text',
        'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
        'sql'                     => "varchar(255) default ''"
    ]
];
