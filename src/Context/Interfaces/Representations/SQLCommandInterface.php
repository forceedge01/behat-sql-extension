<?php

namespace Genesis\SQLExtension\Context\Interfaces\Representations;

/**
 * SQLClause class.
 */
interface SQLCommandInterface
{
    /**
     * Get the table.
     *
     * @return string
     */
    public function getTable();

    /**
     * Set the table.
     *
     * @param string $table
     *
     * @return $this
     */
    public function setTable($table);

    /**
     * Get the where.
     *
     * @return array
     */
    public function getWhere();

    /**
     * Add a where condition.
     *
     * @param string $column The column to set.
     * @param string $value The value for the column.
     *
     * @return $this
     */
    public function addWhere($column, $value);

    /**
     * Set where params.
     *
     * @param array $where The where criteria.
     *
     * @return $this
     */
    public function setWhere(array $where);

    /**
     * Get the columns set.
     *
     * @return array
     */
    public function getColumns();

    /**
     * Add a column.
     *
     * @param string $column The column to set.
     * @param string|null $value The value to set.
     *
     * @return $this
     */
    public function addColumn($column, $value = null);

    /**
     * Set columns.
     *
     * @param array $columns The columns to set.
     *
     * @return $this
     */
    public function setColumns(array $columns);

    /**
     * Get the type.
     *
     * @return string
     */
    public function getType();

    /**
     * Set the type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type);

    /**
     * Return only columns set.
     *
     * @return array
     */
    public function getColumnsOnly();

    /**
     * Return only values set.
     *
     * @return array
     */
    public function getValuesOnly();

    /**
     * Get the limit.
     *
     * @return int
     */
    public function getLimit();

    /**
     * Set the limit.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit($limit);

    /**
     * Get the order.
     *
     * @return string
     */
    public function getOrder();

    /**
     * Set the order.
     *
     * @param string $columns The columns separated by commas.
     * @param string $order The ordering.
     *
     * @return $this
     */
    public function setOrder($column, $order);
}
