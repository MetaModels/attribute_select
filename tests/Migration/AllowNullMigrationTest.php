<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2024 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeSelectBundle\Test\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\StringType;
use MetaModels\AttributeSelectBundle\Migration\AllowNullMigration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MetaModels\AttributeSelectBundle\Migration\AllowNullMigration
 */
class AllowNullMigrationTest extends TestCase
{
    public function testName(): void
    {
        $connection = $this->createMock(Connection::class);
        $migration  = new AllowNullMigration($connection);

        self::assertSame('Allow null values in MetaModels "select" attributes.', $migration->getName());
    }

    public function runConfiguration(): \Generator
    {
        yield 'required tables not exist' => [
            (object) [
                'requiredTablesExist' => false,
                'shouldRun'           => false,
                'attributeConfigured' => false
            ]
        ];

        yield 'attribute select not configured' => [
            (object) [
                'requiredTablesExist' => true,
                'shouldRun'           => false,
                'attributeConfigured' => false
            ]
        ];

        yield 'attribute select is configured' => [
            (object) [
                'requiredTablesExist' => true,
                'shouldRun'           => false,
                'attributeConfigured' => true
            ]
        ];

        yield 'columns migrated' => [
            (object) [
                'requiredTablesExist' => true,
                'shouldRun'           => true,
                'attributeConfigured' => true
            ]
        ];
    }

    /**
     * @dataProvider runConfiguration
     *
     * @param object{requiredTablesExist: bool, shouldRun: bool, attributeConfigured: bool} $configuration
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRun(object $configuration): void
    {
        $connection = $this->createMock(Connection::class);
        $plattform  = $this->getMockBuilder(AbstractPlatform::class)->disableOriginalConstructor()->getMock();
        $manager    = $this
            ->getMockBuilder(AbstractSchemaManager::class)
            ->setConstructorArgs([$connection, $plattform])
            ->onlyMethods(['tablesExist', 'introspectTable', 'alterTable'])
            ->getMockForAbstractClass();

        $manager
            ->expects(self::once())
            ->method('tablesExist')
            ->with(['tl_metamodel', 'tl_metamodel_attribute'])
            ->willReturn($configuration->requiredTablesExist);

        $connection
            ->expects(
                $configuration->attributeConfigured ? self::exactly($configuration->shouldRun ? 7 : 2) : self::once()
            )
            ->method('createSchemaManager')
            ->willReturn($manager);

        $queryBuilderExecuted = 0;
        if ($configuration->requiredTablesExist) {
            $attributes = [
                ['metamodel' => 'mm_table_2', 'attribute' => 'normal'],
                ['metamodel' => 'mm_table_1', 'attribute' => 'camelCase'],
                ['metamodel' => 'mm_table_1', 'attribute' => 'normal'],
                ['metamodel' => 'mm_table_2', 'attribute' => 'camelCase'],
                ['metamodel' => 'mm_table_2', 'attribute' => 'columnnotexist'],
                ['metamodel' => 'mm_table_2', 'attribute' => 'columnNotExist']
            ];
            $result = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
            $result
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('fetchAllAssociative')
                ->willReturn($configuration->attributeConfigured ? $attributes : []);

            $queryBuilder = $this->createMock(QueryBuilder::class);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(4)  : self::never())
                ->method('update')
                ->willReturnCallback(function (string $table, string $alias) use ($queryBuilder) {
                    static $expected = [
                        ['mm_table_2', 't'],
                        ['mm_table_2', 't'],
                        ['mm_table_1', 't'],
                        ['mm_table_1', 't'],
                    ];
                    static $invocationCount = 0;

                    self::assertSame($expected[$invocationCount][0] ?? null, $table);
                    self::assertSame($expected[$invocationCount][1] ?? null, $alias);
                    $invocationCount++;

                    return $queryBuilder;
                });
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(4)  : self::never())
                ->method('set')
                ->willReturnCallback(function (string $parameter, string $value) use ($queryBuilder) {
                    static $expected = [
                        ['t.normal', 'null'],
                        ['t.camelCase', 'null'],
                        ['t.camelCase', 'null'],
                        ['t.normal', 'null'],
                    ];
                    static $invocationCount = 0;

                    self::assertSame($expected[$invocationCount][0] ?? null, $parameter);
                    self::assertSame($expected[$invocationCount][1] ?? null, $value);
                    $invocationCount++;

                    return $queryBuilder;
                });
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('select')
                ->with('metamodel.tableName AS metamodel', 'attribute.colName AS attribute')
                ->willReturn($queryBuilder);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('from')
                ->with('tl_metamodel_attribute', 'attribute')
                ->willReturn($queryBuilder);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('leftJoin')
                ->with('attribute', 'tl_metamodel', 'metamodel', 'attribute.pid = metamodel.id')
                ->willReturn($queryBuilder);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(6) : self::once())
                ->method('where')
                ->willReturnCallback(function (string $restriction) use ($queryBuilder) {
                    static $expected = [
                        ['attribute.type=:type'],
                        ['attribute.type=:type'],
                        ['t.normal = ""'],
                        ['t.camelCase = ""'],
                        ['t.camelCase = ""'],
                        ['t.normal = ""']
                    ];
                    static $invocationCount = 0;

                    self::assertSame($expected[$invocationCount][0] ?? '', $restriction);
                    $invocationCount++;

                    return $queryBuilder;
                });
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('setParameter')
                ->with('type', 'alias')
                ->willReturn($queryBuilder);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(6)  : self::once())
                ->method('executeQuery')
                ->willReturnCallback(
                    function () use (&$queryBuilderExecuted, $result) {
                        $queryBuilderExecuted++;
                        if ($queryBuilderExecuted <= 2) {
                            return $result;
                        }

                        return $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
                    }
                );

            $connection
                ->expects($configuration->shouldRun ? self::exactly(6)  : self::once())
                ->method('createQueryBuilder')
                ->willReturn($queryBuilder);
        }

        $tables = [];
        if ($configuration->requiredTablesExist && $configuration->attributeConfigured) {
            $columnDefault = $configuration->shouldRun ? 0 : null;
            $tables = [
                'mm_table_1' => (new Table('mm_table_2', [
                    (new Column('normal', new StringType()))->setDefault($columnDefault),
                    (new Column('camelCase', new StringType()))->setDefault($columnDefault)
                ])),
                'mm_table_2' => (new Table('mm_table_2', [
                    (new Column('normal', new StringType()))->setDefault($columnDefault),
                    (new Column('camelCase', new StringType()))->setDefault($columnDefault)
                ])),
            ];
        }
        $manager
            ->method('introspectTable')
            ->willReturnCallback(function (string $tableName) use ($tables) {
                $return = $tables[$tableName] ?? null;
                self::assertNotNull($return);
                return $return;
            });

        $migration = new AllowNullMigration($connection);
        self::assertSame($configuration->shouldRun, $migration->shouldRun());
        self::assertSame($configuration->requiredTablesExist ? 1 : 0, $queryBuilderExecuted);

        if (!$configuration->shouldRun) {
            return;
        }


        $migrationResult = $migration->run();
        self::assertTrue($migrationResult->isSuccessful());
        self::assertSame(
            'Adjusted column(s): mm_table_2.normal, mm_table_2.camelCase, mm_table_1.camelCase, mm_table_1.normal',
            $migrationResult->getMessage()
        );
        self::assertSame(6, $queryBuilderExecuted);
    }
}
