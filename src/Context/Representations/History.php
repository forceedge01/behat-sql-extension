<?php

namespace Genesis\SQLExtension\Context\Representations;

/**
 * History class.
 */
class History extends Representation
{
    /**
     * @var string $entity.
     */
    private $entity;

    /**
     * @var string $sql.
     */
    private $sql;

    /**
     * @var int|null $lastId.
     */
    private $lastId;

    /**
     * Get the entity.
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the entity.
     *
     * @param string $entity
     *
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
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

    /**
     * Get the lastId.
     *
     * @return int|null
     */
    public function getLastId()
    {
        return $this->lastId;
    }

    /**
     * Set the lastId.
     *
     * @param int|null $lastId
     *
     * @return $this
     */
    public function setLastId($lastId)
    {
        $this->lastId = $lastId;

        return $this;
    }
}
