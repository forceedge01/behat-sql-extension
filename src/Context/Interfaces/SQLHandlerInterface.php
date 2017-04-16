<?php

namespace Genesis\SQLExtension\Context\Interfaces;

use Behat\Gherkin\Node\TableNode;
use Exception;
use Traversable;

/*
 * This file is part of the Behat\SQLExtension
 *
 * (c) Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SQL Handler.
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
interface SQLHandlerInterface
{
    /**
     * Will ignore duplicate inserts.
     */
    const IGNORE_DUPLICATE = true;

    /**
     * Insert command type.
     */
    const COMMAND_TYPE_INSERT = 'insert';

    /**
     * Update command type.
     */
    const COMMAND_TYPE_UPDATE = 'update';

    /**
     * Select command type.
     */
    const COMMAND_TYPE_SELECT = 'select';

    /**
     * Delete command type.
     */
    const COMMAND_TYPE_DELETE = 'delete';

    /**
     * returns sample data for a data type.
     */
    public function sampleData($type);

    /**
     * Get the clause type.
     */
    public function getCommandType();

    /**
     * Set the clause type.
     */
    public function setCommandType($commandType);

    /**
     * Constructs a clause based on the glue, to be used for where and update clause.
     *
     * @param string $commandType
     * @param string $glue
     * @param array $columns
     */
    public function constructSQLClause($commandType, $glue, array $columns);

    /**
     * Sets a behat keyword.
     */
    public function setKeyword($key, $value);

    /**
     * Fetches a specific keyword from the behat keywords store.
     */
    public function getKeyword($key);

    /**
     * Checks the value for possible keywords set in behat.yml file.
     */
    public function checkForKeyword($value);

    /**
     * Prints out messages when in debug mode.
     */
    public function debugLog($log);

    /**
     * Get all id's inserted for an entity.
     */
    public function getLastIds($entity = null);

    /**
     * Check for any mysql errors.
     */
    public function throwErrorIfNoRowsAffected(Traversable $sqlStatement, $ignoreDuplicate = false);

    /**
     * Errors found then throw exception.
     */
    public function throwExceptionIfErrors(Traversable $sqlStatement);

    /**
     * Gets the last insert id.
     */
    public function getLastId();

    /**
     * Quotes value if needed for sql.
     */
    public function quoteOrNot($val);

    /**
     * Get the duplicate key from the error message.
     */
    public function getKeyFromDuplicateError($error);

    /**
     * Set all keys from the current entity.
     * 
     * @param string $entity
     * @param string $criteria
     */
    public function setKeywordsFromCriteria($entity, $criteria);

    /**
     * Get a record by a criteria.
     * 
     * @param string $entity
     * @param string $criteria
     */
    public function fetchByCriteria($entity, $criteria);

    /**
     * Set the record as keywords for re-use.
     * 
     * @param string $entity
     * @param array $record
     */
    public function setKeywordsFromRecord($entity, array $record);

    /**
     * Do what needs to be done with the last insert id.
     */
    public function handleLastId($entity, $id);

    /**
     * Get the entity the way the user had inputted it.
     */
    public function getUserInputEntity($entity);

    /**
     * @param  TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToQueries(TableNode $node);

    /**
     * Filter keywords and convert queries to an array.
     *
     * @param string $columns
     *
     * @return array
     */
    public function convertToFilteredArray($columns);

    /**
     * @param  TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToSingleContextClause(TableNode $node);

    /**
     * Checks if the command executed affected any rows.
     */
    public function hasFetchedRows(Traversable $sqlStatement);

    /**
     * Set the entity for further processing.
     */
    public function setEntity($entity);

    /**
     * Get the entity on which actions are being performed.
     */
    public function getEntity();

    /**
     * Get the database name.
     */
    public function getDatabaseName();

    /**
     * Get the table name.
     */
    public function getTableName();
}
