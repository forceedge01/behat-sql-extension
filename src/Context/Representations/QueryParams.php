<?php

namespace Genesis\SQLExtension\Context\Representations;

/**
 * QueryParams class.
 */
class QueryParams extends Representation
{
    /**
     * @var string $table.
     */
    private $table;

    /**
     * @var array The rawValues to work with.
     */
    private $rawValues;

    /**
     * @var array The resolved values.
     */
    private $resolvedValues;

    /**
     * @param string $table The table.
     * @param array $rawValues The values array.
     * @param array $resolvedValues The resolved values.
     */
    public function __construct($table, array $rawValues, array $resolvedValues = [])
    {
        $this->table = $table;
        $this->rawValues = $rawValues;
        $this->resolvedValues = $resolvedValues;
    }

    /**
     * Get the table.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set the table.
     *
     * @param string $table
     *
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get the rawValues.
     *
     * @return array
     */
    public function getRawValues()
    {
        return $this->rawValues;
    }

    /**
     * Set the rawValues.
     *
     * @param array $rawValues
     *
     * @return $this
     */
    public function setRawValues($rawValues)
    {
        $this->rawValues = $rawValues;

        return $this;
    }

    /**
     * Get the resolvedValues.
     *
     * @return array
     */
    public function getResolvedValues()
    {
        return $this->resolvedValues;
    }

    /**
     * Set the resolvedValues.
     *
     * @param array $resolvedValues
     *
     * @return $this
     */
    public function setResolvedValues($resolvedValues)
    {
        $this->resolvedValues = $resolvedValues;

        return $this;
    }
}
