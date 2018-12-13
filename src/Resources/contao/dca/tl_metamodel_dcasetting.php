<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Oliver Hoff <oliver@hofff.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['attr_id']['select'] = [
    'presentation' => [
        'tl_class',
        'includeBlankOption',
        'submitOnChange',
        'chosen',
        'select_as_radio'
    ],
    'functions'    => [
        'mandatory'
    ],
    'overview'     => [
        'filterable',
        'searchable'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['palettes']['__selector__'][] = 'select_as_radio';

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['select_as_radio'][2] = [
    'presentation after select_as_radio' => [
        'select_minLevel',
        'select_maxLevel'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['select_as_radio'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['select_as_radio'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => [0, 1, 2],
    'reference' => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['select_as_radio_reference'],
    'sql'       => 'varchar(1) NOT NULL default \'0\'',
    'eval'      => [
        'tl_class' => 'clr'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['select_minLevel'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['select_minLevel'],
    'exclude'   => true,
    'inputType' => 'text',
    'sql'       => 'int(11) NOT NULL default \'0\'',
    'eval'      => [
        'tl_class' => 'clr w50'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['select_maxLevel'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['select_maxLevel'],
    'exclude'   => true,
    'inputType' => 'text',
    'sql'       => 'int(11) NOT NULL default \'0\'',
    'eval'      => [
        'tl_class' => 'w50'
    ]
];
