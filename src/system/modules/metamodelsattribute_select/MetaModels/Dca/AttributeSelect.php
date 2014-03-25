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
use MetaModels\Helper\ContaoController;

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
		if (self::$objInstance == null) {
			self::$objInstance = new AttributeSelect();
		}
		return self::$objInstance;
	}

	public function getTableNames()
	{
		$objDB = \Database::getInstance();
		return $objDB->listTables();
	}

	public function getColumnNames(DataContainerInterface $objDC)
	{
		$arrFields = array();

		if (($objDC->getEnvironment()->getCurrentModel())
			&& \Database::getInstance()->tableExists($objDC->getEnvironment()->getCurrentModel()->getProperty('select_table')))
		{
			foreach ($this->Database->listFields($objDC->getEnvironment()->getCurrentModel()->getProperty('select_table')) as $arrInfo)
			{
				if ($arrInfo['type'] != 'index')
				{
					$arrFields[$arrInfo['name']] = $arrInfo['name'];
				}
			}
		}

		return $arrFields;
	}

	public function getIntColumnNames(DataContainerInterface $objDC)
	{
		$arrFields = array();

		if (($objDC->getEnvironment()->getCurrentModel())
			&& \Database::getInstance()->tableExists($objDC->getEnvironment()->getCurrentModel()->getProperty('select_table')))
		{
			foreach (\Database::getInstance()->listFields($objDC->getEnvironment()->getCurrentModel()->getProperty('select_table')) as $arrInfo)
			{
				if ($arrInfo['type'] != 'index' && $arrInfo['type'] == 'int')
				{
					$arrFields[$arrInfo['name']] = $arrInfo['name'];
				}
			}
		}

		return $arrFields;
	}

	public function checkQuery($varValue, DataContainerInterface $objDC)
	{
		if ($objDC->getEnvironment()->getCurrentModel() && $varValue)
		{
			$objDB    = \Database::getInstance();
			$objModel = $objDC->getEnvironment()->getCurrentModel();

			$strTableName = $objModel->getProperty('select_table');
			$strColNameId = $objModel->getProperty('select_id');
			$strColNameValue = $objModel->getProperty('select_column');
			$strColNameAlias = $objModel->getProperty('select_alias') ? $objModel->getProperty('select_alias') : $strColNameId;
			$strSortColumn = $objModel->getProperty('select_sorting') ? $objModel->getProperty('select_sorting') : $strColNameId;

			$strColNameWhere = $varValue;

			$strQuery = sprintf('SELECT %1$s.*
			FROM %1$s%2$s ORDER BY %1$s.%3$s',
				$strTableName, //1
				($strColNameWhere ? ' WHERE ('.$strColNameWhere.')' : false), //2
				$strSortColumn // 3
			);

            // replace inserttags but do not cache
            $strQuery = ContaoController::getInstance()->replaceInsertTags($strQuery, false);

			try
			{
				$objValue = $objDB
					->prepare($strQuery)
					->execute();
			}
			catch(\Exception $e)
			{
				// add error
				$objDC->addError($GLOBALS['TL_LANG']['tl_metamodel_attribute']['sql_error']);

				// log error
				$this->log($e->getMessage(), 'TableMetaModelsAttributeSelect checkQuery()', TL_ERROR);

				// keep the current value
				return $objDC->getEnvironment()->getCurrentModel()->getProperty('select_where');
			}
		}

		return $varValue;
	}
}
