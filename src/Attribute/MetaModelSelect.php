<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2022 The MetaModels team.
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
 * @author     David Maack <david.maack@arcor.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\Attribute;

use Contao\System;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAliasConverter;
use MetaModels\Attribute\ITranslated;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SearchAttribute;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\Helper\TableManipulator;
use MetaModels\IFactory;
use MetaModels\IItem;
use MetaModels\IItems;
use MetaModels\IMetaModel;
use MetaModels\ITranslatedMetaModel;
use MetaModels\Render\Template;

/**
 * This is the MetaModelAttribute class for handling select attributes on MetaModels.
 */
class MetaModelSelect extends AbstractSelect implements IAliasConverter
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
     * @param array                      $arrData              The information array, for attribute information, refer
     *                                                         to documentation of table tl_metamodel_attribute and
     *                                                         documentation of the certain attribute classes for
     *                                                         information what values are understood.
     * @param Connection|null            $connection           The database connection.
     * @param TableManipulator|null      $tableManipulator     Table manipulator instance.
     * @param IFactory|null              $factory              MetaModel factory.
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
            // @codingStandardsIgnoreStart Silencing errors is discouraged
            @trigger_error(
                'Factory is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardIgnoreEnd
            $factory = System::getContainer()->get('metamodels.factory');
        }

        if (null === $filterSettingFactory) {
            // @codingStandardsIgnoreStart Silencing errors is discouraged
            @trigger_error(
                'Filter setting factory is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardIgnoreEnd
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
        return parent::checkConfiguration() && (null !== $this->getSelectMetaModel());
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
        return \array_merge(
            parent::getAttributeSettingNames(),
            [
                'select_filter',
                'select_filterparams',
            ]
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
        $values = [];
        foreach ($items as $item) {
            /** @var IItem $item */
            $valueId    = $item->get('id');
            $parsedItem = $item->parseValue();

            $values[$valueId] = \array_merge(
                [self::SELECT_RAW => $parsedItem['raw']],
                $parsedItem['text']
            );
        }

        return $values;
    }

    /**
     * Retrieve the values with the given ids.
     *
     * @param string[] $valueIds The ids of the values to retrieve.
     * @param array    $attrOnly The attribute names to obtain.
     *
     * @return array
     */
    protected function getValuesById($valueIds, $attrOnly = [])
    {
        $recursionKey = $this->getMetaModel()->getTableName();

        // Prevent recursion.
        static $tables = [];
        if (isset($tables[$recursionKey])) {
            return [];
        }
        $tables[$recursionKey] = $recursionKey;

        $metaModel = $this->getSelectMetaModel();

        try {
            $parent = $this->getMetaModel();

            if ($metaModel instanceof ITranslatedMetaModel && $parent instanceof ITranslatedMetaModel) {
                $currentLanguage  = $parent->getLanguage();
                $previousLanguage = $metaModel->selectLanguage($currentLanguage);
            } elseif ($metaModel instanceof ITranslatedMetaModel) {
                $previousLanguage = $metaModel->selectLanguage($metaModel->getMainLanguage());
            }

            $filter = $metaModel->getEmptyFilter();
            $this->buildFilterRulesForFilterSetting($filter);
            $filter->addFilterRule(new StaticIdList($valueIds));

            $items  =
                $metaModel->findByFilter(
                    $filter,
                    $this->getSortingColumn(),
                    0,
                    0,
                    $this->getSortDirection(),
                    $attrOnly
                );
        } finally {
            if (isset($previousLanguage)) {
                $metaModel->selectLanguage($previousLanguage);
            }
        }

        unset($tables[$recursionKey]);

        return $this->itemsToValues($items);
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        $aliasColumn = $this->getAliasColumn();

        if (null !== ($widgetValue = ($varValue[$aliasColumn] ?? null))) {
            return (string) $widgetValue;
        }

        if (null !== ($widgetValue = ($varValue[self::SELECT_RAW][$aliasColumn] ?? null))) {
            return (string) $widgetValue;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException When the value is invalid.
     */
    public function widgetToValue($varValue, $itemId)
    {
        if (null === $varValue) {
            return null;
        }
        static $cache = [];
        $attributeId = $this->get('id');
        if (\array_key_exists($attributeId, $cache) && \array_key_exists($varValue, $cache[$attributeId])) {
            return $cache[$attributeId][$varValue];
        }

        $model = $this->getSelectMetaModel();
        $alias = $this->getAliasColumn();

        if ($model->hasAttribute($alias)) {
            $attribute = $model->getAttribute($alias);
            // It is an attribute, we may search for it.

            if ($attribute instanceof ITranslated) {
                $languages = [];
                $metaModel = $this->getMetaModel();
                if ($metaModel instanceof ITranslatedMetaModel) {
                    $languages[] = $metaModel->getLanguage();
                } elseif ($metaModel->isTranslated(false)) {
                    $languages[] = $metaModel->getActiveLanguage();
                }

                $relatedModel = $attribute->getMetaModel();
                if ($relatedModel instanceof ITranslatedMetaModel) {
                    $languages[] = $relatedModel->getMainLanguage();
                } elseif ($relatedModel->isTranslated(false)) {
                    $languages[] = $metaModel->getActiveLanguage();
                } else {
                    throw new \LogicException('Translated attribute within untranslated MetaModel?!?');
                }

                $ids = $attribute->searchForInLanguages($varValue, $languages);
            } else {
                $ids = $attribute->searchFor($varValue);
            }
        } else {
            // Must be a system column then.
            $result = $this->connection->createQueryBuilder()
                ->select('v.id')
                ->from($this->getSelectSource(), 'v')
                ->where('v.' . $alias . '=:value')
                ->setParameter('value', $varValue)
                ->executeQuery();

            $ids = $result->fetchFirstColumn();
        }

        // Maybe deleted value?
        if ([] === $ids) {
            return $cache[$attributeId][$varValue] = null;
        }

        // Multiple results.
        if (null === $ids || \count($ids) > 1) {
            throw new \RuntimeException(
                \sprintf(
                    'Multiple values found for %s, are there obsolete values for %s.%s (att_id: %s)?',
                    \var_export($varValue, true),
                    $model->getTableName(),
                    $this->getColName(),
                    $this->get('id')
                )
            );
        }
        $valueId = \array_shift($ids);

        $value = $this->getValuesById(
            [$valueId],
            [$this->getAliasColumn(), $this->getValueColumn(), $this->getIdColumn(), $this->getSortingColumn()]
        );

        return $cache[$attributeId][$varValue] = $value[$valueId];
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
            return [];
        }

        $metaModel    = $this->getMetaModel(); // Model of the attribute.
        $relatedModel = $this->getSelectMetaModel(); // Model to get the options from.

        // Check if the current MM has translations.
        $originalLanguage = null;
        $targetLanguage   = null;
        if ($metaModel instanceof ITranslatedMetaModel) {
            $targetLanguage = $this->getMetaModel()->getLanguage();
        } elseif ($metaModel->isTranslated(false)) {
            $targetLanguage = $metaModel->getActiveLanguage();
        } elseif ($relatedModel instanceof ITranslatedMetaModel) {
            $targetLanguage = $relatedModel->getMainLanguage();
        } elseif ($relatedModel->isTranslated(false)) {
            $targetLanguage = $relatedModel->getFallbackLanguage();
        }


        // Retrieve original language only if target language is set.
        if ($targetLanguage) {
            if ($relatedModel instanceof ITranslatedMetaModel) {
                $originalLanguage = $relatedModel->selectLanguage($targetLanguage);
            } elseif ($relatedModel->isTranslated(false)) {
                $originalLanguage       = \str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $this->getMetaModel()->getActiveLanguage());
            }
        }

        $filter = $this->getSelectMetaModel()->getEmptyFilter();

        $this->buildFilterRulesForFilterSetting($filter);

        $objItems = $this->getSelectMetaModel()->findByFilter(
            $filter,
            $this->getSortingColumn(),
            0,
            0,
            $this->getSortDirection(),
            [$this->getValueColumn(), $this->getAliasColumn()]
        );

        if (isset($originalLanguage)) {
            if ($relatedModel instanceof ITranslatedMetaModel) {
                $relatedModel->selectLanguage($originalLanguage);
            } else {
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $originalLanguage);
            }
        }

        return $this->convertItemsToFilterOptions($objItems, $this->getValueColumn(), $this->getAliasColumn());
    }

    /**
     * Fetch filter options from foreign table taking the given flag into account.
     *
     * @param IFilter $filter The filter to which the rules shall be added to.
     * @param array   $idList The list of ids of items for which the rules shall be added.
     *
     * @return void
     */
    public function buildFilterRulesForUsedOnly($filter, $idList = [])
    {
        $builder = $this->connection->createQueryBuilder()
            ->select('t.' . $this->getColName())
            ->from($this->getMetaModel()->getTableName(), 't')
            ->groupBy('t.' . $this->getColName());

        if (!empty($idList)) {
            $builder
                ->where('t.id IN (:ids)')
                ->setParameter('ids', $idList, Connection::PARAM_STR_ARRAY);
        }

        $arrUsedValues = $builder->execute()->fetchFirstColumn();
        $arrUsedValues = \array_filter(
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
            $filterParams = \array_keys($filterSettings->getParameterFilterNames());
            $processed    = [];

            // We have to use all the preset values we want first.
            foreach ($presets as $presetName => $preset) {
                if (\in_array($presetName, $presetNames)) {
                    $processed[$presetName] = $preset['value'];
                }
            }

            // Now we have to use all FrontEnd filter params, that are either:
            // * not contained within the presets
            // * or are overridable.
            foreach ($filterParams as $parameter) {
                // Unknown parameter? - next please.
                if (!\array_key_exists($parameter, $values)) {
                    continue;
                }

                // Not a preset or allowed to override? - use value.
                if ((!\array_key_exists($parameter, $presets)) || $presets[$parameter]['use_get']) {
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
     * @param string         $displayValue The name of the attribute to use as value.
     * @param string         $aliasColumn  The name of the attribute to use as alias.
     * @param null|string[]  $count        The counter array.
     * @param null|array     $idList       A list for the current Items to use.
     *
     * @return array
     */
    protected function convertItemsToFilterOptions($items, $displayValue, $aliasColumn, &$count = null, $idList = null)
    {
        if (null !== $count) {
            $this->determineCount($items, $count, $idList);
        }

        $result = [];
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
     * @param IItems|IItem[] $items  The item collection to convert.
     * @param null|string[]  $count  The counter array.
     * @param array          $idList The id list for the subselect.
     *
     * @return void
     */
    private function determineCount($items, &$count, $idList)
    {
        $usedOptionsIdList = \array_unique(
            \array_filter(
                \array_map(
                    function ($item) {
                        /** @var IItem $item */
                        return $item->get('id');
                    },
                    \iterator_to_array($items)
                )
            )
        );

        if (empty($usedOptionsIdList)) {
            return;
        }

        $valueCol = $this->getColName();
        $query    = $this->connection->createQueryBuilder()
            ->select('t.' . $this->getColName())
            ->addSelect(\sprintf('COUNT(t.%s) AS count', $this->getColName()))
            ->from($this->getMetaModel()->getTableName(), 't')
            ->where('t.' . $this->getColName() . ' IN (:ids)')
            ->groupBy('t.' . $this->getColName())
            ->setParameter('ids', $usedOptionsIdList, Connection::PARAM_STR_ARRAY);
        if ($idList !== null && !empty($idList)) {
            $query
                ->andWhere('t.id IN (:idList)')
                ->setParameter('idList', $idList, Connection::PARAM_STR_ARRAY);
        }
        $query = $query->execute();

        while ($row = $query->fetchAssociative()) {
            $count[$row[$valueCol]] = $row['count'];
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
            return [];
        }

        $strDisplayValue    = $this->getValueColumn();
        $strSortingValue    = $this->getSortingColumn();
        $strCurrentLanguage = null;

        $metaModel = $this->getSelectMetaModel();

        // Change language.
        if (TL_MODE == 'BE' && !$metaModel instanceof ITranslatedMetaModel) {
            $strCurrentLanguage     = \str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
            $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $this->getMetaModel()->getActiveLanguage());
        }

        $filter = $this->getSelectMetaModel()->getEmptyFilter();

        $this->buildFilterRulesForFilterSetting($filter);

        // Add some more filter rules.
        if ($usedOnly || ($idList && \is_array($idList))) {
            $this->buildFilterRulesForUsedOnly($filter, $idList ?: []);
        }

        $objItems = $this->getSelectMetaModel()->findByFilter($filter, $strSortingValue);

        // Reset language.
        if (TL_MODE == 'BE' && isset($strCurrentLanguage)) {
            $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $strCurrentLanguage);
        }

        return $this->convertItemsToFilterOptions(
            $objItems,
            $strDisplayValue,
            $this->getAliasColumn(),
            $arrCount,
            $idList
        );
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
            ->select('t.id, t.' . $myColName)
            ->from($this->getMetaModel()->getTableName(), 't')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $idList, Connection::PARAM_STR_ARRAY)
            ->execute();

        $valueIds = [];
        $valueMap = [];
        while ($values = $statement->fetchAssociative()) {
            $itemId             = $values['id'];
            $value              = $values[$myColName];
            $valueIds[$itemId]  = $value;
            $valueMap[$value][] = $itemId;
        }

        $filter =
            $metaModel->getEmptyFilter()->addFilterRule(new StaticIdList(\array_unique(\array_values($valueIds))));
        $value  = $this->getValueColumn();
        $items  = $metaModel->findByFilter($filter, $value, 0, 0, $strDirection, [$value]);
        $result = [];
        foreach ($items as $item) {
            $result = \array_merge($result, $valueMap[$item->get('id')]);
        }

        $diff = \array_diff($idList, $result);

        return \array_merge($result, $diff);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFor($arrIds)
    {
        if (!$this->isProperlyConfigured()) {
            return [];
        }

        $result      = [];
        $valueColumn = $this->getColName();
        // First pass, load database rows.
        $statement = $this->connection->createQueryBuilder()
            ->select('t.' . $valueColumn . ', t.id')
            ->from($this->getMetaModel()->getTableName(), 't')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $arrIds, Connection::PARAM_STR_ARRAY)
            ->execute();

        $valueIds = [];
        while ($rows = $statement->fetchAssociative()) {
            /** @noinspection PhpUndefinedFieldInspection */
            $valueIds[$rows['id']] = $rows[$valueColumn];
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

        $query = \sprintf(
        // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
            'UPDATE %1$s SET %1$s.%2$s=:val WHERE %1$s.id=:id',
            $this->getMetaModel()->getTableName(), // 1
            $this->getColName()                    // 2
        // @codingStandardsIgnoreEnd
        );

        foreach ($arrValues as $itemId => $value) {
            if (\is_array($value) && isset($value[self::SELECT_RAW]['id'])) {
                $this->connection->prepare($query)
                    ->execute(['val' => (int) $value[self::SELECT_RAW]['id'], 'id' => $itemId]);
            } elseif (\is_numeric($itemId) && (\is_numeric($value) || $value === null)) {
                $this->connection->prepare($query)->execute(['val' => (int) $value, 'id' => $itemId]);
            } else {
                throw new \RuntimeException(
                    'Invalid values encountered, itemId: ' .
                    \var_export($value, true) .
                    ' value: ' . \var_export($value, true)
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

        $metaModel    = $this->getMetaModel();
        $relatedModel = $this->getSelectMetaModel();
        $attribute    = $relatedModel->getAttribute($strColNameAlias);
        if (!$attribute) {
            // If not an attribute, perform plain SQL translation. See #32, 34.
            return parent::convertValuesToValueIds($values);
        }

        // Check if the current MM has translations.
        $originalLanguage = null;
        $targetLanguage   = null;
        if ($metaModel instanceof ITranslatedMetaModel) {
            $targetLanguage = $this->getMetaModel()->getLanguage();
        } elseif ($metaModel->isTranslated(false)) {
            $targetLanguage = $metaModel->getActiveLanguage();
        }

        // Retrieve original language only if target language is set.
        if ($targetLanguage) {
            if ($relatedModel instanceof ITranslatedMetaModel) {
                $originalLanguage = $relatedModel->selectLanguage($targetLanguage);
            } elseif ($relatedModel->isTranslated(false)) {
                $originalLanguage       = \str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $targetLanguage);
            }
        }

        $sanitizedValues = [];
        foreach ($values as $value) {
            $valueIds = $attribute->searchFor($value);
            if ($valueIds === null) {
                return null;
            }

            $sanitizedValues = \array_merge($valueIds, $sanitizedValues);
        }

        if (isset($originalLanguage)) {
            if ($relatedModel instanceof ITranslatedMetaModel) {
                $relatedModel->selectLanguage($originalLanguage);
            } else {
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $originalLanguage);
            }
        }

        return \array_unique($sanitizedValues);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdForAlias(string $alias, string $language): ?string
    {
        if (!$this->isProperlyConfigured()) {
            return null;
        }

        $aliasColumn  = $this->getAliasColumn();
        $relatedModel = $this->getSelectMetaModel();

        // Check first, if alias column a system column.
        if (!$relatedModel->hasAttribute($aliasColumn)) {
            $result  = $this->connection->createQueryBuilder()
                ->select('t.id')
                ->from($this->getSelectSource(), 't')
                ->where('t.' . $aliasColumn . '=:value')
                ->setParameter('value', $alias)
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->execute();
            $idValue = $result->fetchOne();

            return ($idValue === false) ? null : (string) $idValue;
        }

        // Check if the current MM has translations.
        $currentLanguage    = null;
        $supportedLanguages = null;

        if ($relatedModel instanceof ITranslatedMetaModel) {
            $supportedLanguages = $relatedModel->getLanguages();
            $fallbackLanguage   = $relatedModel->getMainLanguage();
        } elseif ($relatedModel->isTranslated(false)) {
            $backendLanguage    = \str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
            $supportedLanguages = $relatedModel->getAvailableLanguages();
            $fallbackLanguage   = $relatedModel->getFallbackLanguage() ?? $backendLanguage;
        }

        if (\is_array($supportedLanguages) && !empty($supportedLanguages)) {
            if (\in_array($language, $supportedLanguages, false)) {
                $currentLanguage = $language;
            } else {
                $currentLanguage = $fallbackLanguage;
            }
        }

        // Retrieve original language only if target language is set.
        if ($currentLanguage) {
            if ($relatedModel instanceof ITranslatedMetaModel) {
                $relatedModel->selectLanguage($language);
            } elseif ($relatedModel->isTranslated(false)) {
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $language);
            }
        }

        // Find the alias in the related metamodels, if there is no found return null.
        // On more than one result return the first one.
        $filter = $relatedModel->getEmptyFilter();
        $filter->addFilterRule(new SearchAttribute($relatedModel->getAttribute($aliasColumn), $alias));
        $items = $relatedModel->findByFilter($filter);

        if ($currentLanguage) {
            if ($relatedModel instanceof ITranslatedMetaModel) {
                $relatedModel->selectLanguage($currentLanguage);
            } elseif ($relatedModel->isTranslated(false)) {
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $currentLanguage);
            }
        }

        if ($items->getCount() == 0) {
            return null;
        }

        return $items->first()->current()->get('id');
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasForId(string $id, string $language): ?string
    {
        if (!$this->isProperlyConfigured()) {
            return null;
        }

        // Check if the current MM has translations.
        $aliasColumn        = $this->getAliasColumn();
        $relatedModel       = $this->getSelectMetaModel();
        $currentLanguage    = null;
        $supportedLanguages = null;
        $fallbackLanguage   = null;

        if ($relatedModel instanceof ITranslatedMetaModel) {
            $supportedLanguages = $relatedModel->getLanguages();
            $fallbackLanguage   = $relatedModel->getMainLanguage();
        } elseif ($relatedModel->isTranslated(false)) {
            $backendLanguage    = \str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
            $supportedLanguages = $relatedModel->getAvailableLanguages();
            $fallbackLanguage   = $relatedModel->getFallbackLanguage() ?? $backendLanguage;
        }

        if (\is_array($supportedLanguages) && !empty($supportedLanguages)) {
            if (\in_array($language, $supportedLanguages, false)) {
                $currentLanguage = $language;
            } else {
                $currentLanguage = $fallbackLanguage;
            }
        }

        // Retrieve original language only if target language is set.
        if ($currentLanguage) {
            if ($relatedModel instanceof ITranslatedMetaModel) {
                $relatedModel->selectLanguage($language);
            } elseif ($relatedModel->isTranslated(false)) {
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $language);
            }
        }

        $item = $relatedModel->findById($id, [$aliasColumn]);
        if ($item === null) {
            return null;
        }

        if ($currentLanguage) {
            if ($relatedModel instanceof ITranslatedMetaModel) {
                $relatedModel->selectLanguage($currentLanguage);
            } elseif ($relatedModel->isTranslated(false)) {
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $currentLanguage);
            }
        }

        return $item->parseAttribute($aliasColumn)['text'] ?? null;
    }
}
