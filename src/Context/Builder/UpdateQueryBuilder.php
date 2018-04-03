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
     */
    public function __construct(Representations\QueryParams $queryParams, $updateClause)
    {
        $this->updateClause = $updateClause;
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
        $table = $this->query->getQueryParams()->getEntity()->getEntityName();

        $sql = "UPDATE {$table} SET {$this->updateClause} WHERE {$this->whereClause}";
        $this->query->setSql($sql);

        return $this;
    }
}
