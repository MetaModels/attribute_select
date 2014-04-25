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
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Dca;

use DcGeneral\DataContainerInterface;

/**
 * Supplementary class for handling DCA information for select attributes.
 *
 * @package    MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class AttributeSelect extends Attribute
{
	/**
	 * The singleton instance.
	 *
	 * @var AttributeSelect
	 */
	protected static $objInstance = null;

	/**
	 * Get the static instance.
	 *
	 * @static
	 * @return AttributeSelect
	 */
	public static function getInstance()
	{
		if (self::$objInstance == null)
		{
			self::$objInstance = new AttributeSelect();
		}
		return self::$objInstance;
	}

	/**
	 * Retrieve all database table names.
	 *
	 * @return array
	 */
	public function getTableNames()
	{
		$objDB = \Database::getInstance();
		return $objDB->listTables();
	}

	/**
	 * Retrieve all column names for the current selected table.
	 *
	 * @param DataContainerInterface $objDC The data container.
	 *
	 * @return array
	 */
	public function getColumnNames(DataContainerInterface $objDC)
	{
		$arrFields = array();
		$objDB     = \Database::getInstance();
		$model     = $objDC->getEnvironment()->getCurrentModel();

		if ($model && $objDB->tableExists($model->getProperty('select_table')))
		{
			foreach ($objDB->listFields($model->getProperty('select_table')) as $arrInfo)
			{
				if ($arrInfo['type'] != 'index')
				{
					$arrFields[$arrInfo['name']] = $arrInfo['name'];
				}
			}
		}

		return $arrFields;
	}

	/**
	 * Retrieve all column names of type int for the current selected table.
	 *
	 * @param DataContainerInterface $objDC The data container.
	 *
	 * @return array
	 */
	public function getIntColumnNames(DataContainerInterface $objDC)
	{
		$arrFields = array();
		$objDB     = \Database::getInstance();
		$model     = $objDC->getEnvironment()->getCurrentModel();

		if ($model && $objDB->tableExists($model->getProperty('select_table')))
		{
			foreach ($objDB->listFields($model->getProperty('select_table')) as $arrInfo)
			{
				if ($arrInfo['type'] != 'index' && $arrInfo['type'] == 'int')
				{
					$arrFields[$arrInfo['name']] = $arrInfo['name'];
				}
			}
		}

		return $arrFields;
	}

	/**
	 * Check if the select_where value is valid by firing a test query.
	 *
	 * @param string                 $varValue The where condition to test.
	 *
	 * @param DataContainerInterface $objDC    The data container.
	 *
	 * @return array
	 */
	public function checkQuery($varValue, DataContainerInterface $objDC)
	{
		$objModel = $objDC->getEnvironment()->getCurrentModel();

		if ($objModel && $varValue)
		{
			$objDB = \Database::getInstance();

			$strTableName  = $objModel->getProperty('select_table');
			$strColNameId  = $objModel->getProperty('select_id');
			$strSortColumn = $objModel->getProperty('select_sorting') ?: $strColNameId;

			$strColNameWhere = $varValue;

			$strQuery = sprintf('
			SELECT %1$s.*
			FROM %1$s%2$s
			ORDER BY %1$s.%3$s',
				// @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
				$strTableName,                                                // 1
				($strColNameWhere ? ' WHERE ('.$strColNameWhere.')' : false), // 2
				$strSortColumn                                                // 3
				// @codingStandardsIgnoreEnd
			);

			try
			{
				$objDB
					->prepare($strQuery)
					->execute();
			}
			catch(\Exception $e)
			{
				// Add error.
				$objDC->addError($GLOBALS['TL_LANG']['tl_metamodel_attribute']['sql_error']);

				// Log error.
				$this->log($e->getMessage(), 'TableMetaModelsAttributeSelect checkQuery()', TL_ERROR);

				// Keep the current value.
				return $objDC->getEnvironment()->getCurrentModel()->getProperty('select_where');
			}
		}

		return $varValue;
	}
}
