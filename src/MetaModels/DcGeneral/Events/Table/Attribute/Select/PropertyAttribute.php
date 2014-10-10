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
 * @author      Stefan heimes <stefan_heimes@hotmail.com>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\DcGeneral\Events\Table\Attribute\Select;

use ContaoCommunityAlliance\Contao\EventDispatcher\Event\CreateEventDispatcherEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionChainInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyConditionChain;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\PropertyInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use MetaModels\DcGeneral\DataDefinition\Palette\Condition\Property\ConditionTableNameIsMetaModel;
use MetaModels\DcGeneral\Events\BaseSubscriber;
use MetaModels\IMetaModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handle events for tl_metamodel_attribute.alias_fields.attr_id.
 */
class PropertyAttribute extends BaseSubscriber
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
        if ($registered) {
            return;
        }
        $registered = true;
        $dispatcher = func_get_arg(2);

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

        self::registerListeners(
            array(
                GetPropertyOptionsEvent::NAME => __CLASS__ . '::getFilters',
            ),
            $dispatcher,
            array('tl_metamodel_attribute', 'select_filter')
        );

        self::registerListeners(
            array(
                BuildWidgetEvent::NAME => __CLASS__ . '::getFiltersParams',
            ),
            $dispatcher,
            array('tl_metamodel_attribute', 'select_filterparams')
        );

        self::buildConditions(
            array(
                'select_id'     => false,
                'select_alias'  => false,
                'select_where'  => false,
                'select_filter' => true,
                'select_filterparams' => true,
            ),
            $event->getContainer()->getPalettesDefinition()
        );
    }

    /**
     * Retrieve all database table names.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public static function getTableNames(GetPropertyOptionsEvent $event)
    {
        $dispatcher   = func_get_arg(2);
        $factory      = new \MetaModels\Factory($dispatcher, new \MetaModels\Attribute\Factory($dispatcher));
        $database     = \Database::getInstance();
        $translated   = $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table_type']['translated'];
        $untranslated = $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table_type']['untranslated'];
        $sqlTable     = $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table_type']['sql-table'];

        $result = array();
        $tables = $factory->collectNames();

        foreach ($tables as $table) {
            $metaModel = $factory->getMetaModel($table);
            if ($metaModel->isTranslated()) {
                $result[$translated][$table] = sprintf('%s (%s)', $metaModel->get('name'), $table);
            } else {
                $result[$untranslated][$table] = sprintf('%s (%s)', $metaModel->get('name'), $table);
            }
        }

        foreach ($database->listTables() as $table) {
            if ((substr($table, 0, 3) !== 'mm_')) {
                $result[$sqlTable][$table] = $table;
            }
        }

        if (is_array($result[$translated])) {
            asort($result[$translated]);
        }

        if (is_array($result[$untranslated])) {
            asort($result[$untranslated]);
        }

        if (is_array($result[$sqlTable])) {
            asort($result[$sqlTable]);
        }

        $event->setOptions($result);
    }

    /**
     * Retrieve all attribute names from a given MetaModel name.
     *
     * @param EventDispatcherInterface $dispatcher    The event dispatcher to use.
     *
     * @param string                   $metaModelName The name of the MetaModel.
     *
     * @return IMetaModel
     */
    public static function getMetaModelFromTableName(EventDispatcherInterface $dispatcher, $metaModelName)
    {
        $factory = new \MetaModels\Factory($dispatcher, new \MetaModels\Attribute\Factory($dispatcher));

        return $factory->getMetaModel($metaModelName);
    }

    /**
     * Retrieve all attribute names from a given MetaModel name.
     *
     * @param EventDispatcherInterface $dispatcher    The event dispatcher to use.
     *
     * @param string                   $metaModelName The name of the MetaModel.
     *
     * @return string[]
     */
    protected static function getAttributeNamesFrom(EventDispatcherInterface $dispatcher, $metaModelName)
    {
        $metaModel = self::getMetaModelFromTableName($dispatcher, $metaModelName);
        $result    = array();

        foreach ($metaModel->getAttributes() as $attribute) {
            $name   = $attribute->getName();
            $column = $attribute->getColName();
            $type   = $attribute->get('type');

            $result[$column] = sprintf('%s (%s - %s)', $name, $column, $type);
        }

        return $result;
    }

    /**
     * Retrieve all columns from a database table.
     *
     * @param string $tableName The database table name.
     *
     * @return string[]
     */
    protected static function getColumnNamesFrom($tableName)
    {
        $database = \Database::getInstance();

        if (!$tableName || !$database->tableExists($tableName)) {
            return array();
        }

        $result = array();

        foreach ($database->listFields($tableName) as $arrInfo) {
            if ($arrInfo['type'] != 'index') {
                $result[$arrInfo['name']] = $arrInfo['name'];
            }
        }

        return $result;
    }

    /**
     * Retrieve all column names for the current selected table.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public static function getColumnNames(GetPropertyOptionsEvent $event)
    {
        $model = $event->getModel();
        $table = $model->getProperty('select_table');

        if (substr($table, 0, 3) === 'mm_') {
            $attributes = self::getAttributeNamesFrom(func_get_arg(2), $table);
            asort($attributes);

            $event->setOptions(
                array
                (
                    $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column_type']['sql']
                        => array_diff_key(self::getColumnNamesFrom($table), array_flip(array_keys($attributes))),
                    $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column_type']['attribute']
                        => $attributes
                )
            );

            return;
        }

        $result = self::getColumnNamesFrom($table);

        if (!empty($result)) {
            asort($result);
            $event->setOptions($result);
        }
    }

    /**
     * Retrieve all filter names for the currently selected MetaModel.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public static function getFilters(GetPropertyOptionsEvent $event)
    {
        $model     = $event->getModel();
        $metaModel = self::getMetaModelFromTableName(func_get_arg(2), $model->getProperty('select_table'));

        if ($metaModel) {
            $filter = \Database::getInstance()
                ->prepare('SELECT id,name FROM tl_metamodel_filter WHERE pid=? ORDER BY name')
                ->execute($metaModel->get('id'));

            $result = array();
            while ($filter->next()) {
                $result[$filter->id] = $filter->name;
            }

            $event->setOptions($result);
        }
    }

    /**
     * Set the sub fields for the sub-dca based in the mm_filter selection.
     *
     * @param BuildWidgetEvent $event The event.
     *
     * @return void
     */
    public static function getFiltersParams(BuildWidgetEvent $event)
    {
        $model      = $event->getModel();
        $properties = $event->getProperty();
        $arrExtra   = $properties->getExtra();
        $filterId   = $model->getProperty('select_filter');

        // Check if we have a filter, if not return.
        if (empty($filterId)) {
            return;
        }

        // Get the filter with the given id and check if we got it.
        // If not return.
        $filterSettings = \MetaModels\Filter\Setting\Factory::byId($filterId);
        if ($filterSettings == null) {
            return;
        }

        // Set the subfields.
        $arrExtra['subfields'] = $filterSettings->getParameterDCA();
        $properties->setExtra($arrExtra);
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

        if (!$table || !$databse->tableExists($table)) {
            return;
        }

        $result = array();

        foreach ($databse->listFields($table) as $arrInfo) {
            if ($arrInfo['type'] != 'index' && $arrInfo['type'] == 'int') {
                $result[$arrInfo['name']] = $arrInfo['name'];
            }
        }

        $event->setOptions($result);
    }

    /**
     * Add a condition to a property.
     *
     * @param PropertyInterface  $property  The property.
     *
     * @param ConditionInterface $condition The condition to add.
     *
     * @return void
     */
    public static function addCondition($property, $condition)
    {
        $currentCondition = $property->getVisibleCondition();
        if ((!($currentCondition instanceof ConditionChainInterface))
            || ($currentCondition->getConjunction() != ConditionChainInterface::OR_CONJUNCTION)
        ) {
            if ($currentCondition === null) {
                $currentCondition = new PropertyConditionChain(array($condition));
            } else {
                $currentCondition = new PropertyConditionChain(array($currentCondition, $condition));
            }
            $currentCondition->setConjunction(ConditionChainInterface::OR_CONJUNCTION);
            $property->setVisibleCondition($currentCondition);
        } else {
            $currentCondition->addCondition($condition);
        }
    }

    /**
     * Build the data definition palettes.
     *
     * @param string[]                    $propertyNames The property names which shall be masked.
     *
     * @param PalettesDefinitionInterface $palettes      The palette definition.
     *
     * @return void
     */
    public static function buildConditions($propertyNames, PalettesDefinitionInterface $palettes)
    {
        foreach ($palettes->getPalettes() as $palette) {
            foreach ($propertyNames as $propertyName => $mask) {
                foreach ($palette->getProperties() as $property) {
                    if ($property->getName() === $propertyName) {
                        $condition = new ConditionTableNameIsMetaModel('select_table', $mask);
                        self::addCondition($property, $condition);
                    }
                }
            }
        }
    }
}
