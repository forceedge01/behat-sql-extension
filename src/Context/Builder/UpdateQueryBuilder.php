<?php

namespace Genesis\SQLExtension\Context\Builder;

use Genesis\SQLExtension\Context\Representations;

/**
 * UpdateQuery class.
 */
class UpdateQueryBuilder extends QueryBuilder
{
    /**
     * @var string
     */
    private $updateClause;

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @param Representations\QueryParams $queryParams The query params.
     * @param string $updateClause The string column names.
     * @param string $whereClause The string column values.
     */
    public function __construct(Representations\QueryParams $queryParams, $updateClause, $whereClause)
    {
        $this->updateClause = $updateClause;
        $this->whereClause = $whereClause;
        parent::__construct($queryParams);
    }

    /**
     * @return $this
     */
    public function buildQuery()
    {
        $table = $this->query->getQueryParams()->getTable();

        $sql = "UPDATE {$table} SET {$this->updateClause} WHERE {$this->whereClause}";
        $this->query->setSql($sql);

        return $this;
    }
}
