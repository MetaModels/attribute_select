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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types = 1);

namespace MetaModels\AttributeSelectBundle\Test\DependencyInjection;

use MetaModels\AttributeSelectBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeSelectBundle\Controller\RateAjaxController;
use MetaModels\AttributeSelectBundle\DependencyInjection\MetaModelsAttributeSelectExtension;
use MetaModels\AttributeSelectBundle\Migration\AllowNullMigration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * This test case test the extension.
 *
 * @covers \MetaModels\AttributeSelectBundle\DependencyInjection\MetaModelsAttributeSelectExtension
 */
class MetaModelsAttributeSelectExtensionTest extends TestCase
{
    /**
     * Test that extension can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $extension = new MetaModelsAttributeSelectExtension();

        $this->assertInstanceOf(MetaModelsAttributeSelectExtension::class, $extension);
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    /**
     * Test that the services are loaded.
     *
     * @return void
     */
    public function testRegistersServices()
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();

        $container
            ->expects($this->atLeastOnce())
            ->method('setDefinition')
            ->withConsecutive(
                [
                    'metamodels.attribute_select.factory',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(AttributeTypeFactory::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('metamodels.attribute_factory'));

                            return true;
                        }
                    )
                ],
                [
                    AllowNullMigration::class,
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertCount(1, $value->getTag('contao.migration'));

                            return true;
                        }
                    )
                ]
            );

        $extension = new MetaModelsAttributeSelectExtension();
        $extension->load([], $container);
    }
}
