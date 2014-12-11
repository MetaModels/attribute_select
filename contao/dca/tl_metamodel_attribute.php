<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package     MetaModels
 * @subpackage  AttributeSelect
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author      Christian de la Haye <service@delahaye.de>
 * @author      Stefan heimes <stefan_heimes@hotmail.com>
 * @author      Andreas Isaak <info@andreas-isaak.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

/**
 * Table tl_metamodel_attribute 
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['select extends _simpleattribute_'] = array
(
    '+display' => array(
        'select_table after description',
        'select_column',
        'select_id',
        'select_alias',
        'select_sorting',
        'select_where',
        'select_filter',
        'select_filterparams'
    )
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_table'] = array
(
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table'],
    'exclude'                => true,
    'inputType'              => 'select',
    'eval'                   => array
    (
        'includeBlankOption' => true,
        'mandatory'          => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ),
);


$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_column'] = array
(
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column'],
    'exclude'                => true,
    'inputType'              => 'select',
    'eval'                   => array
    (
        'includeBlankOption' => true,
        'mandatory'          => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_id'] = array
(
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_id'],
    'exclude'                => true,
    'inputType'              => 'select',
    'eval'                   => array
    (
        'includeBlankOption' => true,
        'mandatory'          => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_alias'] = array
(
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_alias'],
    'exclude'                => true,
    'inputType'              => 'select',
    'eval'                   => array
    (
        'includeBlankOption' => true,
        'mandatory'          => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_sorting'] = array
(
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_sorting'],
    'exclude'                => true,
    'inputType'              => 'select',
    'eval'                   => array
    (
        'includeBlankOption' => true,
        'mandatory'          => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_where'] = array
(
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_where'],
    'exclude'                => true,
    'inputType'              => 'textarea',
    'eval'                   => array
    (
        'tl_class'           => 'clr',
        'style'              => 'height: 4em;',
        'decodeEntities'     => 'true'
    )
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_filter'] = array
(
    'label'            => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_filter'],
    'exclude'          => true,
    'inputType'        => 'select',
    'eval'             => array
    (
        'includeBlankOption' => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_filterparams'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_filterparams'],
    'exclude'   => true,
    'inputType' => 'mm_subdca',
    'eval'      => array
    (
        'tl_class'   => 'clr m12'
    )
);
