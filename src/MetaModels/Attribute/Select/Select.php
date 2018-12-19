<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     David Maack <maack@men-at-work.de>
 * @author     Oliver Hoff <oliver@hofff.com>
 * @author     Paul Pflugradt <paulpflugradt@googlemail.com>
 * @author     Simon Kusterer <simon.kusterer@xamb.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Attribute\Select;

/**
 * This is the MetaModelAttribute class for handling select attributes on plain SQL tables.
 */
class Select extends AbstractSelect
{
    /**
     * {@inheritDoc}
     */
    protected function checkConfiguration()
    {
        return parent::checkConfiguration()
            && $this->getDatabase()->tableExists($this->getSelectSource());
    }

    /**
     * {@inheritdoc}
     */
    public function sortIds($idList, $strDirection)
    {
        if (!$this->isProperlyConfigured()) {
            return $idList;
        }

        $strTableName  = $this->getSelectSource();
        $strColNameId  = $this->getIdColumn();
        $strSortColumn = $this->getSortingColumn();
        $idList        = $this->getDatabase()
            ->prepare(
                \sprintf(
                    'SELECT %1$s.id FROM %1$s
                    LEFT JOIN %3$s ON (%3$s.%4$s=%1$s.%2$s)
                    WHERE %1$s.id IN (%5$s)
                    ORDER BY %3$s.%6$s %7$s',
                    // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                    $this->getMetaModel()->getTableName(), // 1
                    $this->getColName(),                   // 2
                    $strTableName,                         // 3
                    $strColNameId,                         // 4
                    $this->parameterMask($idList),         // 5
                    $strSortColumn,                        // 6
                    $strDirection                          // 7
                    // @codingStandardsIgnoreEnd
                )
            )
            ->execute($idList)
            ->fetchEach('id');

        return $idList;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return \array_merge(
            parent::getAttributeSettingNames(),
            [
                'select_id',
                'select_where',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        $value      = $varValue[$this->getIdColumn()];
        $sourceName = $this->getSelectSource();

        if ($this->isTreePicker() && !\in_array($sourceName, ['tl_page', 'tl_files'])) {
            return $value ? [$value] : null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $itemId)
    {
        // Lookup the value.
        $values = $this->getDatabase()
            ->prepare(\sprintf('SELECT %1$s.* FROM %1$s WHERE %2$s=?', $this->getSelectSource(), $this->getIdColumn()))
            ->execute($varValue);

        return $values->row();
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

        $values = $this->getFilterOptionsForUsedOnly(false);
        return $this->convertOptionsList($values, $this->getIdColumn(), $this->getValueColumn());
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getAdditionalWhere()
    {
        return $this->get('select_where') ? \html_entity_decode($this->get('select_where')) : false;
    }

    /**
     * Convert the database result into a proper result array.
     *
     * @param \Database\Result $values      The database result.
     *
     * @param string           $aliasColumn The name of the alias column to be used.
     *
     * @param string           $valueColumn The name of the value column.
     *
     * @param array            $count       The optional count array.
     *
     * @return array
     */
    protected function convertOptionsList($values, $aliasColumn, $valueColumn, &$count = null)
    {
        $arrReturn = [];
        while ($values->next()) {
            if (\is_array($count)) {
                /** @noinspection PhpUndefinedFieldInspection */
                $count[$values->$aliasColumn] = $values->mm_count;
            }

            $arrReturn[$values->$aliasColumn] = $values->$valueColumn;
        }

        return $arrReturn;
    }

    /**
     * Fetch filter options from foreign table taking the given flag into account.
     *
     * @param bool $usedOnly The flag if only used values shall be returned.
     *
     * @return \Database\Result
     */
    public function getFilterOptionsForUsedOnly($usedOnly)
    {
        $additionalWhere = $this->getAdditionalWhere();
        $sortColumn      = $this->getSortingColumn();
        if ($usedOnly) {
            return $this->getDatabase()->execute(\sprintf(
                'SELECT COUNT(%1$s.%2$s) as mm_count, %1$s.*
                    FROM %1$s
                    RIGHT JOIN %3$s ON (%3$s.%4$s=%1$s.%2$s)
                    %5$s
                    GROUP BY %1$s.%2$s
                    ORDER BY %1$s.%6$s',
                // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                $this->getSelectSource(),                                  // 1
                $this->getIdColumn(),                                      // 2
                $this->getMetaModel()->getTableName(),                     // 3
                $this->getColName(),                                       // 4
                ($additionalWhere ? ' WHERE ('.$additionalWhere.')' : ''), // 5
                $sortColumn                                                // 6
                // @codingStandardsIgnoreEnd
            ));
        }

        return $this->getDatabase()->execute(\sprintf(
            'SELECT COUNT(%3$s.%4$s) as mm_count, %1$s.*
                FROM %1$s
                LEFT JOIN %3$s ON (%3$s.%4$s=%1$s.%2$s)
                %5$s
                GROUP BY %1$s.%2$s
                ORDER BY %1$s.%6$s',
            // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
            $this->getSelectSource(),                                  // 1
            $this->getIdColumn(),                                      // 2
            $this->getMetaModel()->getTableName(),                     // 3
            $this->getColName(),                                       // 4
            ($additionalWhere ? ' WHERE ('.$additionalWhere.')' : ''), // 5
            $sortColumn                                                // 6
            // @codingStandardsIgnoreEnd
        ));
    }

    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        if (!$this->isFilterOptionRetrievingPossible($idList)) {
            return [];
        }

        $tableName       = $this->getSelectSource();
        $idColumn        = $this->getIdColumn();
        $strSortColumn   = $this->getSortingColumn();
        $strColNameWhere = $this->getAdditionalWhere();

        $objDB = $this->getDatabase();
        if ($idList) {
            $objValue = $objDB
                ->prepare(\sprintf(
                    'SELECT COUNT(%1$s.%2$s) as mm_count, %1$s.*
                    FROM %1$s
                    RIGHT JOIN %3$s ON (%3$s.%4$s=%1$s.%2$s)
                    WHERE (%3$s.id IN (%5$s)%6$s)
                    GROUP BY %1$s.%2$s
                    ORDER BY %1$s.%7$s',
                    // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                    $tableName,                                              // 1
                    $idColumn,                                               // 2
                    $this->getMetaModel()->getTableName(),                   // 3
                    $this->getColName(),                                     // 4
                    $this->parameterMask($idList),                           // 5
                    ($strColNameWhere ? ' AND ('.$strColNameWhere.')' : ''), // 6
                    $strSortColumn                                           // 7
                    // @codingStandardsIgnoreEnd
                ))
                ->execute($idList);
        } else {
            $objValue = $this->getFilterOptionsForUsedOnly($usedOnly);
        }

        return $this->convertOptionsList($objValue, $this->getAliasColumn(), $this->getValueColumn(), $arrCount);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFor($arrIds)
    {
        if (!$this->isProperlyConfigured()) {
            return [];
        }

        $objDB          = $this->getDatabase();
        $strTableNameId = $this->getSelectSource();
        $strColNameId   = $this->getIdColumn();
        $arrReturn      = [];

        $strMetaModelTableName   = $this->getMetaModel()->getTableName();
        $strMetaModelTableNameId = $strMetaModelTableName.'_id';

        // Using aliased join here to resolve issue #3 - SQL error for self referencing table.
        $objValue = $objDB
            ->prepare(\sprintf(
                'SELECT sourceTable.*, %2$s.id AS %3$s
                FROM %1$s sourceTable
                LEFT JOIN %2$s ON (sourceTable.%4$s=%2$s.%5$s)
                WHERE %2$s.id IN (%6$s)',
                // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                $strTableNameId,              // 1
                $strMetaModelTableName,       // 2
                $strMetaModelTableNameId,     // 3
                $strColNameId,                // 4
                $this->getColName(),          // 5
                $this->parameterMask($arrIds) // 6
                // @codingStandardsIgnoreEnd
            ))
            ->execute($arrIds);

        while ($objValue->next()) {
            $arrReturn[$objValue->$strMetaModelTableNameId] = $objValue->row();
        }

        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFor($arrValues)
    {
        if (!$this->isProperlyConfigured()) {
            return;
        }

        $strTableName = $this->getSelectSource();
        $strColNameId = $this->getIdColumn();
        if ($strTableName && $strColNameId) {
            $strQuery = \sprintf(
                'UPDATE %1$s SET %2$s=? WHERE %1$s.id=?',
                $this->getMetaModel()->getTableName(),
                $this->getColName()
            );

            $objDB = $this->getDatabase();
            foreach ($arrValues as $intItemId => $arrValue) {
                $objDB->prepare($strQuery)->execute($arrValue[$strColNameId], $intItemId);
            }
        }
    }
}
