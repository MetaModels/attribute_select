<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2020 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Martin Treml <github@r2pi.net>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright  2012-2020 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\EncodePropertyValueFromWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionChainInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\NotCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyConditionChain;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyValueCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\PropertyInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use MetaModels\DcGeneral\DataDefinition\Palette\Condition\Property\ConditionTableNameIsMetaModel;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\IFactory;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Handle events for tl_metamodel_attribute.alias_fields.attr_id.
 */
class BackendEventsListener
{
    /**
     * Request scope determinator.
     *
     * @var RequestScopeDeterminator
     */
    protected $scopeMatcher;

    /**
     * Database connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * MetaModels factory.
     *
     * @var IFactory
     */
    protected $factory;

    /**
     * Translator.
     *
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Filter setting factory.
     *
     * @var IFilterSettingFactory
     */
    protected $filterSettingFactory;

    /**
     * EventListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher         Request scope determinator.
     * @param Connection               $connection           Database connection.
     * @param IFactory                 $factory              MetaModels factory.
     * @param IFilterSettingFactory    $filterSettingFactory Filter setting factory.
     * @param TranslatorInterface      $translator           Translator.
     */
    public function __construct(
        RequestScopeDeterminator $scopeMatcher,
        Connection $connection,
        IFactory $factory,
        IFilterSettingFactory $filterSettingFactory,
        TranslatorInterface $translator
    ) {
        $this->scopeMatcher         = $scopeMatcher;
        $this->connection           = $connection;
        $this->factory              = $factory;
        $this->translator           = $translator;
        $this->filterSettingFactory = $filterSettingFactory;
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
        $result = [];
        $tables = $this->factory->collectNames();

        foreach ($tables as $table) {
            $metaModel = $this->factory->getMetaModel($table);
            if ($metaModel->isTranslated()) {
                $result[$keyTranslated][$table] = \sprintf('%s (%s)', $metaModel->get('name'), $table);
            } else {
                $result[$keyUntranslated][$table] = \sprintf('%s (%s)', $metaModel->get('name'), $table);
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
        $sqlTable     = $this->translator->trans(
            'tl_metamodel_attribute.select_table_type.sql-table',
            [],
            'contao_tl_metamodel_attribute'
        );
        $translated   = $this->translator->trans(
            'tl_metamodel_attribute.select_table_type.translated',
            [],
            'contao_tl_metamodel_attribute'
        );
        $untranslated = $this->translator->trans(
            'tl_metamodel_attribute.select_table_type.untranslated',
            [],
            'contao_tl_metamodel_attribute'
        );

        $result = $this->getMetaModelTableNames($translated, $untranslated);

        foreach ($this->connection->getSchemaManager()->listTableNames() as $table) {
            if (0 !== strpos($table, 'mm_')) {
                $result[$sqlTable][$table] = $table;
            }
        }

        if (\is_array($result[$translated])) {
            \asort($result[$translated]);
        }

        if (\is_array($result[$untranslated])) {
            \asort($result[$untranslated]);
        }

        if (\is_array($result[$sqlTable])) {
            \asort($result[$sqlTable]);
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
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

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
        $metaModel = $this->factory->getMetaModel($metaModelName);
        $result    = [];

        if (empty($metaModel)) {
            return $result;
        }

        foreach ($metaModel->getAttributes() as $attribute) {
            $name   = $attribute->getName();
            $column = $attribute->getColName();
            $type   = $attribute->get('type');

            $result[$column] = \sprintf('%s [%s, "%s"]', $name, $type, $column);
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
        return (!empty($table) && $this->connection->getSchemaManager()->tablesExist([$table]));
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
        if (!$this->tableExists($tableName)) {
            return [];
        }

        $result    = [];
        $fieldList = $this->connection->getSchemaManager()->listTableColumns($tableName);

        foreach ($fieldList as $column) {
            if (($typeFilter === null) || \in_array($column->getType()->getName(), $typeFilter)) {
                $result[$column->getName()] = $column->getName();
            }
        }

        if (!empty($result)) {
            \asort($result);
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
        if (0 === strpos($table, 'mm_')) {
            $attributes = $this->getAttributeNamesFrom($table);
            \asort($attributes);

            $sql = $this->translator->trans(
                'tl_metamodel_attribute.select_column_type.sql',
                [],
                'contao_tl_metamodel_attribute'
            );

            $attribute = $this->translator->trans(
                'tl_metamodel_attribute.select_column_type.attribute',
                [],
                'contao_tl_metamodel_attribute'
            );

            return
                [
                    $sql       => \array_diff_key(
                        $this->getColumnNamesFromMetaModel($table),
                        \array_flip(array_keys($attributes))
                    ),
                    $attribute => $attributes,
                ];
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
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

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
            \asort($result);
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
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'select_filter')
        ) {
            return;
        }

        $model     = $event->getModel();
        $metaModel = $this->factory->getMetaModel($model->getProperty('select_table'));

        if ($metaModel) {
            $statement = $this->connection
                ->prepare('SELECT id,name FROM tl_metamodel_filter WHERE pid=:pid ORDER BY name');

            $statement->execute(['pid' => $metaModel->get('id')]);

            $result = [];
            while ($row = $statement->fetch(\PDO::FETCH_OBJ)) {
                /** @noinspection PhpUndefinedFieldInspection */
                $result[$row->id] = $row->name;
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
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

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
        $filterSettings = $this->filterSettingFactory->createCollection($filterId);
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
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'select_id')
        ) {
            return;
        }

        $result = $this->getColumnNamesFromMetaModel(
            $event->getModel()->getProperty('select_table'),
            [Type::INTEGER, Type::BIGINT, Type::SMALLINT]
        );

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
                $currentCondition = new PropertyConditionChain([$condition]);
            } else {
                $currentCondition = new PropertyConditionChain([$currentCondition, $condition]);
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
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

        foreach ($palettes->getPalettes() as $palette) {
            foreach ($propertyNames as $propertyName => $mask) {
                foreach ($palette->getProperties() as $property) {
                    if ($property->getName() === $propertyName) {
                        // Show the widget when we are editing a select attribute.
                        $condition = new PropertyConditionChain(
                            [
                                new PropertyConditionChain(
                                    [
                                        new PropertyValueCondition('type', 'select'),
                                        new ConditionTableNameIsMetaModel('select_table', $mask)
                                    ]
                                )
                            ],
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
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

        if ($event->getContainer()->getName() !== 'tl_metamodel_attribute') {
            return;
        }

        $this->buildConditions(
            [
                'select_id'           => false,
                'select_where'        => false,
                'select_filter'       => true,
                'select_filterparams' => true,
            ],
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
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getProperty() !== 'select_where')
        ) {
            return;
        }

        $where  = $event->getValue();
        $values = $event->getPropertyValueBag();

        if ($where) {
            $query = $this->connection->createQueryBuilder()
                ->select('sourceTable.*')
                ->from($values->getPropertyValue('select_table'), 'sourceTable')
                ->where($where)
                ->orderBy('sourceTable.' . ($values->getPropertyValue('select_sorting')
                        ?: $values->getPropertyValue('select_id')));

            try {
                $query->execute();
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    \sprintf(
                        '%s %s',
                        $this->translator->trans('sql_error', [], 'contao_tl_metamodel_attribute'),
                        $e->getMessage()
                    )
                );
            }
        }
    }
}
