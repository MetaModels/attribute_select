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

namespace MetaModels\AttributeSelectBundle\Attribute;

use MetaModels\Attribute\IAttributeTypeFactory;

/**
 * Attribute type factory for select attributes.
 */
class AttributeTypeFactory implements IAttributeTypeFactory
{
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
        return 'system/modules/metamodelsattribute_select/html/select.png';
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance($information, $metaModel)
    {
        if (substr($information['select_table'], 0, 3) === 'mm_') {
            return new MetaModelSelect($metaModel, $information);
        }

        return new Select($metaModel, $information);
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
