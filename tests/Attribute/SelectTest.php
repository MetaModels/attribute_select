<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2017 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\AttributeSelectBundle\Attribute\MetaModelSelect;
use MetaModels\AttributeSelectBundle\Attribute\Select;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\Helper\TableManipulator;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests to test class Select.
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
            ->will($this->returnValue('mm_unittest'));

        $metaModel
            ->expects($this->any())
            ->method('getActiveLanguage')
            ->will($this->returnValue($language));

        $metaModel
            ->expects($this->any())
            ->method('getFallbackLanguage')
            ->will($this->returnValue($fallbackLanguage));

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
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiationMetaModelSelect()
    {
        $connection    = $this->mockConnection();
        $manipulator   = $this->mockTableManipulator($connection);
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);

        $text = new MetaModelSelect(
            $this->mockMetaModel('en', 'en'),
            [],
            $connection,
            $manipulator,
            $factory,
            $filterFactory
        );

        $this->assertInstanceOf('MetaModels\AttributeSelectBundle\Attribute\MetaModelSelect', $text);
    }
}
