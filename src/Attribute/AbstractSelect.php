<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2025 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2025 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\Attribute;

use Doctrine\DBAL\ArrayParameterType;
use MetaModels\Attribute\AbstractHybrid;
use MetaModels\AttributeSelectBundle\FilterRule\FilterRuleSelect;

/**
 * This is the MetaModelAttribute class for handling select attributes.
 */
abstract class AbstractSelect extends AbstractHybrid
{
    /**
     * The widget mode to use.
     *
     * @var int
     */
    protected $widgetMode = 0;

    /**
     * Local cached flag if the attribute has been properly configured.
     *
     * @var bool|null
     */
    private ?bool $isProperlyConfigured = null;

    /**
     * {@inheritdoc}
     */
    public function getSQLDataType()
    {
        return 'int(11) NULL';
    }

    /**
     * Determine if we want to use tree selection.
     *
     * @return bool
     */
    protected function isTreePicker()
    {
        return $this->widgetMode === 2;
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getSelectSource()
    {
        return $this->get('select_table');
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getIdColumn()
    {
        return $this->get('select_id') ?: 'id';
    }

    /**
     * Determine the correct sort direction to use.
     *
     * @return string
     */
    protected function getSortDirection()
    {
        return $this->get('select_sort');
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getSortingColumn()
    {
        return $this->get('select_sorting') ?: $this->getIdColumn();
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getValueColumn()
    {
        return $this->get('select_column');
    }

    /**
     * Determine the correct alias column to use.
     *
     * @return string
     */
    protected function getAliasColumn()
    {
        $colNameAlias = $this->get('select_alias');
        if ($this->isTreePicker() || !$colNameAlias) {
            $colNameAlias = $this->getIdColumn();
        }

        return $colNameAlias;
    }

    /**
     * Ensure the attribute has been configured correctly.
     *
     * @return bool
     */
    protected function isProperlyConfigured()
    {
        if (null !== $this->isProperlyConfigured) {
            return $this->isProperlyConfigured;
        }

        return $this->isProperlyConfigured = $this->checkConfiguration();
    }

    /**
     * Check the configuration of the attribute.
     *
     * @return bool
     */
    protected function checkConfiguration()
    {
        return $this->getSelectSource()
            && $this->getValueColumn()
            && $this->getAliasColumn()
            && $this->getIdColumn()
            && $this->getSortingColumn();
    }

    /**
     * Test that we can create the filter options.
     *
     * @param string[]|null $idList The ids of items that the values shall be fetched from
     *                              (If empty or null, all items).
     *
     * @return bool
     */
    protected function isFilterOptionRetrievingPossible($idList)
    {
        return $this->isProperlyConfigured() && (($idList === null) || !empty($idList));
    }

    /**
     * Get the picker input type.
     *
     * @return string
     */
    private function getPickerType()
    {
        $sourceName = $this->getSelectSource();
        if (!\in_array($sourceName, ['tl_page', 'tl_files'])) {
            return 'DcGeneralTreePicker';
        }

        return $sourceName === 'tl_page' ? 'pageTree' : 'fileTree';
    }

    /**
     * Obtain the filter options with always the id being contained instead of the alias.
     *
     * This is being called from BackendSubscriber to circumvent problems when dealing with translated aliases.
     *
     * @return array<string, string>
     */
    abstract public function getFilterOptionsForDcGeneral();

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = [])
    {
        $arrFieldDef      = parent::getFieldDefinition($arrOverrides);
        $this->widgetMode = (int) $arrOverrides['select_as_radio'];
        if ($this->isTreePicker()) {
            $arrFieldDef['inputType']          = $this->getPickerType();
            $arrFieldDef['eval']['sourceName'] = $this->getSelectSource();
            $arrFieldDef['eval']['fieldType']  = 'radio';
            $arrFieldDef['eval']['idProperty'] = $this->getAliasColumn();
            $arrFieldDef['eval']['orderField'] = $this->getSortingColumn();
            $arrFieldDef['eval']['minLevel']   = $arrOverrides['select_minLevel'];
            $arrFieldDef['eval']['maxLevel']   = $arrOverrides['select_maxLevel'];
        } elseif ($this->widgetMode === 1) {
            // If select as radio is true, change the input type.
            $arrFieldDef['inputType'] = 'radio';
        } else {
            $arrFieldDef['inputType'] = 'select';
        }

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return \array_merge(
            parent::getAttributeSettingNames(),
            [
                'select_table',
                'select_column',
                'select_alias',
                'select_sorting',
                'select_sort',
                'select_as_radio',
                'includeBlankOption',
                'submitOnChange',
                'mandatory',
                'chosen',
                'filterable',
                'searchable'
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Search value in table.
     */
    public function searchFor($strPattern)
    {
        return (new FilterRuleSelect($this, $strPattern, $this->connection))->getMatchingIds() ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function unsetDataFor($arrIds)
    {
        $this->connection->createQueryBuilder()
            ->update($this->getMetaModel()->getTableName(), 't')
            ->set('t.' . $this->getColName(), '0')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $arrIds, ArrayParameterType::STRING)
            ->executeQuery();
    }

    /**
     * Convert the passed values to a list of value ids.
     *
     * @param list<string> $values The values to convert.
     *
     * @return list<string>
     */
    public function convertValuesToValueIds($values)
    {
        $tableName   = $this->getSelectSource();
        $idColumn    = $this->getIdColumn();
        $aliasColumn = $this->getAliasColumn();

        if ($idColumn === $aliasColumn) {
            return $values;
        }

        $values = \array_unique(\array_filter($values));
        if (empty($values)) {
            return [];
        }

        return $this->connection->createQueryBuilder()
            ->select('t.' . $idColumn)
            ->from($tableName, 't')
            ->where('t.' . $aliasColumn . ' IN (:values)')
            ->setParameter('values', $values, ArrayParameterType::STRING)
            ->executeQuery()
            ->fetchFirstColumn();
    }

    /**
     * Convert a native attribute value into a value to be used in a filter Url.
     *
     * This returns the value of the alias if any defined or the value of the id otherwise.
     *
     * @param mixed $varValue The source value.
     *
     * @return string
     */
    public function getFilterUrlValue($varValue)
    {
        return \urlencode($varValue[$this->getAliasColumn()]);
    }

    /**
     * {@inheritDoc}
     *
     * This is needed for compatibility with MySQL strict mode.
     */
    public function serializeData($value)
    {
        return $value === '' ? null : $value;
    }
}
