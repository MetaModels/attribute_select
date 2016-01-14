<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * @package    MetaModels
 * @subpackage Core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\DcGeneral\Events\MetaModels\Select;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use MetaModels\Attribute\Select\AbstractSelect;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\DcGeneral\Events\BaseSubscriber;

/**
 * The subscriber for the get filter options call.
 */
class BackendSubscriber extends BaseSubscriber
{
    /**
     * {@inheritDoc}
     */
    protected function registerEventsInDispatcher()
    {
        $this->addListener(
            GetPropertyOptionsEvent::NAME,
            array($this, 'getPropertyOptions')
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
        if ($event->getOptions() !== null) {
            return;
        }

        $model = $event->getModel();

        if (!($model instanceof Model)) {
            return;
        }
        $attribute = $model->getItem()->getAttribute($event->getPropertyName());

        if (!($attribute instanceof AbstractSelect)) {
            return;
        }

        try {
            $options = $attribute->getFilterOptions(null, false);
        } catch (\Exception $exception) {
            $options = array('Error: ' . $exception->getMessage());
        }

        $event->setOptions($options);
    }
}
