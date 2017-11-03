<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2017 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Martin Treml <github@r2pi.net>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\EncodePropertyValueFromWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionChainInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\NotCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyValueCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyConditionChain;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\PropertyInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use MetaModels\DcGeneral\DataDefinition\Palette\Condition\Property\ConditionTableNameIsMetaModel;
use MetaModels\DcGeneral\Events\BaseSubscriber;

/**
 * Handle events for tl_metamodel_attribute.alias_fields.attr_id.
 */
class Subscriber extends BaseSubscriber
{
    /**
     * Boot the system in the backend.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function registerEventsInDispatcher()
    {
        $this
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                array($this, 'getTableNames')
            )
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                array($this, 'getColumnNames')
            )
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                array($this, 'getIntColumnNames')
            )
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                array($this, 'getFilters')
            )
            ->addListener(
                BuildWidgetEvent::NAME,
                array($this, 'getFiltersParams')
            )
            ->addListener(
                BuildDataDefinitionEvent::NAME,
                array($this, 'buildPaletteRestrictions')
            )
            ->addListener(
                EncodePropertyValueFromWidgetEvent::NAME,
                array($this, 'checkQuery')
            );
    }

    /**
     * Retrieve all MetaModels table names.
     *
     * @param string $keyTranslated   The array key to use for translated MetaModels.
     *
     * @param string $keyUntranslated The array key to use for untranslated MetaModels.
     *
     * @return array
     */
    private function getMetaModelTableNames($keyTranslated, $keyUntranslated)
    {
        $factory = $this->getServiceContainer()->getFactory();
        $result  = array();
        $tables  = $factory->collectNames();

        foreach ($tables as $table) {
            $metaModel = $factory->getMetaModel($table);
            if ($metaModel->isTranslated()) {
                $result[$keyTranslated][$table] = sprintf('%s (%s)', $metaModel->get('name'), $table);
            } else {
                $result[$keyUntranslated][$table] = sprintf('%s (%s)', $metaModel->get('name'), $table);
            }
        }

        return $result;
    }

    /**
     * Retrieve all database table names.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getTableAndMetaModelsList()
    {
        $database     = $this->getServiceContainer()->getDatabase();
        $sqlTable     = $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table_type']['sql-table'];
        $translated   = $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table_type']['translated'];
        $untranslated = $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_table_type']['untranslated'];

        $result = $this->getMetaModelTableNames($translated, $untranslated);

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

        return $result;
    }

    /**
     * Retrieve all database table names.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getTableNames(GetPropertyOptionsEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'select_table')) {
            return;
        }

        $event->setOptions($this->getTableAndMetaModelsList());
    }

    /**
     * Retrieve all attribute names from a given MetaModel name.
     *
     * @param string $metaModelName The name of the MetaModel.
     *
     * @return string[]
     */
    protected function getAttributeNamesFrom($metaModelName)
    {
        $metaModel = $this->getServiceContainer()->getFactory()->getMetaModel($metaModelName);
        $result    = array();

        if (empty($metaModel)) {
            return $result;
        }

        foreach ($metaModel->getAttributes() as $attribute) {
            $name   = $attribute->getName();
            $column = $attribute->getColName();
            $type   = $attribute->get('type');

            $result[$column] = sprintf('%s (%s - %s)', $name, $column, $type);
        }

        return $result;
    }

    /**
     * Check if a table exists in the database.
     *
     * @param string $table The table name.
     *
     * @return bool
     */
    private function tableExists($table)
    {
        return (!empty($table) && $this->getServiceContainer()->getDatabase()->tableExists($table));
    }

    /**
     * Retrieve all columns from a database table.
     *
     * @param string     $tableName  The database table name.
     *
     * @param array|null $typeFilter Optional of types to filter for.
     *
     * @return string[]
     */
    protected function getColumnNamesFromMetaModel($tableName, $typeFilter = null)
    {
        $database = $this->getServiceContainer()->getDatabase();

        if (!$this->tableExists($tableName)) {
            return array();
        }

        $result = array();

        foreach ($database->listFields($tableName) as $arrInfo) {
            if ($arrInfo['type'] == 'index') {
                continue;
            }

            if (($typeFilter === null) || in_array($arrInfo['type'], $typeFilter)) {
                $result[$arrInfo['name']] = $arrInfo['name'];
            }
        }

        if (!empty($result)) {
            asort($result);
            return $result;
        }

        return $result;
    }

    /**
     * Retrieve all column names for the given table.
     *
     * @param string $table The table name.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getColumnNamesFrom($table)
    {
        if (substr($table, 0, 3) === 'mm_') {
            $attributes = $this->getAttributeNamesFrom($table);
            asort($attributes);

            return
                array
                (
                    $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column_type']['sql'] => array_diff_key(
                        $this->getColumnNamesFromMetaModel($table),
                        array_flip(array_keys($attributes))
                    ),
                    $GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_column_type']['attribute'] => $attributes
                );
        }

        return $this->getColumnNamesFromMetaModel($table);
    }

    /**
     * Retrieve all column names for the current selected table.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getColumnNames(GetPropertyOptionsEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || (
                ($event->getPropertyName() !== 'select_column')
                && ($event->getPropertyName() !== 'select_alias')
                && ($event->getPropertyName() !== 'select_sorting')
            )
        ) {
            return;
        }

        $result = $this->getColumnNamesFrom($event->getModel()->getProperty('select_table'));

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
    public function getFilters(GetPropertyOptionsEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'select_filter')
        ) {
            return;
        }

        $model     = $event->getModel();
        $metaModel = $this->getServiceContainer()->getFactory()->getMetaModel($model->getProperty('select_table'));

        if ($metaModel) {
            $filter = $this
                ->getServiceContainer()
                ->getDatabase()
                ->prepare('SELECT id,name FROM tl_metamodel_filter WHERE pid=? ORDER BY name')
                ->execute($metaModel->get('id'));

            $result = array();
            while ($filter->next()) {
                /** @noinspection PhpUndefinedFieldInspection */
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
    public function getFiltersParams(BuildWidgetEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getProperty()->getName() !== 'select_filterparams')
        ) {
            return;
        }

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
        $filterSettings = $this->getServiceContainer()->getFilterFactory()->createCollection($filterId);
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
    public function getIntColumnNames(GetPropertyOptionsEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'select_id')
        ) {
            return;
        }

        $result = $this->getColumnNamesFromMetaModel($event->getModel()->getProperty('select_table'), array('int'));

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
    public function addCondition($property, $condition)
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
     * @param array<string,bool>          $propertyNames The property names which shall be masked.
     *
     * @param PalettesDefinitionInterface $palettes      The palette definition.
     *
     * @return void
     */
    public function buildConditions($propertyNames, PalettesDefinitionInterface $palettes)
    {
        foreach ($palettes->getPalettes() as $palette) {
            foreach ($propertyNames as $propertyName => $mask) {
                foreach ($palette->getProperties() as $property) {
                    if ($property->getName() === $propertyName) {
                        // Show the widget when we are editing a select attribute.
                        $condition = new PropertyConditionChain(
                            array(
                                new PropertyConditionChain(
                                    array(
                                        new PropertyValueCondition('type', 'select'),
                                        new ConditionTableNameIsMetaModel('select_table', $mask)
                                    )
                                )
                            ),
                            ConditionChainInterface::OR_CONJUNCTION
                        );
                        // If we want to hide the widget for metamodel tables, do so only when editing a select
                        // attribute.
                        if (!$mask) {
                            $condition->addCondition(new NotCondition(new PropertyValueCondition('type', 'select')));
                        }

                        $this->addCondition($property, $condition);
                    }
                }
            }
        }
    }

    /**
     * Build the data definition palettes.
     *
     * @param BuildDataDefinitionEvent $event The event.
     *
     * @return void
     */
    public function buildPaletteRestrictions(BuildDataDefinitionEvent $event)
    {
        if ($event->getContainer()->getName() !== 'tl_metamodel_attribute') {
            return;
        }

        $this->buildConditions(
            array(
                'select_id'     => false,
                'select_where'  => false,
                'select_filter' => true,
                'select_filterparams' => true,
            ),
            $event->getContainer()->getPalettesDefinition()
        );
    }


    /**
     * Check if the select_where value is valid by firing a test query.
     *
     * @param EncodePropertyValueFromWidgetEvent $event The event.
     *
     * @return void
     *
     * @throws \RuntimeException When the where condition is invalid.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function checkQuery(EncodePropertyValueFromWidgetEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getProperty() !== 'select_where')
        ) {
            return;
        }

        $where  = $event->getValue();
        $values = $event->getPropertyValueBag();

        if ($where) {
            $objDB = \Database::getInstance();

            $strTableName  = $values->getPropertyValue('select_table');
            $strColNameId  = $values->getPropertyValue('select_id');
            $strSortColumn = $values->getPropertyValue('select_sorting') ?: $strColNameId;

            $query = sprintf(
                'SELECT %1$s.*
                FROM %1$s%2$s
                ORDER BY %1$s.%3$s',
                // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                $strTableName,                            // 1
                ($where ? ' WHERE ('.$where.')' : false), // 2
                $strSortColumn                            // 3
            // @codingStandardsIgnoreEnd
            );

            try {
                $objDB
                    ->prepare($query)
                    ->execute();
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    sprintf(
                        '%s %s',
                        $GLOBALS['TL_LANG']['tl_metamodel_attribute']['sql_error'],
                        $e->getMessage()
                    )
                );
            }
        }
    }
}
