<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2023 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2023 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types = 1);

namespace MetaModels\AttributeSelectBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;

/**
 * This migration changes all database columns to allow null values.
 *
 * This became necessary with the changes for https://github.com/MetaModels/core/issues/1330.
 */
class AllowNullMigration extends AbstractMigration
{
    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Create a new instance.
     *
     * @param Connection $connection The database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Allow null values in MetaModels "select" attributes.';
    }

    /**
     * Must only run if:
     * - the MM tables are present AND
     * - there are some columns defined AND
     * - these columns do not allow null values yet.
     *
     * @return bool
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_metamodel', 'tl_metamodel_attribute'])) {
            return false;
        }

        $langColumns = $this->fetchNonNullableColumns();
        if (empty($langColumns)) {
            return false;
        }

        return true;
    }

    /**
     * Collect the columns to be updated and update them.
     *
     * @return MigrationResult
     */
    public function run(): MigrationResult
    {
        $langColumns = $this->fetchNonNullableColumns();
        $message     = [];
        foreach ($langColumns as $tableName => $tableColumns) {
            foreach ($tableColumns as $tableColumn) {
                $this->fixColumn($tableName, $tableColumn);
                $message[] = $tableName . '.' . $tableColumn->getName();
            }
        }

        return new MigrationResult(true, 'Adjusted column(s): ' . implode(', ', $message));
    }

    /**
     * Fetch all columns that are not nullable yet.
     *
     * @return array
     */
    private function fetchNonNullableColumns(): array
    {
        $langColumns = $this->fetchColumnNames();
        if (empty($langColumns)) {
            return [];
        }
        $schemaManager = $this->connection->getSchemaManager();

        $result = [];
        foreach ($langColumns as $tableName => $tableColumnNames) {
            /** @var Column[] $columns */
            $columns = [];
            // The schema manager return the column list with lowercase keys, wo got to use the real names.
            \array_map(
                function (Column $column) use (&$columns) {
                    $columns[$column->getName()] = $column;
                },
                $schemaManager->listTableColumns($tableName)
            );
            foreach ($tableColumnNames as $tableColumnName) {
                $column = ($columns[$tableColumnName] ?? null);
                if (null === $column) {
                    continue;
                }
                if (null !== $column->getDefault()) {
                    if (!isset($result[$tableName])) {
                        $result[$tableName] = [];
                    }
                    $result[$tableName][] = $column;
                }
            }
        }

        return $result;
    }

    /**
     * Obtain the names of table columns.
     *
     * @return array
     */
    private function fetchColumnNames(): array
    {
        $langColumns = $this
            ->connection
            ->createQueryBuilder()
            ->select('metamodel.tableName AS metamodel', 'attribute.colName AS attribute')
            ->from('tl_metamodel_attribute', 'attribute')
            ->leftJoin('attribute', 'tl_metamodel', 'metamodel', 'attribute.pid = metamodel.id')
            ->where('attribute.type=:type')
            ->setParameter('type', 'select')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        foreach ($langColumns as $langColumn) {
            if (!isset($result[$langColumn['metamodel']])) {
                $result[$langColumn['metamodel']] = [];
            }
            $result[$langColumn['metamodel']][] = $langColumn['attribute'];
        }

        return $result;
    }

    /**
     * Fix a table column.
     *
     * @param string $tableName The name of the table.
     * @param Column $column    The column.
     *
     * @return void
     */
    private function fixColumn(string $tableName, Column $column): void
    {
        $manager = $this->connection->getSchemaManager();
        $table   = $manager->listTableDetails($tableName);

        $changeColumn = new Column($column->getName(), $column->getType());
        $changeColumn
            ->setLength($column->getLength())
            ->setNotnull(false)
            ->setDefault(null);
        $columnDiff = new ColumnDiff($column->getName(), $changeColumn);

        $tableDiff                   = new TableDiff($tableName);
        $tableDiff->fromTable        = $table;
        $tableDiff->changedColumns[] = $columnDiff;
        $manager->alterTable($tableDiff);

        $this->connection->createQueryBuilder()
            ->update($tableName, 't')
            ->set('t.' . $column->getName(), 'null')
            ->where('t.' . $column->getName() . ' = 0')
            ->executeQuery();
    }
}
