<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package	   MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  CyberSpectrum
 * @license    private
 * @filesource
 */
if (!defined('TL_ROOT'))
{
	die('You cannot access this file directly!');
}

/**
 * Table tl_metamodel_attribute 
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['select extends _simpleattribute_'] = array
(
	'+title' => array('select_table after description', 'select_column', 'select_id', 'select_alias')
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_table'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table'],
	'exclude'               => true,
	'inputType'             => 'select',
	'options_callback'      => array('TableMetaModelsAttributeSelect', 'getTableNames'),
	'eval'                  => array
	(
		'includeBlankOption' => true,
		'doNotSaveEmpty' => true,
		'alwaysSave' => true,
		'submitOnChange'=> true,
        'tl_class'=>'w50',
		'chosen' => 'true'
	),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_column'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column'],
	'exclude'               => true,
	'inputType'             => 'select',
	'options_callback'      => array('TableMetaModelsAttributeSelect', 'getColumnNames'),
	'eval'                  => array
	(
		'includeBlankOption' => true,
		'doNotSaveEmpty' => true,
		'alwaysSave' => true,
		'submitOnChange'=> true,
		'tl_class'=>'w50',
		'chosen' => 'true'
	),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_id'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_id'],
	'exclude'               => true,
	'inputType'             => 'select',
	'options_callback'      => array('TableMetaModelsAttributeSelect', 'getIntColumnNames'),
	'eval'                  => array
	(
		'includeBlankOption' => true,
		'doNotSaveEmpty' => true,
		'alwaysSave' => true,
		'submitOnChange'=> true,
		'tl_class'=>'w50',
		'chosen' => 'true'
	),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_alias'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_alias'],
	'exclude'               => true,
	'inputType'             => 'select',
	'options_callback'      => array('TableMetaModelsAttributeSelect', 'getColumnNames'),
	'eval'                  => array
	(
		'includeBlankOption' => true,
		'doNotSaveEmpty' => true,
		'alwaysSave' => true,
		'submitOnChange'=> true,
		'tl_class'=>'w50',
		'chosen' => 'true'
	),
);

?>