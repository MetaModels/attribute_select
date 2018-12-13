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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\Attribute;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

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
            && $this->connection->getSchemaManager()->tablesExist([$this->getSelectSource()]);
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
        $idList        = $this->connection->createQueryBuilder()
            ->select('m.id')
            ->from($this->getMetaModel()->getTableName(), 'm')
            ->leftJoin('m', $strTableName, 's', sprintf('s.%s = m.%s', $strColNameId, $this->getColName()))
            ->where('m.id IN (:ids)')
            ->orderBy('s.' . $strSortColumn, $strDirection)
            ->setParameter('ids', $idList, Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);

        return $idList;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(parent::getAttributeSettingNames(), array(
            'select_id',
            'select_where',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        return $varValue[$this->getIdColumn()];
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $itemId)
    {
        // Lookup the value.
        $values = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getSelectSource())
            ->where($this->getIdColumn() . '=:id')
            ->setParameter('id', $varValue)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        return $values;
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
        return $this->get('select_where') ? html_entity_decode($this->get('select_where')) : false;
    }

    /**
     * Convert the database result into a proper result array.
     *
     * @param Statement $statement   The database result statement.
     *
     * @param string    $aliasColumn The name of the alias column to be used.
     *
     * @param string    $valueColumn The name of the value column.
     *
     * @param array     $count       The optional count array.
     *
     * @return array
     */
    protected function convertOptionsList($statement, $aliasColumn, $valueColumn, &$count = null)
    {
        $arrReturn = array();
        while ($values = $statement->fetch(\PDO::FETCH_OBJ)) {
            if (is_array($count)) {
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
     * @return Statement
     */
    public function getFilterOptionsForUsedOnly($usedOnly)
    {
        $sortColumn = $this->getSortingColumn();

        if ($usedOnly) {
            $builder = $this->connection->createQueryBuilder()
                ->select('COUNT(sourceTable.' . $this->getIdColumn() . ') as mm_count')
                ->addSelect('sourceTable.*')
                ->from($this->getSelectSource(), 'sourceTable')
                ->rightJoin(
                    'sourceTable',
                    $this->getMetaModel()->getTableName(),
                    'modelTable',
                    'modelTable.' . $this->getColName() . '=' . 'sourceTable.' . $this->getIdColumn()
                )
                ->addGroupBy('sourceTable.' . $this->getIdColumn())
                ->addOrderBy('sourceTable.' . $sortColumn);

            if($additionalWhere = $this->getAdditionalWhere()) {
                $builder->andWhere($additionalWhere);
            }

            return $builder->execute();
        }

        $builder = $this->connection->createQueryBuilder()
            ->select('COUNT(modelTable.' . $this->getColName() . ') as mm_count')
            ->addSelect('sourceTable.*')
            ->from($this->getSelectSource(), 'sourceTable')
            ->leftJoin(
                'sourceTable',
                $this->getMetaModel()->getTableName(),
                'modelTable',
                'modelTable.' . $this->getColName() . '=' . 'sourceTable.' . $this->getIdColumn()
            )
            ->addGroupBy('sourceTable.' . $this->getIdColumn())
            ->addOrderBy('sourceTable.' . $sortColumn);

        if($additionalWhere = $this->getAdditionalWhere()) {
            $builder->andWhere($additionalWhere);
        }

        return $builder->execute();
    }

    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        if (!$this->isFilterOptionRetrievingPossible($idList)) {
            return array();
        }

        $tableName       = $this->getSelectSource();
        $idColumn        = $this->getIdColumn();
        $strSortColumn   = $this->getSortingColumn();

        if ($idList) {
            $builder = $this->connection->createQueryBuilder()
                ->select('COUNT(sourceTable.' . $idColumn . ') as mm_count')
                ->addSelect('sourceTable.*')
                ->from($tableName, 'sourceTable')
                ->rightJoin(
                    'sourceTable',
                    $this->getMetaModel()->getTableName(),
                    'modelTable',
                    'modelTable.' . $this->getColName() . '=sourceTable.' . $idColumn
                )
                ->where('modelTable.id IN (:ids)')
                ->setParameter('ids', $idList, Connection::PARAM_STR_ARRAY)
                ->addGroupBy('sourceTable.' . $idColumn)
                ->addOrderBy('sourceTable.' . $strSortColumn);

            if($additionalWhere = $this->getAdditionalWhere()) {
                $builder->andWhere($additionalWhere);
            }

            $statement = $builder->execute();

        } else {
            $statement = $this->getFilterOptionsForUsedOnly($usedOnly);
        }

        return $this->convertOptionsList($statement, $this->getAliasColumn(), $this->getValueColumn(), $arrCount);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFor($arrIds)
    {
        if (!$this->isProperlyConfigured()) {
            return array();
        }

        $strTableNameId = $this->getSelectSource();
        $strColNameId   = $this->getIdColumn();
        $arrReturn      = [];

        $strMetaModelTableName   = $this->getMetaModel()->getTableName();
        $strMetaModelTableNameId = $strMetaModelTableName.'_id';

        $builder = $this->connection->createQueryBuilder()
            ->select('sourceTable.*')
            ->addselect('modelTable.id AS ' . $strMetaModelTableNameId)
            ->from($strTableNameId, 'sourceTable')
            ->leftJoin(
                'sourceTable',
                $strMetaModelTableName,
                'modelTable',
                'sourceTable.' . $strColNameId . '=modelTable.' . $this->getColName()
            )
            ->where('modelTable.id IN (:ids)')
            ->setParameter('ids', $arrIds, Connection::PARAM_STR_ARRAY)
            ->execute();

        foreach ($builder->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $arrReturn[$row[$strMetaModelTableNameId]] = $row;
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
            foreach ($arrValues as $intItemId => $arrValue) {
                $this->connection->update(
                    $this->getMetaModel()->getTableName(),
                    [$this->getColName() => $arrValue[$strColNameId]],
                    ['id' => $intItemId]
                );
            }
        }
    }
}
