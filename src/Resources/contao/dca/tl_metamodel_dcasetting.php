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
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Oliver Hoff <oliver@hofff.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

use Contao\System;
use MetaModels\ContaoFrontendEditingBundle\MetaModelsContaoFrontendEditingBundle;

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['attr_id']['select'] = [
    'presentation' => [
        'tl_class',
        'be_template',
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
    'label'       => 'select_as_radio.label',
    'description' => 'select_as_radio.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'options'     => [0, 1, 2],
    'reference'   => [
        '0' => 'select_as_radio_reference.0',
        '1' => 'select_as_radio_reference.1',
        '2' => 'select_as_radio_reference.2',
    ],
    'sql'         => 'varchar(1) NOT NULL default \'0\'',
    'eval'        => [
        'tl_class' => 'clr w50'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['select_minLevel'] = [
    'label'       => 'select_minLevel.label',
    'description' => 'select_minLevel.description',
    'exclude'     => true,
    'inputType'   => 'text',
    'sql'         => 'int(11) NOT NULL default \'0\'',
    'eval'        => [
        'tl_class' => 'clr w50'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['select_maxLevel'] = [
    'label'       => 'select_maxLevel.label',
    'description' => 'select_maxLevel.description',
    'exclude'     => true,
    'inputType'   => 'text',
    'sql'         => 'int(11) NOT NULL default \'0\'',
    'eval'        => [
        'tl_class' => 'w50'
    ]
];

// Load configuration for the frontend editing.
if (\in_array(
    MetaModelsContaoFrontendEditingBundle::class,
    System::getContainer()->getParameter('kernel.bundles'),
    true
)) {
    $GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['attr_id']['select']['presentation'][] =
        'fe_template';
}
