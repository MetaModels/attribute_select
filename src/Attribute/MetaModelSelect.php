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
 * @author     Stefan heimes <stefan_heimes@hotmail.com>
 * @author     Martin Treml <github@r2pi.net>
 * @author     David Maack <david.maack@arcor.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\Attribute;

use Contao\System;
use Doctrine\DBAL\Connection;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\Helper\TableManipulator;
use MetaModels\IFactory;
use MetaModels\IItem;
use MetaModels\IItems;
use MetaModels\IMetaModel;
use MetaModels\Render\Template;

/**
 * This is the MetaModelAttribute class for handling select attributes on MetaModels.
 */
class MetaModelSelect extends AbstractSelect
{
    /**
     * The key in the result array where the RAW values shall be stored.
     */
    const SELECT_RAW = '__SELECT_RAW__';

    /**
     * The MetaModel we are referencing on.
     *
     * @var IMetaModel
     */
    protected $objSelectMetaModel;

    /**
     * MetaModel factory.
     *
     * @var IFactory
     */
    private $factory;

    /**
     * Filter setting factory.
     *
     * @var IFilterSettingFactory
     */
    private $filterSettingFactory;

    /**
     * Instantiate an MetaModel attribute.
     *
     * Note that you should not use this directly but use the factory classes to instantiate attributes.
     *
     * @param IMetaModel                 $objMetaModel         The MetaModel instance this attribute belongs to.
     *
     * @param array $arrData                                   The information array, for attribute information, refer
     *                                                         to documentation of table tl_metamodel_attribute and
     *                                                         documentation of the certain attribute classes for
     *                                                         information what values are understood.
     *
     * @param Connection                 $connection           The database connection.
     *
     * @param TableManipulator           $tableManipulator     Table manipulator instance.
     *
     * @param IFactory                   $factory              MetaModel factory.
     *
     * @param IFilterSettingFactory|null $filterSettingFactory Filter setting factory.
     */
    public function __construct(
        IMetaModel $objMetaModel,
        array $arrData = [],
        Connection $connection = null,
        TableManipulator $tableManipulator = null,
        IFactory $factory = null,
        IFilterSettingFactory $filterSettingFactory = null
    ) {
        parent::__construct($objMetaModel, $arrData, $connection, $tableManipulator);

        if (null === $factory) {
            @trigger_error(
                'Factory is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            $factory = System::getContainer()->get('metamodels.factory');
        }

        if (null === $filterSettingFactory) {
            @trigger_error(
                'Filter setting factory is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            $filterSettingFactory = System::getContainer()->get('metamodels.filter_setting_factory');
        }

        $this->factory              = $factory;
        $this->filterSettingFactory = $filterSettingFactory;
    }

    /**
     * {@inheritDoc}
     */
    protected function checkConfiguration()
    {
        return parent::checkConfiguration()
            && (null !== $this->getSelectMetaModel());
    }

    /**
     * Retrieve the linked MetaModel instance.
     *
     * @return IMetaModel
     */
    protected function getSelectMetaModel()
    {
        if (empty($this->objSelectMetaModel)) {
            $this->objSelectMetaModel = $this->factory->getMetaModel($this->getSelectSource());
        }

        return $this->objSelectMetaModel;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(Template $objTemplate, $arrRowData, $objSettings)
    {
        parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);
        /** @noinspection PhpUndefinedFieldInspection */
        $objTemplate->displayValue = $this->getValueColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(
            parent::getAttributeSettingNames(),
            array(
                'select_filter',
                'select_filterparams',
            )
        );
    }

    /**
     * Convert the item list to values.
     *
     * @param IItems $items The items to convert.
     *
     * @return array
     */
    protected function itemsToValues(IItems $items)
    {
        $values = array();
        foreach ($items as $item) {
            /** @var IItem $item */
            $valueId    = $item->get('id');
            $parsedItem = $item->parseValue();

            $values[$valueId] = array_merge(
                array(self::SELECT_RAW => $parsedItem['raw']),
                $parsedItem['text']
            );
        }

        return $values;
    }

    /**
     * Retrieve the values with the given ids.
     *
     * @param string[] $valueIds The ids of the values to retrieve.
     *
     * @return array
     */
    protected function getValuesById($valueIds)
    {
        $recursionKey = $this->getMetaModel()->getTableName();

        // Prevent recursion.
        static $tables = array();
        if (isset($tables[$recursionKey])) {
            return array();
        }
        $tables[$recursionKey] = $recursionKey;

        $metaModel = $this->getSelectMetaModel();
        $filter    = $metaModel->getEmptyFilter()->addFilterRule(new StaticIdList($valueIds));
        $items     = $metaModel->findByFilter($filter, 'id');
        unset($tables[$recursionKey]);

        return $this->itemsToValues($items);
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        if (isset($varValue[$this->getIdColumn()])) {
            // Hope the best that this is unique...
            return (string) $varValue[$this->getIdColumn()];
        }

        if (isset($varValue[self::SELECT_RAW]['id'])) {
            return (string) $varValue[self::SELECT_RAW]['id'];
        }

        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException When the value is invalid.
     */
    public function widgetToValue($varValue, $itemId)
    {
        $model     = $this->getSelectMetaModel();
        $alias     = $this->getIdColumn();
        $attribute = $model->getAttribute($alias);

        if ($attribute) {
            // It is an attribute, we may search for it.
            $ids = $attribute->searchFor($varValue);
            if (!$ids) {
                $valueId = 0;
            } else {
                if (count($ids) > 1) {
                    throw new \RuntimeException(
                        sprintf(
                            'Multiple values found for %s, are there obsolete values for %s.%s (att_id: %s)?',
                            var_export($varValue, true),
                            $model->getTableName(),
                            $this->getColName(),
                            $this->get('id')
                        )
                    );
                }
                $valueId = array_shift($ids);
            }
        } else {
            // Must be a system column then.
            // Special case first, the id is our alias, easy way out.
            if ($alias === 'id') {
                $valueId = $varValue;
            } else {
                $result = $this->connection->createQueryBuilder()
                    ->select('v.id')
                    ->from($this->getSelectSource(), 'v')
                    ->where('v.' . $this->getAliasColumn() . '=:value')
                    ->setParameter('value', $varValue)
                    ->execute();

                $valueId = $result->fetch(\PDO::FETCH_COLUMN);

                /** @noinspection PhpUndefinedFieldInspection */
                if ($valueId === false) {
                    throw new \RuntimeException('Could not translate value ' . var_export($varValue, true));
                }
            }
        }

        $value = $this->getValuesById(array($valueId));

        return $value[$valueId];
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getFilterOptionsForDcGeneral()
    {
        if (!$this->isFilterOptionRetrievingPossible(null)) {
            return array();
        }

        $originalLanguage       = $GLOBALS['TL_LANGUAGE'];
        $GLOBALS['TL_LANGUAGE'] = $this->getMetaModel()->getActiveLanguage();

        $filter = $this->getSelectMetaModel()->getEmptyFilter();

        $this->buildFilterRulesForFilterSetting($filter);

        $objItems = $this->getSelectMetaModel()->findByFilter($filter, $this->getSortingColumn());

        $GLOBALS['TL_LANGUAGE'] = $originalLanguage;

        return $this->convertItemsToFilterOptions($objItems, $this->getValueColumn(), $this->getIdColumn());
    }

    /**
     * Fetch filter options from foreign table taking the given flag into account.
     *
     * @param IFilter $filter The filter to which the rules shall be added to.
     *
     * @param array   $idList The list of ids of items for which the rules shall be added.
     *
     * @return void
     */
    public function buildFilterRulesForUsedOnly($filter, $idList = array())
    {
        $builder = $this->connection->createQueryBuilder()
            ->select($this->getColName())
            ->from($this->getMetaModel()->getTableName())
            ->groupBy($this->getColName());

        if (!empty($idList)) {
            $builder
                ->where('id IN (:ids)')
                ->setParameter('ids', $idList, Connection::PARAM_INT_ARRAY);
        }

        $arrUsedValues = $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);
        $arrUsedValues = array_filter(
            $arrUsedValues,
            function ($value) {
                return !empty($value);
            }
        );

        $filter->addFilterRule(new StaticIdList($arrUsedValues));
    }

    /**
     * Fetch filter options from foreign table taking the given flag into account.
     *
     * @param IFilter $filter The filter to which the rules shall be added to.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function buildFilterRulesForFilterSetting($filter)
    {
        if (!$this->get('select_filter')) {
            return;
        }

        // Set Filter and co.
        $filterSettings = $this->filterSettingFactory->createCollection($this->get('select_filter'));

        if ($filterSettings) {
            $values       = $_GET;
            $presets      = (array) $this->get('select_filterparams');
            $presetNames  = $filterSettings->getParameters();
            $filterParams = array_keys($filterSettings->getParameterFilterNames());
            $processed    = array();

            // We have to use all the preset values we want first.
            foreach ($presets as $presetName => $preset) {
                if (in_array($presetName, $presetNames)) {
                    $processed[$presetName] = $preset['value'];
                }
            }

            // Now we have to use all FrontEnd filter params, that are either:
            // * not contained within the presets
            // * or are overridable.
            foreach ($filterParams as $parameter) {
                // Unknown parameter? - next please.
                if (!array_key_exists($parameter, $values)) {
                    continue;
                }

                // Not a preset or allowed to override? - use value.
                if ((!array_key_exists($parameter, $presets)) || $presets[$parameter]['use_get']) {
                    $processed[$parameter] = $values[$parameter];
                }
            }

            $filterSettings->addRules($filter, $processed);
        }
    }

    /**
     * Convert a collection of items into a proper filter option list.
     *
     * @param IItems|IItem[] $items        The item collection to convert.
     *
     * @param string         $displayValue The name of the attribute to use as value.
     *
     * @param string         $aliasColumn  The name of the attribute to use as alias.
     *
     * @param null|string[]  $count        The counter array.
     *
     * @return array
     */
    protected function convertItemsToFilterOptions($items, $displayValue, $aliasColumn, &$count = null)
    {
        if (null !== $count) {
            $this->determineCount($items, $count);
        }

        $result = array();
        foreach ($items as $item) {
            $textValue  = $this->tryParseAttribute($displayValue, $item);
            $aliasValue = $this->tryParseAttribute($aliasColumn, $item);

            $result[$aliasValue] = $textValue;

            // Clean the count array if alias is different from id value.
            if (null !== $count && isset($count[$item->get('id')]) && $aliasValue !== $item->get('id')) {
                $count[$aliasValue] = $count[$item->get('id')];
                unset($count[$item->get('id')]);
            }
        }

        return $result;
    }

    /**
     * Parse a column as text or return the native value if that failed.
     *
     * @param string $displayValue The attribute to parse.
     * @param IITem  $item         The item to extract the value from.
     *
     * @return mixed
     */
    private function tryParseAttribute($displayValue, IItem $item)
    {
        $parsedValue = $item->parseAttribute($displayValue);
        if (isset($parsedValue['text'])) {
            return $parsedValue['text'];
        }

        return $item->get($displayValue);
    }

    /**
     * Determine the option count for the passed items.
     *
     * @param IItems|IItem[] $items The item collection to convert.
     *
     * @param null|string[]  $count The counter array.
     *
     * @return void
     */
    private function determineCount($items, &$count)
    {
        $idList = array_unique(array_filter(array_map(
            function ($item) {
                /** @var IItem $item */
                return $item->get('id');
            },
            iterator_to_array($items)
        )));

        if (empty($idList)) {
            return;
        }

        $valueCol = $this->getColName();
        $query    = $this->connection->createQueryBuilder()
            ->select($this->getColName())
            ->addSelect(sprintf('COUNT(%s) AS count', $this->getColName()))
            ->from($this->getMetaModel()->getTableName())
            ->where($this->getColName() . ' IN (:ids)')
            ->groupBy($this->getColName())
            ->setParameter('ids', $idList)
            ->execute();

        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $count[$row->{$valueCol}] = $row->count;
        }
    }

    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        if (!$this->isFilterOptionRetrievingPossible($idList)) {
            return array();
        }

        $strDisplayValue    = $this->getValueColumn();
        $strSortingValue    = $this->getSortingColumn();
        $strCurrentLanguage = null;

        // Change language.
        if (TL_MODE == 'BE') {
            $strCurrentLanguage     = $GLOBALS['TL_LANGUAGE'];
            $GLOBALS['TL_LANGUAGE'] = $this->getMetaModel()->getActiveLanguage();
        }

        $filter = $this->getSelectMetaModel()->getEmptyFilter();

        $this->buildFilterRulesForFilterSetting($filter);

        // Add some more filter rules.
        if ($usedOnly || ($idList && is_array($idList))) {
            $this->buildFilterRulesForUsedOnly($filter, $idList ?: array());
        }

        $objItems = $this->getSelectMetaModel()->findByFilter($filter, $strSortingValue);

        // Reset language.
        if (TL_MODE == 'BE') {
            $GLOBALS['TL_LANGUAGE'] = $strCurrentLanguage;
        }

        return $this->convertItemsToFilterOptions($objItems, $strDisplayValue, $this->getAliasColumn(), $arrCount);
    }

    /**
     * {@inheritdoc}
     *
     * This implementation does a complete sorting by the referenced MetaModel.
     */
    public function sortIds($idList, $strDirection)
    {
        $metaModel = $this->getSelectMetaModel();
        $myColName = $this->getColName();
        $statement = $this->connection->createQueryBuilder()
            ->select('id,' . $myColName)
            ->from($this->getMetaModel()->getTableName())
            ->where('id IN (:ids)')
            ->setParameter('ids', $idList, Connection::PARAM_INT_ARRAY)
            ->execute();

        $valueIds = array();
        $valueMap = array();
        while ($values = $statement->fetch(\PDO::FETCH_OBJ)) {
            $itemId             = $values->id;
            $value              = $values->$myColName;
            $valueIds[$itemId]  = $value;
            $valueMap[$value][] = $itemId;
        }

        $filter = $metaModel->getEmptyFilter()->addFilterRule(new StaticIdList(array_unique(array_values($valueIds))));
        $value  = $this->getValueColumn();
        $items  = $metaModel->findByFilter($filter, $value, 0, 0, $strDirection, array($value));
        $result = array();
        foreach ($items as $item) {
            $result = array_merge($result, $valueMap[$item->get('id')]);
        }

        $diff = array_diff($idList, $result);
        return array_merge($result, $diff);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFor($arrIds)
    {
        if (!$this->isProperlyConfigured()) {
            return array();
        }

        $result      = array();
        $valueColumn = $this->getColName();
        // First pass, load database rows.
        $statement  = $this->connection->createQueryBuilder()
            ->select($valueColumn . ', id')
            ->from($this->getMetaModel()->getTableName())
            ->where('id IN (:ids)')
            ->setParameter('ids', $arrIds)
            ->execute();

        $valueIds = array();
        while ($rows = $statement->fetch(\PDO::FETCH_OBJ)) {
            /** @noinspection PhpUndefinedFieldInspection */
            $valueIds[$rows->id] = $rows->$valueColumn;
        }

        $values = $this->getValuesById($valueIds);

        foreach ($valueIds as $itemId => $valueId) {
            if (empty($valueId)) {
                $result[$itemId] = null;
                continue;
            }
            $result[$itemId] = $values[$valueId];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException When invalid data is encountered.
     */
    public function setDataFor($arrValues)
    {
        if (!($this->getSelectSource() && $this->getValueColumn())) {
            return;
        }

        $query = sprintf(
        // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
            'UPDATE %1$s SET %2$s=:val WHERE %1$s.id=:id',
            $this->getMetaModel()->getTableName(), // 1
            $this->getColName()                    // 2
        // @codingStandardsIgnoreEnd
        );

        foreach ($arrValues as $itemId => $value) {
            if (is_array($value) && isset($value[self::SELECT_RAW]['id'])) {
                $this->connection->prepare($query)->execute(['val' => $value[self::SELECT_RAW]['id'], 'id' => $itemId]);
            } elseif (is_numeric($itemId) && (is_numeric($value) || $value === null)) {
                $this->connection->prepare($query)->execute(['val' => $value, 'id' => $itemId]);
            } else {
                throw new \RuntimeException(
                    'Invalid values encountered, itemId: ' .
                    var_export($value, true) .
                    ' value: ' . var_export($value, true)
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertValuesToValueIds($values)
    {
        $strColNameAlias = $this->getAliasColumn();
        $strColNameId    = $this->getIdColumn();

        if ($strColNameId === $strColNameAlias) {
            return $values;
        }

        $attribute = $this->getSelectMetaModel()->getAttribute($strColNameAlias);
        if (!$attribute) {
            // If not an attribute, perform plain SQL translation. See #32, 34.
            return parent::convertValuesToValueIds($values);
        }

        $sanitizedValues = array();
        foreach ($values as $value) {
            $valueIds = $attribute->searchFor($value);
            if ($valueIds === null) {
                return null;
            }

            $sanitizedValues = array_merge($valueIds, $sanitizedValues);
        }

        return array_unique($sanitizedValues);
    }
}
