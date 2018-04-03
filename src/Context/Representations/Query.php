<?php

namespace Genesis\SQLExtension\Context\Representations;

/**
 * QueryParams class.
 */
class Query extends Representation
{
    /**
     * @var QueryParams $queryParams.
     */
    private $queryParams;

    /**
     * @var string $query;
     */
    private $sql;

    /**
     * @var string $type The type of query;
     */
    private $type;

    /**
     * @param QueryParams $queryParams The query params to work with.
     */
    public function __construct(QueryParams $queryParams)
    {
        $this->queryParams = $queryParams;
    }

    /**
     * @return string Defines the type of the query command.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string Defines the type of the query command.
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the Query params.
     *
     * @return string
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Get the sql.
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }
    
    /**
     * Set the sql.
     *
     * @param string $sql
     *
     * @return $this
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
    
        return $this;
    }
}
