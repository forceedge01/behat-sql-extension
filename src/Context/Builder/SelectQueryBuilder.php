<?php

namespace Genesis\SQLExtension\Context\Builder;

use Genesis\SQLExtension\Context\Representations;

/**
 * DeleteQuery class.
 */
class SelectQueryBuilder extends QueryBuilder
{
    /**
     * @var string
     */
    private $whereClause;

    /**
     * @var string
     */
    private $selectColumns = '*';

    /**
     * @param Representations\QueryParams $queryParams The query params.
     */
    public function __construct(Representations\QueryParams $queryParams)
    {
        parent::__construct($queryParams);
    }

    /**
     * @param string $whereClause The where clause.
     */
    public function setWhereClause($whereClause)
    {
        $this->whereClause = $whereClause;

        return $this;
    }

    /**
     * @param string $columns The columns to set.
     */
    public function setColumns($columns)
    {
        $this->selectColumns = $columns;

        return $this;
    }

    /**
     * @return $this
     */
    public function buildQuery()
    {
        $table = $this->query->getQueryParams()->getEntity()->getEntityName();

        $sql = "SELECT {$this->selectColumns} FROM {$table} WHERE {$this->whereClause}";
        $this->query->setSql($sql);

        return $this;
    }
}
