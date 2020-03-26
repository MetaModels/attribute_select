<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2020 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2020 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['select extends _simpleattribute_'] = [
    '+display' => [
        'select_table after description',
        'select_column',
        'select_id',
        'select_alias',
        'select_sorting',
        'select_where',
        'select_filter',
        'select_filterparams'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_table'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
];


$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_column'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_id'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_id'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_alias'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_alias'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_sorting'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_sorting'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => 'varchar(255) NOT NULL default \'\'',
    'eval'      => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_where'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_where'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'sql'       => 'text NULL',
    'eval'      => [
        'tl_class'       => 'clr',
        'style'          => 'height: 4em;',
        'decodeEntities' => 'true'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_filter'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_filter'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => 'int(11) unsigned NOT NULL default \'0\'',
    'eval'      => [
        'includeBlankOption' => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'clr w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_filterparams'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_filterparams'],
    'exclude'   => true,
    'inputType' => 'mm_subdca',
    'sql'       => 'text NULL',
    'eval'      => [
        'tl_class' => 'clr m12'
    ]
];
