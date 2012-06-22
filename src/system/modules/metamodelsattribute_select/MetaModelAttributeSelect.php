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
 * This is the MetaModelAttribute class for handling select attributes.
 * 
 * @package	   MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class MetaModelAttributeSelect extends MetaModelAttributeHybrid
{

	public function getOptions($blnUsedOnly=false)
	{
		return array();
	}

	/////////////////////////////////////////////////////////////////
	// interface IMetaModelAttribute
	/////////////////////////////////////////////////////////////////

	public function getAttributeSettingNames()
	{
		return array_merge(parent::getAttributeSettingNames(), array(
			'select_table',
			'select_column',
			'select_id',
			'select_alias'
		));
	}

	public function getFieldDefinition()
	{
		// TODO: add tree support here.
		$arrFieldDef=parent::getFieldDefinition();
		$arrFieldDef['inputType'] = 'select';
		$arrFieldDef['options'] = $this->getFilterOptions();
		return $arrFieldDef;
	}

	public function parseValue($arrRowData, $strOutputFormat = 'text')
	{
		$arrResult = parent::parseValue($arrRowData, $strOutputFormat);
		$arrResult['text'] = $arrRowData[$this->getColName()][$this->get('select_column')];
		return $arrResult;
	}

	/**
	 * {@inheritdoc}
	 */
	public function parseFilterUrl($arrUrlParams)
	{
		$objFilterRule = NULL;
		if (key_exists($this->getColName(), $arrUrlParams))
		{
			$objFilterRule = new MetaModelFilterRuleSelect($this, $arrUrlParams[$this->getColName()]);
		}
		return $objFilterRule;
	}

	/**
	 * {@inheritdoc}
	 * 
	 * Fetch filter options from foreign table.
	 * 
	 */
	public function getFilterOptions($arrIds = array())
	{
		$strTableName = $this->get('select_table');
		$strColNameId = $this->get('select_id');
		$arrReturn = array();

		if ($strTableName && $strColNameId)
		{
			$strColNameValue = $this->get('select_column');
			$strColNameAlias = $this->get('select_alias');
			if (!$strColNameAlias)
			{
				$strColNameAlias = $strColNameId;
			}
			$objDB = Database::getInstance();
			if ($arrIds)
			{
				$objValue = $objDB->prepare(sprintf('
					SELECT %1$s.*
					FROM %1$s 
					WHERE %1$s.%2$s IN (%3$s) GROUP BY %1$s.%2$s',
					$strTableName, // 1
					$strColNameId, // 2
					implode(',', $arrIds) // 3
				))
				->execute($this->get('id'));
			} else {
				$objValue = $objDB->prepare(sprintf('SELECT %1$s.* FROM %1$s', $strTableName))
				->execute();
			}

			while ($objValue->next())
			{
				$arrReturn[$objValue->$strColNameAlias] = $objValue->$strColNameValue;
			}
		}
		return $arrReturn;
	}

	/////////////////////////////////////////////////////////////////
	// interface IMetaModelAttributeSimple
	/////////////////////////////////////////////////////////////////

	public function getSQLDataType()
	{
		return 'int(11) NOT NULL default \'0\'';
	}

	/////////////////////////////////////////////////////////////////
	// interface IMetaModelAttributeComplex
	/////////////////////////////////////////////////////////////////

	public function getDataFor($arrIds)
	{
		$objDB = Database::getInstance();
		$strTableNameId = $this->get('select_table');
		$strColNameId = $this->get('select_id');
		$arrReturn = array();

		if ($strTableNameId && $strColNameId)
		{
			$strMetaModelTableName = $this->getMetaModel()->getTableName();
			$strMetaModelTableNameId = $strMetaModelTableName.'_id';

			$objValue = $objDB->prepare(sprintf('SELECT %1$s.*, %2$s.id AS %3$s FROM %1$s LEFT JOIN %2$s ON (%1$s.%4$s=%2$s.%5$s) WHERE %2$s.id IN (%6$s)',
				$strTableNameId, // 1
				$strMetaModelTableName, // 2
				$strMetaModelTableNameId, // 3
				$strColNameId, // 4
				$this->getColName(), // 5
				implode(',', $arrIds) //6
			))
			->execute();
			while ($objValue->next())
			{
				$arrReturn[$objValue->$strMetaModelTableNameId] = $objValue->row();
			}
		}
		return $arrReturn;
	}

	public function setDataFor($arrValues)
	{
		// TODO: store to database.
	}
}

?>