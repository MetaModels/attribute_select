<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2016 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['typeOptions']['select']  = 'Select';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table'][0]        = 'Source table';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table'][1]        = 'Please select the source table from where the values shall be retrieved from.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column'][0]       = 'Value column';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column'][1]       = 'Please select the column where the display text value shall be retrieved from.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_id'][0]           = 'Id column';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_id'][1]           = 'Please select the column that shall be used as id';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_alias'][0]        = 'Alias column';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_alias'][1]        = 'Please select the column that shall be used as option alias (Used in filter widgets i.e.). Select the same as for the id column if unsure.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_sorting'][0]      = 'Select sorting';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_sorting'][1]      = 'Please select an entry for the tag sorting.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_where'][0]        = 'SQL';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_where'][1]        = 'The list of options can be limited by using SQL.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_filter'][0]       = 'Filter';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_filter'][1]       = 'Here you can choose the filter to use.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_filterparams'][0] = 'Filter parameters';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_filterparams'][1] = 'Here you can choose a default value for the filter.';

/**
 * Misc.
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['sql_error']             = 'The SQL query causes an error.';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table_type']['translated']   = 'Translated MetaModels';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table_type']['untranslated'] = 'Untranslated MetaModels';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table_type']['sql-table']    = 'SQL Table';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column_type']['attribute'] = 'MetaModel attributes';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column_type']['sql']       = 'SQL table column';
