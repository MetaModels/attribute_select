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
 * @author      Christian de la Haye <service@delahaye.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['typeOptions']['select']    = 'Auswahl';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_sorting']           = array('Einträge Sortierung', 'Bitte einen Eintrag für die Sortierung auswählen.');
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_where']             = array('SQL-Bedingung', 'Mit der Bedingung kann die Liste der Auswahlpunkte eingeschränkt werden.');
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['sql_error']                = 'Die SQL-Bedingung erzeugt einen Fehler.';