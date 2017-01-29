<?php

namespace Genesis\SQLExtension\Context\Representations;

use Genesis\SQLExtension\Context\Interfaces\Representations\SQLCommandInterface;

/**
 * SQLClause class.
 */
class SQLCommand extends Representation implements SQLCommandInterface
{
    /**
     * @var string $table.
     */
    private $table;

    /**
     * @var array $column.
     */
    private $columns = [];

    /**
     * @var array $where.
     */
    private $where = [];

    /**
     * @var string $type.
     */
    private $type;

    /**
     * @var int $limit.
     */
    private $limit;

    /**
     * @var string The order.
     */
    private $order;

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
     * Get the where.
     *
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Add a where condition.
     *
     * @param string $column The column to set.
     * @param string|int|null $value The value for the column.
     *
     * @return $this
     */
    public function addWhere($column, $value)
    {
        $this->where[$column] = $value;

        return $this;
    }

    /**
     * Set where params.
     *
     * @param array $where The where criteria.
     *
     * @return $this
     */
    public function setWhere(array $where)
    {
        $this->where = $where;

        return $this;
    }

    /**
     * Get the columns set.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Add a column.
     *
     * @param string $column The column to set.
     * @param string|int|null $value The value to set.
     *
     * @return $this
     */
    public function addColumn($column, $value = null)
    {
        $this->columns[$column] = $value;

        return $this;
    }

    /**
     * Set columns.
     *
     * @param array $columns The columns to set.
     *
     * @return $this
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;

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
     * Return only columns set.
     *
     * @return array
     */
    public function getColumnsOnly()
    {
        return array_keys($this->columns);
    }

    /**
     * Return only values set.
     *
     * @return array
     */
    public function getValuesOnly()
    {
        return array_values($this->columns);
    }

    /**
     * Get the limit.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set the limit.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get the order.
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the order.
     *
     * @param string $columns The columns separated by commas.
     * @param string $order The ordering.
     *
     * @return $this
     */
    public function setOrder($column, $order)
    {
        $chunks = explode(',', $column);
        array_walk($chunks, function (&$value) {
            $value = '`' . trim($value) . '`';
        });

        $this->order = implode(', ', $chunks) . ' ' . $order;

        return $this;
    }
}
