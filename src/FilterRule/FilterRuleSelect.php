<?php

/**
 * This file is part of MetaModels/attribute_select.
 *
 * (c) 2012-2021 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_select
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_select/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeSelectBundle\FilterRule;

use Contao\System;
use Doctrine\DBAL\Connection;
use MetaModels\AttributeSelectBundle\Attribute\AbstractSelect;
use MetaModels\Filter\FilterRule;

/**
 * This is the MetaModelFilterRule class for handling select fields.
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
     * Database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * {@inheritDoc}
     */
    public function __construct(AbstractSelect $objAttribute, $strValue, Connection $connection = null)
    {
        parent::__construct();

        if (null === $connection) {
            // @codingStandardsIgnoreStart Silencing errors is discouraged
            @trigger_error(
                'Connection is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardIgnoreEnd
            $connection = System::getContainer()->get('database_connection');
        }

        $this->objAttribute = $objAttribute;
        $this->value        = $strValue;
        $this->connection   = $connection;
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

        $matches = $this->connection->createQueryBuilder()
            ->select('id')
            ->from($this->objAttribute->getMetaModel()->getTableName(), 't')
            ->where('t.' . $this->objAttribute->getColName() . ' IN (:ids)')
            ->setParameter('ids', $values, Connection::PARAM_STR_ARRAY)
            ->execute();

        return $matches->fetchAll(\PDO::FETCH_COLUMN);
    }
}
