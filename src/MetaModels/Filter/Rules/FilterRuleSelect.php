<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
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
    protected $objAttribute;

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
        $values = $this->objAttribute->convertValuesToValueIds(\explode(',', $this->value));
        if (empty($values)) {
            return $values;
        }

        $database = $this->objAttribute->getMetaModel()->getServiceContainer()->getDatabase();
        $matches  = $database
            ->prepare(
                sprintf(
                    'SELECT id FROM %s WHERE %s IN (%s)',
                    $this->objAttribute->getMetaModel()->getTableName(),
                    $this->objAttribute->getColName(),
                    \implode(',', \array_fill(0, \count($values), '?'))
                )
            )
        ->execute($values);

        return $matches->fetchEach('id');
    }
}
