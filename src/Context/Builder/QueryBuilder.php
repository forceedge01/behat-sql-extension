<?php

namespace Genesis\SQLExtension\Context\Builder;

use Genesis\SQLExtension\Context\Representations;

/**
 * QueryBuilder class.
 */
abstract class QueryBuilder extends Builder
{
    private Representations\Query $query;
    /**
     * @param Representations\QueryParams $queryParams The query params.
     */
    public function __construct(Representations\QueryParams $queryParams)
    {
        $this->query = new Representations\Query($queryParams);
    }

    abstract public function buildQuery();

    /**
     * @return $this
     */
    public function inferType()
    {
        $this->throwExceptionIfNotSet($this->query->getSql(), 'sql');
        $this->query->setType(strtolower(strtok($this->query->getSql(), ' ')));

        return $this;
    }

    /**
     * @return Representations\Query
     */
    public function getResult()
    {
        return $this->query;
    }
}
