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
 * @author      Andreas Isaak <andy.jared@googlemail.com>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Filter\Rules;

use MetaModels\Attribute\Select\AbstractSelect;
use MetaModels\Filter\FilterRule;

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
     * @var AbstractSelect
     */
    protected $objAttribute = null;

    /**
     * The value to search.
     *
     * @var string
     */
    protected $value;

    /**
     * {@inheritDoc}
     */
    public function __construct(AbstractSelect $objAttribute, $strValue)
    {
        parent::__construct();

        $this->objAttribute = $objAttribute;
        $this->value        = $strValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingIds()
    {
        $arrValues = $this->objAttribute->convertValuesToValueIds(explode(',', $this->value));
        if (empty($arrValues)) {
            return $arrValues;
        }

        $objDB      = $this->objAttribute->getMetaModel()->getServiceContainer()->getDatabase();
        $objMatches = $objDB->execute(sprintf(
            'SELECT id FROM %s WHERE %s IN (%s)',
            $this->objAttribute->getMetaModel()->getTableName(),
            $this->objAttribute->getColName(),
            implode(',', $arrValues)
        ));

        return $objMatches->fetchEach('id');
    }
}
