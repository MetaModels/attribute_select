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

//$GLOBALS['METAMODELS']['attributes']['select']['image'] = 'system/modules/metamodelsattribute_select/html/select.png';

$GLOBALS['TL_EVENTS'][\ContaoCommunityAlliance\Contao\EventDispatcher\Event\CreateEventDispatcherEvent::NAME][] =
    'MetaModels\DcGeneral\Events\Table\Attribute\Select\PropertyAttribute::registerEvents';

$GLOBALS['TL_EVENTS'][\ContaoCommunityAlliance\Contao\EventDispatcher\Event\CreateEventDispatcherEvent::NAME][] =
    'MetaModels\DcGeneral\Events\Table\Attribute\Select\PropertySelectWhere::registerEvents';

$GLOBALS['TL_EVENT_SUBSCRIBERS'][] = 'MetaModels\Attribute\Select\AttributeTypeFactory';
$GLOBALS['TL_EVENT_SUBSCRIBERS'][] = 'MetaModels\DcGeneral\Events\MetaModels\Select\BackendSubscriber';
