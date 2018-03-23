<?php

namespace Genesis\SQLExtension\Context\Interfaces;

/**
 * DatabaseProvider interface.
 *
 * Methods that are unique to each database used by the sql API.
 */
interface DatabaseProviderInterface
{
    /**
     * Get the DNS connection string for the PDO driver.
     *
     * @param string $dbname
     * @param string $host
     * @param int $port
     *
     * @return string
     */
    public function getPdoDnsString($dbname, $host, $port);

    /**
     * Get the primary key of a table provided.
     *
     * @param string $database
     * @param string $schema
     * @param string $table
     *
     * @return string
     */
    public function getPrimaryKeyForTable($database, $schema, $table);

    /**
     * Get the mandatory table column names excluding the primary key.
     *
     * @param string $database
     * @param string $schema
     * @param string $table
     *
     * @return array The array should contain the following:
     * [
     *     'columnName' => [
     *         'type' => 'dataType',
     *         'length' => 'maxLength'
     *     ]
     * ]
     */
    public function getRequiredTableColumns($database, $schema, $table);

    /**
     * {@inheritDoc}
     */
    public function getLeftDelimiterForReservedWord();

    /**
     * {@inheritDoc}
     */
    public function getRightDelimiterForReservedWord();
}
