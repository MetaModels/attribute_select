<?php
/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Test\Attribute\Select;

use MetaModels\Attribute\Select\MetaModelSelect;
use MetaModels\Attribute\Select\Select;
use MetaModels\IMetaModel;
use MetaModels\MetaModel;
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
        $metaModel = $this->getMockBuilder(MetaModel::class)->setMethods([])->setConstructorArgs([[]])->getMock();

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
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiationSelect()
    {
        $text = new Select($this->mockMetaModel('en', 'en'));
        $this->assertInstanceOf(Select::class, $text);
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiationMetaModelSelect()
    {
        $text = new MetaModelSelect($this->mockMetaModel('en', 'en'));
        $this->assertInstanceOf(MetaModelSelect::class, $text);
    }
}
