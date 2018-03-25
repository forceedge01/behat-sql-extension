<?php

namespace Genesis\SQLExtension\Context\Interfaces;

use Traversable;

interface DBManagerInterface
{
    /**
     * Get params.
     *
     * @return array
     */
    public function getParams();

    /**
     * Gets the connection for query execution.
     */
    public function getConnection();

    /**
     * @param string $database
     * @param string $schema
     * @param string $table
     *
     * @return string|bool
     */
    public function getPrimaryKeyForTable($database, $schema, $table);

    /**
     * @param string $sql
     *
     * @return Traversable
     */
    public function execute($sql);

    /**
     * @param Traversable $statement
     */
    public function hasFetchedRows(Traversable $statement);

    /**
     * Gets a column list for a table with their type.
     *
     * @param string $database
     * @param string $schema
     * @param string $table
     */
    public function getRequiredTableColumns($database, $schema, $table);

    /**
     * Get the last insert id.
     *
     * @param string $table For compatibility with postgres.
     *
     * @return int|null
     */
    public function getLastInsertId($table = null);

    /**
     * Check for any mysql errors.
     */
    public function throwErrorIfNoRowsAffected(Traversable $sqlStatement, $ignoreDuplicate = false);

    /**
     * Errors found then throw exception.
     *
     * @param Traversable $sqlStatement
     *
     * @throws Exception if errors found.
     *
     * @return boolean
     */
    public function throwExceptionIfErrors(Traversable $sqlStatement);

    /**
     * Get the first value from a PDO statement.
     *
     * @param Traversable $statement The statement to work with.
     *
     * @return mixed.
     */
    public function getFirstValueFromStatement(Traversable $statement);

    /**
     * Close the pdo connection.
     *
     * @return void
     */
    public function closeConnection();

    /**
     * Close pdo statement.
     *
     * @param Traversable $statement The statement to close.
     *
     * @return $this self.
     */
    public function closeStatement(Traversable $statement);
}
