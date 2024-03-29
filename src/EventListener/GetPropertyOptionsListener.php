<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2022 The MetaModels team.
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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\Data\MultiLanguageDataProviderInterface;
use MetaModels\AttributeSelectBundle\Attribute\AbstractSelect;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\ITranslatedMetaModel;

/**
 * The subscriber for the get filter options call.
 */
class GetPropertyOptionsListener
{
    /**
     * Retrieve the property options.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function getPropertyOptions(GetPropertyOptionsEvent $event)
    {
        if ($event->getOptions() !== null) {
            return;
        }

        $provider = $event->getEnvironment()->getDataProvider();
        $model    = $event->getModel();
        if (!($model instanceof Model)) {
            return;
        }

        // Check if we have the right attribute.
        $attribute = $model->getItem()->getAttribute($event->getPropertyName());
        if (!($attribute instanceof AbstractSelect)) {
            return;
        }

        // Check multilanguage support.
        $attrModel = $attribute->getMetaModel();
        if ($provider instanceof MultiLanguageDataProviderInterface) {
            $currentLanguage = $provider->getCurrentLanguage();

            if (!empty($currentLanguage) && $attrModel instanceof ITranslatedMetaModel) {
                $originalLanguage = $attrModel->selectLanguage($currentLanguage);
            } elseif (!empty($currentLanguage)) {
                $originalLanguage       = \str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $currentLanguage);
            }
        }

        try {
            $options = $attribute->getFilterOptionsForDcGeneral();
        } catch (\Exception $exception) {
            $options = ['Error: ' . $exception->getMessage()];
        }

        // Reset language.
        if ($provider instanceof MultiLanguageDataProviderInterface && isset($originalLanguage)) {
            if ($attrModel instanceof ITranslatedMetaModel) {
                $attrModel->selectLanguage($originalLanguage);
            } else {
                $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $originalLanguage);
            }
        }

        $event->setOptions($options);
    }
}
