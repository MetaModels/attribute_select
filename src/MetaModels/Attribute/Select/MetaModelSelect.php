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
        return array_merge(parent::getAttributeSettingNames(), array(
            'select_filter',
            'select_filterparams',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        $arrReturn = array();

        if (!is_array($varValue) || empty($varValue)) {
            return $arrReturn;
        }

        foreach ($varValue as $mixItem) {
            if (is_array($mixItem) && isset($mixItem['id'])) {
                $arrReturn[] = $mixItem['id'];
            } elseif (!is_array($mixItem)) {
                $arrReturn[] = $mixItem;
            }
        }

        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $intId)
    {
        return $varValue;
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

        $arrUsedValues = \Database::getInstance()
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
            $presets      = (array)$this->get('select_filterparams');
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

        $arrReturn = array();

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

        foreach ($objItems as $objItem) {
            $arrItem = $objItem->parseValue();

            $strValue = $arrItem['text'][$strDisplayValue];
            $strAlias = $objItem->get('id');

            $arrReturn[$strAlias] = $strValue;
        }

        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFor($arrIds)
    {
        $displayValue = $this->getValueColumn();
        $result       = array();

        // Get data from MetaModel.
        $metaModel = $this->getSelectMetaModel();

        if ($this->getSelectSource() && $metaModel && $displayValue) {
            // First pass, load database rows.
            $rows = $this->getDatabase()->prepare(
                sprintf(
                    'SELECT %2$s, id FROM %1$s WHERE id IN (%3$s)',
                    // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following
                    // lines.
                    $this->getMetaModel()->getTableName(),     // 1
                    $this->getColName(),                       // 2
                    implode(',', array_map('intval', $arrIds)) // 3
                    // @codingStandardsIgnoreEnd
                )
            )->execute();

            while ($rows->next()) {
                $result[$rows->id] = $rows->row();
            }

            $filter = $metaModel->getEmptyFilter();
            $filter->addFilterRule(new StaticIdList($arrIds));

            $items = $metaModel->findByFilter($filter);

            foreach ($items as $item) {
                $itemId    = $item->get('id');
                $arrValues = $item->parseValue();

                $result[$itemId] = array_merge(
                    $result[$itemId],
                    $arrValues['text']
                );
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
            if (is_array($value) && isset($value['id'])) {
                    $database->prepare($query)->execute($value['id'], $itemId);
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
}
