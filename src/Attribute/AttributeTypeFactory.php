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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\Helper\TableManipulator;
use MetaModels\IFactory;

/**
 * Attribute type factory for select attributes.
 */
class AttributeTypeFactory implements IAttributeTypeFactory
{
    /**
     * Database connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Table manipulator.
     *
     * @var TableManipulator
     */
    protected $tableManipulator;

    /**
     * MetaModels factory.
     *
     * @var IFactory
     */
    protected $factory;

    /**
     * Filter setting factory.
     *
     * @var IFilterSettingFactory
     */
    protected $filterSettingFactory;

    /**
     * Construct.
     *
     * @param Connection            $connection           Database connection.
     * @param TableManipulator      $tableManipulator     Table manipulator.
     * @param IFactory              $factory              MetaModels factory.
     * @param IFilterSettingFactory $filterSettingFactory Filter setting factory.
     */
    public function __construct(
        Connection $connection,
        TableManipulator $tableManipulator,
        IFactory $factory,
        IFilterSettingFactory $filterSettingFactory
    ) {
        $this->connection           = $connection;
        $this->tableManipulator     = $tableManipulator;
        $this->factory              = $factory;
        $this->filterSettingFactory = $filterSettingFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeName()
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeIcon()
    {
        return 'bundles/metamodelsattributeselect/select.png';
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance($information, $metaModel)
    {
        if (substr($information['select_table'], 0, 3) === 'mm_') {
            return new MetaModelSelect(
                $metaModel,
                $information,
                $this->connection,
                $this->tableManipulator,
                $this->factory,
                $this->filterSettingFactory
            );
        }

        return new Select($metaModel, $information, $this->connection, $this->tableManipulator);
    }

    /**
     * Check if the type is translated.
     *
     * @return bool
     */
    public function isTranslatedType()
    {
        return false;
    }

    /**
     * Check if the type is of simple nature.
     *
     * @return bool
     */
    public function isSimpleType()
    {
        return true;
    }

    /**
     * Check if the type is of complex nature.
     *
     * @return bool
     */
    public function isComplexType()
    {
        return true;
    }
}
