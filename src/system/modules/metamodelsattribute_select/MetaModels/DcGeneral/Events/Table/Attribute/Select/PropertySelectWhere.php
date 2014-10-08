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
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\DcGeneral\Events\Table\Attribute\Select;

use ContaoCommunityAlliance\Contao\EventDispatcher\Event\CreateEventDispatcherEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\EncodePropertyValueFromWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use MetaModels\DcGeneral\Events\BaseSubscriber;

/**
 * Handle events for tl_metamodel_attribute.alias_fields.select_where.
 */
class PropertySelectWhere
    extends BaseSubscriber
{
    /**
     * Register all listeners to handle creation of a data container.
     *
     * @param CreateEventDispatcherEvent $event The event.
     *
     * @return void
     */
    public static function registerEvents(CreateEventDispatcherEvent $event)
    {
        $dispatcher = $event->getEventDispatcher();
        self::registerBuildDataDefinitionFor(
            'tl_metamodel_attribute',
            $dispatcher,
            __CLASS__ . '::registerTableMetaModelAttributeEvents'
        );
    }

    /**
     * Register the events for table tl_metamodel_attribute.
     *
     * @param BuildDataDefinitionEvent $event The event being processed.
     *
     * @return void
     */
    public static function registerTableMetaModelAttributeEvents(BuildDataDefinitionEvent $event)
    {
        static $registered;
        if ($registered)
        {
            return;
        }
        $registered = true;
        $dispatcher = $event->getDispatcher();

        self::registerListeners(
            array(
                EncodePropertyValueFromWidgetEvent::NAME => __CLASS__ . '::checkQuery',
            ),
            $dispatcher,
            array('tl_metamodel_attribute', 'select_where')
        );
    }

    /**
     * Check if the select_where value is valid by firing a test query.
     *
     * @param EncodePropertyValueFromWidgetEvent $event The event.
     *
     * @return void
     *
     * @throws \RuntimeException When the where condition is invalid.
     */
    public static function checkQuery(EncodePropertyValueFromWidgetEvent $event)
    {
        $where  = $event->getValue();
        $values = $event->getPropertyValueBag();

        if ($where)
        {
            $objDB = \Database::getInstance();

            $strTableName  = $values->getPropertyValue('select_table');
            $strColNameId  = $values->getPropertyValue('select_id');
            $strSortColumn = $values->getPropertyValue('select_sorting') ?: $strColNameId;

            $query = sprintf('
                SELECT %1$s.*
                FROM %1$s%2$s
                ORDER BY %1$s.%3$s',
                // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                $strTableName,                            // 1
                ($where ? ' WHERE ('.$where.')' : false), // 2
                $strSortColumn                            // 3
            // @codingStandardsIgnoreEnd
            );

            try
            {
                $objDB
                    ->prepare($query)
                    ->execute();
            }
            catch(\Exception $e)
            {
                throw new \RuntimeException(sprintf(
                    '%s %s',
                    $GLOBALS['TL_LANG']['tl_metamodel_attribute']['sql_error'],
                    $e->getMessage()
                    )
                );
            }
        }
    }
}
