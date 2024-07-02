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
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\AttributeSelectBundle\Attribute\AttributeTypeFactory;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\Helper\TableManipulator;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;

/**
 * Test the attribute factory.
 *
 * @covers \MetaModels\AttributeSelectBundle\Attribute\AttributeTypeFactory
 */
class SelectAttributeTypeFactoryTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $tableName        The table name.
     * @param string $language         The language.
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($tableName, $language, $fallbackLanguage)
    {
        $metaModel = $this->getMockBuilder(IMetaModel::class)->getMock();

        $metaModel
            ->expects(self::any())
            ->method('getTableName')
            ->willReturn($tableName);

        $metaModel
            ->expects(self::any())
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->expects(self::any())
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
     * Override the method to run the tests on the attribute factories to be tested.
     *
     * @return IAttributeTypeFactory[]
     */
    protected function getAttributeFactories()
    {
        $connection    = $this->mockConnection();
        $manipulator   = $this->mockTableManipulator($connection);
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);

        return array(new AttributeTypeFactory($connection, $manipulator, $factory, $filterFactory));
    }

    /**
     * Test creation of an plain SQL select.
     *
     * @return void
     */
    public function testCreateSelect()
    {
        $connection    = $this->mockConnection();
        $manipulator   = $this->mockTableManipulator($connection);
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);

        $factory   = new AttributeTypeFactory($connection, $manipulator, $factory, $filterFactory);
        $values    = array(
            'select_table'  => 'tl_page',
            'select_column' => 'pid',
            'select_alias'  => 'alias',
        );
        $attribute = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $this->assertInstanceOf('MetaModels\AttributeSelectBundle\Attribute\Select', $attribute);

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $attribute->get($key), $key);
        }
    }

    /**
     * Test creation of an plain SQL select.
     *
     * @return void
     */
    public function testCreateMetaModelSelect()
    {
        $connection    = $this->mockConnection();
        $manipulator   = $this->mockTableManipulator($connection);
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);

        $factory   = new AttributeTypeFactory($connection, $manipulator, $factory, $filterFactory);
        $values    = array(
            'select_table'  => 'mm_page',
            'select_column' => 'pid',
            'select_alias'  => 'alias',
        );
        $attribute = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $this->assertInstanceOf('MetaModels\AttributeSelectBundle\Attribute\MetaModelSelect', $attribute);

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $attribute->get($key), $key);
        }
    }
}
