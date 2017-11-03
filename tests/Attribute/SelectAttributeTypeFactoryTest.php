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

use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\Attribute\Select\AttributeTypeFactory;
use MetaModels\IMetaModel;
use MetaModels\Test\Attribute\AttributeTypeFactoryTest;

/**
 * Test the attribute factory.
 *
 * @package MetaModels\Test\Filter\Setting
 */
class SelectAttributeTypeFactoryTest extends AttributeTypeFactoryTest
{
    /**
     * Mock a MetaModel.
     *
     * @param string $tableName        The table name.
     *
     * @param string $language         The language.
     *
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($tableName, $language, $fallbackLanguage)
    {
        $metaModel = $this->getMock(
            'MetaModels\MetaModel',
            array(),
            array(array())
        );

        $metaModel
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue($tableName));

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
     * Override the method to run the tests on the attribute factories to be tested.
     *
     * @return IAttributeTypeFactory[]
     */
    protected function getAttributeFactories()
    {
        return array(new AttributeTypeFactory());
    }

    /**
     * Test creation of an plain SQL select.
     *
     * @return void
     */
    public function testCreateSelect()
    {
        $factory   = new AttributeTypeFactory();
        $values    = array(
            'select_table'  => 'tl_page',
            'select_column' => 'pid',
            'select_alias'  => 'alias',
        );
        $attribute = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $this->assertInstanceOf('MetaModels\Attribute\Select\Select', $attribute);

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
        $factory   = new AttributeTypeFactory();
        $values    = array(
            'select_table'  => 'mm_page',
            'select_column' => 'pid',
            'select_alias'  => 'alias',
        );
        $attribute = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $this->assertInstanceOf('MetaModels\Attribute\Select\MetaModelSelect', $attribute);

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $attribute->get($key), $key);
        }
    }
}
