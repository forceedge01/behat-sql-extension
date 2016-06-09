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
     * @param string $entity
     * 
     * @result string
     */
    public function getPrimaryKeyForTable($database, $table);

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
     * @param string $table
     */
    public function getRequiredTableColumns($table);

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
}
