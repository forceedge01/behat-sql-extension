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
     * @param string $whereClause The string column values.
     */
    public function __construct(Representations\QueryParams $queryParams, $whereClause)
    {
        $this->whereClause = $whereClause;
        parent::__construct($queryParams);
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
