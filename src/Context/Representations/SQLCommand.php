<?php

namespace Genesis\SQLExtension\Context\Representations;

/**
 * SQLClause class.
 */
class SQLCommand extends Representation
{
    /**
     * @var string $table.
     */
    private $table;

    /**
     * @var array $select.
     */
    private $select = [];

    /**
     * @var array $where.
     */
    private $where = [];

    /**
     * @var string $type.
     */
    private $type;

    /**
     * @var string $glue.
     */
    private $glue;

    /**
     * @var array $update.
     */
    private $update = [];

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
     * Get the select.
     *
     * @return array
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * Set the select.
     *
     * @param array $select
     *
     * @return $this
     */
    public function setSelect(array $select)
    {
        $this->select = $select;

        return $this;
    }

    /**
     * Get the where.
     *
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Set the where.
     *
     * @param array $where
     *
     * @return $this
     */
    public function setWhere(array $where)
    {
        $this->where = $where;

        return $this;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the glue.
     *
     * @return string
     */
    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * Set the glue.
     *
     * @param string $glue
     *
     * @return $this
     */
    public function setGlue($glue)
    {
        $this->glue = $glue;

        return $this;
    }

    /**
     * Get the update.
     *
     * @return array
     */
    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * Set the update.
     *
     * @param array $update
     *
     * @return $this
     */
    public function setUpdate(array $update)
    {
        $this->update = $update;

        return $this;
    }
}
