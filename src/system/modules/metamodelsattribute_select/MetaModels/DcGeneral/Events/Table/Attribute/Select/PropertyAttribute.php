<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage Core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\DcGeneral\Events\Table\Attribute\Select;

use ContaoCommunityAlliance\Contao\EventDispatcher\Event\CreateEventDispatcherEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use MetaModels\DcGeneral\Events\BaseSubscriber;
use MetaModels\Factory;

/**
 * Handle events for tl_metamodel_attribute.alias_fields.attr_id.
 */
class PropertyAttribute
	extends BaseSubscriber
{
	/**
	 * Register all listeners to handle creation of a data container.
	 *
	 * @param CreateEventDispatcherEvent $event The event.
	 *
	 * @return void
	 */
	public static function registerEvents(CreateEventDispatcherEvent $event)
	{
		$dispatcher = $event->getEventDispatcher();
		self::registerBuildDataDefinitionFor(
			'tl_metamodel_attribute',
			$dispatcher,
			__CLASS__ . '::registerTableMetaModelAttributeEvents'
		);
	}

	/**
	 * Register the events for table tl_metamodel_attribute.
	 *
	 * @param BuildDataDefinitionEvent $event The event being processed.
	 *
	 * @return void
	 */
	public static function registerTableMetaModelAttributeEvents(BuildDataDefinitionEvent $event)
	{
		static $registered;
		if ($registered)
		{
			return;
		}
		$registered = true;
		$dispatcher = $event->getDispatcher();

		self::registerListeners(
			array(
				GetPropertyOptionsEvent::NAME => __CLASS__ . '::getTableNames',
			),
			$dispatcher,
			array('tl_metamodel_attribute', 'select_table')
		);

		self::registerListeners(
			array(
				GetPropertyOptionsEvent::NAME => __CLASS__ . '::getColumnNames',
			),
			$dispatcher,
			array('tl_metamodel_attribute', 'select_column')
		);

		self::registerListeners(
			array(
				GetPropertyOptionsEvent::NAME => __CLASS__ . '::getIntColumnNames',
			),
			$dispatcher,
			array('tl_metamodel_attribute', 'select_id')
		);

		self::registerListeners(
			array(
				GetPropertyOptionsEvent::NAME => __CLASS__ . '::getColumnNames',
			),
			$dispatcher,
			array('tl_metamodel_attribute', 'select_alias')
		);

		self::registerListeners(
			array(
				GetPropertyOptionsEvent::NAME => __CLASS__ . '::getColumnNames',
			),
			$dispatcher,
			array('tl_metamodel_attribute', 'select_sorting')
		);
	}

	/**
	 * Retrieve all database table names.
	 *
	 * @param GetPropertyOptionsEvent $event The event.
	 *
	 * @return void
	 */
	public static function getTableNames(GetPropertyOptionsEvent $event)
	{
		$objDB = \Database::getInstance();
		$event->setOptions($objDB->listTables());
	}

	/**
	 * Retrieve all column names for the current selected table.
	 *
	 * @param GetPropertyOptionsEvent $event The event.
	 *
	 * @return void
	 */
	public static function getColumnNames(GetPropertyOptionsEvent $event)
	{
		$model   = $event->getModel();
		$table   = $model->getProperty('select_table');
		$databse = \Database::getInstance();

		if (!$table || !$databse->tableExists($table))
		{
			return;
		}

		$result = array();

		foreach ($databse->listFields($table) as $arrInfo)
		{
			if ($arrInfo['type'] != 'index')
			{
				$result[$arrInfo['name']] = $arrInfo['name'];
			}
		}

		$event->setOptions($result);
	}

	/**
	 * Retrieve all column names of type int for the current selected table.
	 *
	 * @param GetPropertyOptionsEvent $event The event.
	 *
	 * @return void
	 */
	public static function getIntColumnNames(GetPropertyOptionsEvent $event)
	{
		$model   = $event->getModel();
		$table   = $model->getProperty('select_table');
		$databse = \Database::getInstance();

		if (!$table || !$databse->tableExists($table))
		{
			return;
		}

		$result = array();

		foreach ($databse->listFields($table) as $arrInfo)
		{
			if ($arrInfo['type'] != 'index' && $arrInfo['type'] == 'int')
			{
				$result[$arrInfo['name']] = $arrInfo['name'];
			}
		}

		$event->setOptions($result);
	}

	/**
	 * Check if the select_where value is valid by firing a test query.
	 *
	 * @param string                 $varValue The where condition to test.
	 *
	 * @param DataContainerInterface $objDC    The data container.
	 *
	 * @return array
	 *
	 * @ToDo: Have to refine for the save event.
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
				return $objModel->getProperty('select_where');
			}
		}

		return $varValue;
	}
}
