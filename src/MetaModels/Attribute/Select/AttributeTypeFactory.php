<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * @package    MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Attribute\Select;

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
