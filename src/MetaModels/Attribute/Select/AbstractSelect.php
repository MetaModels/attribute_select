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
 * @author      Stefan heimes <stefan_heimes@hotmail.com>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\Select;

use MetaModels\Attribute\AbstractHybrid;
use MetaModels\Filter\Rules\FilterRuleSelect;

/**
 * This is the abstract base class for handling select attributes.
 *
 * @package    MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
abstract class AbstractSelect extends AbstractHybrid
{
    /**
     * The widget mode to use.
     *
     * @var int
     */
    protected $widgetMode;

    /**
     * Retrieve the database instance.
     *
     * @return \Database
     */
    protected function getDatabase()
    {
        return \Database::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDataType()
    {
        return 'int(11) NOT NULL default \'0\'';
    }

    /**
     * Determine if we want to use tree selection.
     *
     * @return bool
     */
    protected function isTreePicker()
    {
        return $this->widgetMode == 2;
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
    protected function getSortingColumn()
    {
        return $this->get('select_sorting') ?: ($this->get('select_id') ?: 'id');
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
        return $this->get('select_alias') ?: $this->get('select_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef      = parent::getFieldDefinition($arrOverrides);
        $this->widgetMode = $arrOverrides['select_as_radio'];
        if ($this->isTreePicker()) {
            $arrFieldDef['inputType']          = 'DcGeneralTreePicker';
            $arrFieldDef['eval']['sourceName'] = $this->getSelectSource();
            $arrFieldDef['eval']['fieldType']  = 'radio';
            $arrFieldDef['eval']['idProperty'] = $this->getAliasColumn();
        } elseif ($this->widgetMode == 1) {
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
        return array_merge(
            parent::getAttributeSettingNames(),
            array(
                'select_table',
                'select_column',
                'select_alias',
                'select_sorting',
                'select_as_radio',
                'includeBlankOption',
                'submitOnChange',
                'mandatory',
                'chosen',
                'filterable',
                'searchable',
                'sortable',
                'flag'
            )
        );
    }

    /**
     * {@inheritdoc}
     *
     * search value in table
     */
    public function searchFor($strPattern)
    {
        $objFilterRule = new FilterRuleSelect($this, $strPattern);

        return $objFilterRule->getMatchingIds();
    }

    /**
     * {@inheritdoc}
     */
    public function unsetDataFor($arrIds)
    {
        $strQuery = sprintf(
            'UPDATE %1$s SET %2$s=0 WHERE %1$s.id IN (%3$s)',
            $this->getMetaModel()->getTableName(),
            $this->getColName(),
            implode(',', $arrIds)
        );
        $this->getDatabase()->execute($strQuery);
    }
}
