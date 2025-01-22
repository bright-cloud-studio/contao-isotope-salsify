<?php

use Contao\Config;

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = str_replace('{files_legend', '{salsify_legend}, ;{files_legend', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);


$GLOBALS['TL_DCA']['tl_settings']['fields'] += [

    // Add a Select to pick the Model
    'openai_model' => [
        'label'             => &$GLOBALS['TL_LANG']['tl_settings']['openai_model'],
        'inputType'         => 'select',
        'options_callback'  => function ()
        {
            return [
                'gpt-4o-mini' => 'gpt-4o-mini',
            ];
        },
        'default'           => 'gpt-4o-mini',
        'eval'              => ['mandatory' => 'true', 'tl_class' => 'w50', 'chosen' => true, 'submitOnChange' => true],
    ],

    // Add an input box for the prompt
    'openai_prompt' => [
        'label'             => &$GLOBALS['TL_LANG']['tl_settings']['openai_prompt'],
        'inputType'         => 'text',
        'default'           => 'Based on this image, create a search engine optimized alt text (under 15 words)',
        'eval'              => ['mandatory' => 'true', 'tl_class' => 'w50'],
    ],

    // Add a Radio option to choose if we should automatically generate or not
    'openai_automatic' => [
        'label'             => &$GLOBALS['TL_LANG']['tl_settings']['openai_automatic'],
        'inputType'         => 'radio',
        'options'           => array('yes' => 'Yes', 'no' => 'No'),
        'default'           => 'yes',
        'eval'              => array('mandatory'=>true, 'tl_class'=>'w50'),
    ],

    // Lists file extensions that will trigger the ai
    'openai_extensions' => [
        'label'             => &$GLOBALS['TL_LANG']['tl_settings']['openai_extensions'],
        'inputType'         => 'text',
        'default'           => 'Based on this image, create a search engine optimized alt text (under 15 words)',
        'eval'              => array('mandatory'=>true, 'tl_class'=>'w50', 'default'=> 'Based on this image, create a search engine optimized alt text (under 15 words)'),
    ],
    
];
