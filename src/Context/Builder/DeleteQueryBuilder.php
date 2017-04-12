<?php

namespace Genesis\SQLExtension\Context\Builder;

use Genesis\SQLExtension\Context\Representations;

/**
 * DeleteQuery class.
 */
class DeleteQueryBuilder extends QueryBuilder
{
    /**
     * @var string
     */
    private $whereClause;

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
     * @return $this
     */
    public function buildQuery()
    {
        $table = $this->query->getQueryParams()->getTable();

        $sql = "DELETE FROM {$table} WHERE {$this->whereClause}";
        $this->query->setSql($sql);

        return $this;
    }
}
