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
 * @author      Christian de la Haye <service@delahaye.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\Select;

use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Filter\Setting\Factory as FilterSettingFactory;
use MetaModels\IItem;
use MetaModels\IItems;
use MetaModels\IMetaModel;
use MetaModels\Render\Template as MetaModelTemplate;
use MetaModels\Factory as MetaModelFactory;

/**
 * This is the MetaModelAttribute class for handling select attributes on MetaModels.
 *
 * @package    MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
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
     * Retrieve the linked MetaModel instance.
     *
     * @return IMetaModel
     */
    protected function getSelectMetaModel()
    {
        if (empty($this->objSelectMetaModel)) {
            $this->objSelectMetaModel = MetaModelFactory::byTableName($this->getSelectSource());
        }

        return $this->objSelectMetaModel;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(MetaModelTemplate $objTemplate, $arrRowData, $objSettings = null)
    {
        parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);

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
     * Retrieve the values with the given ids.
     *
     * @param int[] $valueIds The ids of the values to retrieve.
     *
     * @return array
     */
    protected function getValuesById($valueIds)
    {
        $metaModel = $this->getSelectMetaModel();
        $filter    = $metaModel->getEmptyFilter();
        $filter->addFilterRule(new StaticIdList($valueIds));

        $items  = $metaModel->findByFilter($filter, 'id');
        $values = array();
        foreach ($items as $item) {
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
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        if (isset($varValue[$this->getAliasColumn()])) {
            // Hope the best that this is unique...
            return $varValue[$this->getAliasColumn()];
        }

        if (isset($varValue[self::SELECT_RAW]['id'])) {
            return $varValue[self::SELECT_RAW]['id'];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException when the value is invalid.
     */
    public function widgetToValue($varValue, $intId)
    {
        $model     = $this->getSelectMetaModel();
        $alias     = $this->getAliasColumn();
        $attribute = $model->getAttribute($alias);

        if ($attribute) {
            // It is an attribute, we may search for it.
            $ids = $attribute->searchFor($varValue);
            if (!$ids) {
                $valueId = 0;
            } else {
                if (count($ids) > 1) {
                    throw new \RuntimeException('Multiple values found for ' . var_export($varValue, true));
                }
                $valueId = array_shift($ids);
            }
        } else {
            // Must be a system column then.
            // Special case first, the id is our alias, easy way out.
            if ($alias === 'id') {
                $valueId = $varValue;
            } else {
                $result = $this->getDatabase()
                    ->prepare(
                        sprintf(
                            'SELECT v.id FROM %1$s AS v WHERE v.%2$s=?',
                            $this->getSelectSource(),
                            $this->getAliasColumn()
                        )
                    )
                    ->execute($varValue);

                if (!$result->numRows) {
                    throw new \RuntimeException('Could not translate value ' . var_export($varValue, true));
                }
                $valueId = $result->id;
            }
        }

        $value = $this->getValuesById(array($valueId));

        return $value[$valueId];
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
        if (empty($idList)) {
            $query = sprintf(
            // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                'SELECT %2$s FROM %1$s GROUP BY %2$s',
                $this->getMetaModel()->getTableName(),  // 1
                $this->getColName()                     // 2
            // @codingStandardsIgnoreEnd
            );
        } else {
            $query = sprintf(
            // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                'SELECT %2$s FROM %1$s WHERE id IN (\'%3$s\') GROUP BY %2$s',
                $this->getMetaModel()->getTableName(),  // 1
                $this->getColName(),                    // 2
                implode("', '", $idList)                // 3
            // @codingStandardsIgnoreEnd
            );
        }

        $arrUsedValues = $this->getDatabase()
            ->prepare($query)
            ->execute()
            ->fetchEach($this->getColName());

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
        // Set Filter and co.
        $filterSettings = FilterSettingFactory::byId($this->get('select_filter'));
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
     * @param string         $displayValue The name of the attribute to use as value.
     * @param string         $aliasColumn  The name of the attribute to use as alias.
     *
     * @return array
     */
    protected function convertItemsToFilterOptions($items, $displayValue, $aliasColumn)
    {
        $result = array();
        foreach ($items as $item) {
            $parsed = $item->parseValue();

            $textValue  = $parsed['text'][$displayValue];
            $aliasValue = isset($parsed['text'][$aliasColumn])
                ? $parsed['text'][$aliasColumn]
                : $parsed['raw'][$aliasColumn];

            $result[$aliasValue] = $textValue;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getFilterOptions($arrIds, $usedOnly, &$arrCount = null)
    {
        $strDisplayValue = $this->get('select_column');
        $strSortingValue = $this->getSortingColumn();
        $aliasColumn     = $this->getAliasColumn();

        if (!($this->getSelectMetaModel() && $strDisplayValue)) {
            return array();
        }

        // Change language.
        if (TL_MODE == 'BE') {
            $strCurrentLanguage     = $GLOBALS['TL_LANGUAGE'];
            $GLOBALS['TL_LANGUAGE'] = $this->getMetaModel()->getActiveLanguage();
        }

        $filter = $this->getSelectMetaModel()->getEmptyFilter();

        $this->buildFilterRulesForFilterSetting($filter);

        // Add some more filter rules.
        if ($usedOnly) {
            $this->buildFilterRulesForUsedOnly($filter);

        } elseif ($arrIds && is_array($arrIds)) {
            $filter->addFilterRule(new StaticIdList($arrIds));
        }

        $objItems = $this->getSelectMetaModel()->findByFilter($filter, $strSortingValue);

        // Reset language.
        if (TL_MODE == 'BE') {
            $GLOBALS['TL_LANGUAGE'] = $strCurrentLanguage;
        }

        return $this->convertItemsToFilterOptions($objItems, $strDisplayValue, $aliasColumn);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFor($arrIds)
    {
        $displayValue = $this->getValueColumn();
        $result       = array();
        $metaModel    = $this->getSelectMetaModel();

        if ($this->getSelectSource() && $metaModel && $displayValue) {
            $valueColumn = $this->getColName();
            // First pass, load database rows.
            $rows = $this->getDatabase()->prepare(
                sprintf(
                    'SELECT %2$s, id FROM %1$s WHERE id IN (%3$s)',
                    // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following
                    // lines.
                    $this->getMetaModel()->getTableName(),     // 1
                    $valueColumn,                              // 2
                    implode(',', array_map('intval', $arrIds)) // 3
                // @codingStandardsIgnoreEnd
                )
            )->executeUncached();

            $valueIds = array();
            while ($rows->next()) {
                $valueIds[$rows->id] = $rows->$valueColumn;
            }

            $filter = $metaModel->getEmptyFilter();
            $filter->addFilterRule(new StaticIdList($valueIds));

            $items  = $metaModel->findByFilter($filter, 'id');
            $values = array();
            foreach ($items as $item) {
                $valueId    = $item->get('id');
                $parsedItem = $item->parseValue();

                $values[$valueId] = array_merge(
                    array(self::SELECT_RAW => $parsedItem['raw']),
                    $parsedItem['text']
                );
            }

            foreach ($valueIds as $itemId => $valueId) {
                $result[$itemId] = $values[$valueIds[$itemId]];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException when invalid data is encountered.
     */
    public function setDataFor($arrValues)
    {
        if (!($this->getSelectSource() && $this->getValueColumn())) {
            return;
        }

        $query = sprintf(
        // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
            'UPDATE %1$s SET %2$s=? WHERE %1$s.id=?',
            $this->getMetaModel()->getTableName(), // 1
            $this->getColName()                    // 2
        // @codingStandardsIgnoreEnd
        );

        $database = $this->getDatabase();
        foreach ($arrValues as $itemId => $value) {
            if (is_array($value) && isset($value[self::SELECT_RAW]['id'])) {
                $database->prepare($query)->execute($value[self::SELECT_RAW]['id'], $itemId);
            } elseif (is_numeric($itemId) && is_numeric($value)) {
                $database->prepare($query)->execute($value, $itemId);
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

        if ($strColNameAlias) {
            /** @var MetaModelSelect $attribute */
            $metaModel       = $this->getSelectMetaModel();
            $sanitizedValues = array();
            foreach ($values as $value) {
                $valueIds = $metaModel->getAttribute($strColNameAlias)->searchFor($value);
                if ($valueIds === null) {
                    return null;
                }

                $sanitizedValues = array_merge($valueIds, $sanitizedValues);
            }

            return $sanitizedValues;
        } else {
            $values = array_map('intval', $values);
        }

        return $values;
    }
}
