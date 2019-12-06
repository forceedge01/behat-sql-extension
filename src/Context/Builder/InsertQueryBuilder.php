<?php

namespace Genesis\SQLExtension\Context\Builder;

use Genesis\SQLExtension\Context\Representations;

/**
 * InsertQuery class.
 */
class InsertQueryBuilder extends QueryBuilder
{
    /**
     * @var string
     */
    private $stringColumnNames;

    /**
     * @var string
     */
    private $stringColumnValues;

    /**
     * @param Representations\QueryParams $queryParams The query params.
     * @param string $stringColumnNames The string column names.
     * @param string $stringColumnValues The string column values.
     */
    public function __construct(Representations\QueryParams $queryParams, $stringColumnNames, $stringColumnValues)
    {
        $this->stringColumnNames = $stringColumnNames;
        $this->stringColumnValues = $stringColumnValues;
        parent::__construct($queryParams);
    }

    /**
     * @return $this
     */
    public function buildQuery()
    {
        $table = $this->query->getQueryParams()->getEntity()->getEntityName();

        $sql = "INSERT INTO {$table} ({$this->stringColumnNames}) VALUES ({$this->stringColumnValues})";
        $this->query->setSql($sql);

        return $this;
    }
}
