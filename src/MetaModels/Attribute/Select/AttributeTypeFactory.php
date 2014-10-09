<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package     MetaModels
 * @subpackage  AttributeSelect
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author      Stefan heimes <stefan_heimes@hotmail.com>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\Select;

use MetaModels\Attribute\AbstractAttributeTypeFactory;
use MetaModels\Attribute\Events\CreateAttributeFactoryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Attribute type factory for select attributes.
 */
class AttributeTypeFactory extends AbstractAttributeTypeFactory implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            CreateAttributeFactoryEvent::NAME => 'registerLegacyAttributeFactoryEvents'
        );
    }

    /**
     * Register all legacy factories and all types defined via the legacy array as a factory.
     *
     * @param CreateAttributeFactoryEvent $event The event.
     *
     * @return void
     */
    public static function registerLegacyAttributeFactoryEvents(CreateAttributeFactoryEvent $event)
    {
        $factory = $event->getFactory();
        $factory->addTypeFactory(new static());
    }

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->typeName  = 'select';
        $this->typeClass = 'MetaModels\Attribute\Select\Select';
    }
}
