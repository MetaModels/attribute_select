<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2024 The MetaModels team.
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
 * @copyright  2012-2024 The MetaModels team.
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
        'select_sort',
        'select_where',
        'select_filter',
        'select_filterparams'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_table'] = [
    'label'       => 'select_table.label',
    'description' => 'select_table.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'sql'         => 'varchar(255) NOT NULL default \'\'',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
];


$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_column'] = [
    'label'       => 'select_column.label',
    'description' => 'select_column.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'sql'         => 'varchar(255) NOT NULL default \'\'',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_id'] = [
    'label'       => 'select_id.label',
    'description' => 'select_id.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'sql'         => 'varchar(255) NOT NULL default \'\'',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_alias'] = [
    'label'       => 'select_alias.label',
    'description' => 'select_alias.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'sql'         => 'varchar(255) NOT NULL default \'\'',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_sorting'] = [
    'label'       => 'select_sorting.label',
    'description' => 'select_sorting.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'sql'         => 'varchar(255) NOT NULL default \'\'',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'tl_class'           => 'clr w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_sort'] = [
    'label'       => 'select_sort.label',
    'description' => 'select_sort.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'options'     => ['asc', 'desc'],
    'eval'        => [
        'tl_class' => 'w50',
    ],
    'reference'   => [
        'asc'  => 'select_sort_directions.asc',
        'desc' => 'select_sort_directions.desc',
    ],
    'sql'         => "varchar(10) NOT NULL default 'asc'"
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_where'] = [
    'label'       => 'select_where.label',
    'description' => 'select_where.description',
    'exclude'     => true,
    'inputType'   => 'textarea',
    'sql'         => 'text NULL',
    'eval'        => [
        'tl_class'       => 'clr',
        'style'          => 'height: 4em;',
        'decodeEntities' => 'true'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_filter'] = [
    'label'       => 'select_filter.label',
    'description' => 'select_filter.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'sql'         => 'int(11) unsigned NOT NULL default \'0\'',
    'eval'        => [
        'includeBlankOption' => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'clr w50',
        'chosen'             => 'true'
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_filterparams'] = [
    'label'       => 'select_filterparams.label',
    'description' => 'select_filterparams.description',
    'exclude'     => true,
    'inputType'   => 'mm_subdca',
    'sql'         => 'text NULL',
    'eval'        => [
        'tl_class' => 'clr m12'
    ]
];
