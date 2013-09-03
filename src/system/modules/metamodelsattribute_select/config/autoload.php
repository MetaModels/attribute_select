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
 * @author      Andreas Isaak <info@andreas-isaak.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'MetaModels\Attribute\Select\Select'       => 'system/modules/metamodelsattribute_select/MetaModels/Attribute/Select/Select.php',
	'MetaModels\Dca\AttributeSelect'           => 'system/modules/metamodelsattribute_select/MetaModels/Dca/AttributeSelect.php',
	'MetaModels\Filter\Rules\FilterRuleSelect' => 'system/modules/metamodelsattribute_select/MetaModels/Filter/Rules/FilterRuleSelect.php',

	'MetaModelAttributeSelect'              => 'system/modules/metamodelsattribute_select/deprecated/MetaModelAttributeSelect.php',
	'MetaModelFilterRuleSelect'             => 'system/modules/metamodelsattribute_select/deprecated/MetaModelFilterRuleSelect.php',
	'TableMetaModelsAttributeSelect'        => 'system/modules/metamodelsattribute_select/deprecated/TableMetaModelsAttributeSelect.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mm_attr_select'              => 'system/modules/metamodelsattribute_select/templates',
));
