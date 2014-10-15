<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage Core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author      Stefan heimes <stefan_heimes@hotmail.com>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\DcGeneral\Events\MetaModels\Select;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use MetaModels\Attribute\Select\AbstractSelect;
use MetaModels\DcGeneral\Data\Model;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The subscriber for the get filter options call.
 */
class BackendSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array
        (
            GetPropertyOptionsEvent::NAME => 'getPropertyOptions'
        );
    }

    /**
     * Retrieve the property options.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getPropertyOptions(GetPropertyOptionsEvent $event)
    {
        if (substr($event->getModel()->getProviderName(), 0, 3) !== 'mm_') {
            return;
        }

        /** @var Model $model */
        $model     = $event->getModel();
        $item      = $model->getItem();
        $attribute = $item->getMetaModel()->getAttribute($event->getPropertyName());

        if (!($attribute instanceof AbstractSelect)) {
            return;
        }

        $event->setOptions($attribute->getFilterOptions(null, false));
    }
}
