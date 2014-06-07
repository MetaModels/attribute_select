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
 * @author      Stefan Heimes <cms@men-at-work.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['select_as_radio'] = array('Display type', 'Select the desired display type.');

/**
 * Reference
 */
$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['select_as_radio_reference'][0] = 'Display as select menu';
$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['select_as_radio_reference'][1] = 'Display as radio button list';
$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['select_as_radio_reference'][2] = 'Display as picker popup';
