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
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['typeOptions']['select'] = 'Select';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table'][0]       = 'Source table';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table'][1]       = 'Please select the source table from where the values shall be retrieved from.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column'][0]      = 'Value column';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column'][1]      = 'Please select the column where the display text value shall be retrieved from.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_id'][0]          = 'Id column';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_id'][1]          = 'Please select the column that shall be used as id';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_alias'][0]       = 'Alias column';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_alias'][1]       = 'Please select the column that shall be used as option alias (Used in filter widgets i.e.). Select the same as for the id column if unsure.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_sorting'][0]     = 'Select sorting';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_sorting'][1]     = 'Please select an entry for the tag sorting.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_where'][0]       = 'SQL';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_where'][1]       = 'The list of options can be limited by using SQL.';

/**
 * Misc.
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['sql_error']             = 'The SQL query causes an error.';
