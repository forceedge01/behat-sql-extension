<?php

namespace Genesis\SQLExtension\Context\Representations;

/**
 * QueryParams class.
 */
class QueryParams extends Representation
{
    /**
     * @var string.
     */
    private $entity;

    /**
     * @var array The rawValues to work with.
     */
    private $rawValues;

    /**
     * @var array The resolved values.
     */
    private $resolvedValues;

    /**
     * @param Entity $entity The table.
     * @param array $rawValues The values array.
     * @param array $resolvedValues The resolved values.
     */
    public function __construct(Entity $entity, array $rawValues, array $resolvedValues = [])
    {
        $this->entity = $entity;
        $this->rawValues = $rawValues;
        $this->resolvedValues = $resolvedValues;
    }

    /**
     * Get the table.
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the table.
     *
     * @param string $table
     *
     * @return $this
     */
    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;

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
