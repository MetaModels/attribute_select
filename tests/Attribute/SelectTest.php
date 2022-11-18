<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2021 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use MetaModels\AttributeSelectBundle\Attribute\Select;
use MetaModels\Helper\TableManipulator;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests to test class Select.
 *
 * @covers \MetaModels\AttributeSelectBundle\Attribute\Select
 */
class SelectTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $language         The language.
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage)
    {
        $metaModel = $this->getMockForAbstractClass('MetaModels\IMetaModel');

        $metaModel
            ->expects($this->any())
            ->method('getTableName')
            ->willReturn('mm_unittest');

        $metaModel
            ->expects($this->any())
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->expects($this->any())
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock the table manipulator.
     *
     * @param Connection $connection The database connection mock.
     *
     * @return TableManipulator|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTableManipulator(Connection $connection)
    {
        return $this->getMockBuilder(TableManipulator::class)
            ->setConstructorArgs([$connection, []])
            ->getMock();
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiationSelect()
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $text = new Select($this->mockMetaModel('en', 'en'), [], $connection, $manipulator);
        $this->assertInstanceOf('MetaModels\AttributeSelectBundle\Attribute\Select', $text);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function valueToWidgetProvider(): array
    {
        return [
            'null returns null' => [
                'expected'    => null,
                'value'       => [],
                'attr_config' => ['id' => uniqid('', false)],
            ],
            'empty string returns empty string' => [
                'expected'    => '',
                'value'       => ['id' => ''],
                'attr_config' => ['id' => uniqid('', false)],
            ],
            'value without row value null' => [
                'expected'    => null,
                'value'       => ['foo' => 'bar'],
                'attr_config' => ['id' => uniqid('', false)],
            ],
            'numeric id is returned' => [
                'expected'    => 10,
                'value'       => ['id' => 10],
                'attr_config' => ['id' => uniqid('', false)],
            ],
        ];
    }

    /**
     * Test the value to widget method.
     *
     * @param mixed $expected   The expected value.
     * @param mixed $value      The input value (native value).
     * @param array $attrConfig The attribute config.
     *
     * @return void
     *
     * @dataProvider valueToWidgetProvider
     */
    public function testValueToWidget($expected, $value, $attrConfig): void
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $select = new Select(
            $this->mockMetaModel('en', 'en'),
            $attrConfig,
            $connection,
            $manipulator
        );

        $this->assertSame($expected, $select->valueToWidget($value));
    }

    /**
     * Test the widget to value method.
     *
     * @return void
     */
    public function testWidgetToValueForNull(): void
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);
        $select      = new Select(
            $this->mockMetaModel('en', 'en'),
            ['id' => uniqid('', false)],
            $connection,
            $manipulator
        );

        $this->assertNull($select->widgetToValue(null, 23));
    }

    /**
     * Test the widget to value method.
     *
     * @return void
     */
    public function testWidgetToValueForNonNull(): void
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);
        $select      = new Select(
            $this->mockMetaModel('en', 'en'),
            [
                'id'           => uniqid('', false),
                'select_table' => 'mm_test_select',
            ],
            $connection,
            $manipulator
        );

        $builder = $this
            ->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builder
            ->expects($this->once())
            ->method('select')
            ->with('*')
            ->willReturn($builder);
        $builder
            ->expects($this->once())
            ->method('from')
            ->with('mm_test_select')
            ->willReturn($builder);

        $builder
            ->expects($this->once())
            ->method('where')
            ->with('t.id=:value')
            ->willReturn($builder);

        $builder
            ->expects($this->once())
            ->method('setParameter')
            ->with('value', 10)
            ->willReturn($builder);

        $builder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($builder);

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $statement
            ->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls([
                'id'      => 10,
                'pid'     => 0,
                'sorting' => 1,
                'tstamp'  => 343094400,
            ], null);
        $builder
            ->expects($this->once())
            ->method('execute')
            ->willReturn($statement);
        $connection->expects($this->once())->method('createQueryBuilder')->willReturn($builder);

        $this->assertSame([
            'id'      => 10,
            'pid'     => 0,
            'sorting' => 1,
            'tstamp'  => 343094400,
        ], $select->widgetToValue(10, 23));
    }
}
