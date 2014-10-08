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
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Filter\Rules;

use MetaModels\Filter\FilterRule;
use MetaModels\Attribute\IAttribute;
use MetaModels\Attribute\Select\Select;

/**
 * This is the MetaModelFilterRule class for handling select fields.
 *
 * @package    MetaModels
 * @subpackage AttributeSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class FilterRuleSelect extends FilterRule
{
    /**
     * The attribute this rule applies to.
     *
     * @var IAttribute
     */
    protected $objAttribute = null;

    /**
     * {@inheritDoc}
     */
    public function __construct(Select $objAttribute, $strValue)
    {
        parent::__construct();

        $this->objAttribute = $objAttribute;
        $this->value        = $strValue;
    }

    /**
     * Convert a list of aliases to id list.
     *
     * @return int[]
     */
    public function sanitizeValue()
    {
        $strTableNameId  = $this->objAttribute->get('select_table');
        $strColNameId    = $this->objAttribute->get('select_id');
        $strColNameAlias = $this->objAttribute->get('select_alias');

        $arrValues = explode(',', $this->value);

        $objDB = \Database::getInstance();

        if ($strColNameAlias)
        {
            $objSelectIds = $objDB
                ->prepare(sprintf(
                    'SELECT %s FROM %s WHERE %s IN (%s)',
                    $strColNameId,
                    $strTableNameId,
                    $strColNameAlias,
                    implode(',', array_fill(0, count($arrValues), '?'))
                ))
                ->executeUncached($arrValues);

            $arrValues = $objSelectIds->fetchEach($strColNameId);
        } else {
            $arrValues = array_map('intval', $arrValues);
        }
        return $arrValues;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingIds()
    {
        $arrValues = $this->sanitizeValue();
        if (!$arrValues)
        {
            return array();
        }

        $objDB      = \Database::getInstance();
        $objMatches = $objDB->executeUncached(sprintf(
            'SELECT id FROM %s WHERE %s IN (%s)',
            $this->objAttribute->getMetaModel()->getTableName(),
            $this->objAttribute->getColName(),
            implode(',', $arrValues)
        ));

        return $objMatches->fetchEach('id');
    }
}
