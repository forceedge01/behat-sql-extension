<?php

namespace Genesis\SQLExtension\Context\Interfaces;

use Behat\Gherkin\Node\TableNode;
use Exception;
use Genesis\SQLExtension\Context\Representations;
use Genesis\SQLExtension\Context\Representations\Entity;
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
     *
     * @param array $type
     */
    public function sampleData(array $type);

    /**
     * Get the clause type.
     */
    public function getCommandType();

    /**
     * Set the clause type.
     *
     * @param mixed $commandType
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
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function setKeyword($key, $value);

    /**
     * Fetches a specific keyword from the behat keywords store.
     *
     * @param mixed $key
     */
    public function getKeyword($key);

    /**
     * Checks the value for possible keywords set in behat.yml file.
     *
     * @param mixed $value
     */
    public function checkForKeyword($value);

    /**
     * Prints out messages when in debug mode.
     *
     * @param mixed $log
     */
    public function debugLog($log);

    /**
     * Get all id's inserted for an entity.
     *
     * @param null|mixed $entity
     */
    public function getLastIds($entity = null);

    /**
     * Check for any mysql errors.
     *
     * @param mixed $ignoreDuplicate
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
     *
     * @param mixed $val
     */
    public function quoteOrNot($val);

    /**
     * Get the duplicate key from the error message.
     *
     * @param mixed $error
     */
    public function getKeyFromDuplicateError($error);

    /**
     * Set all keys from the current entity.
     *
     * @param Representations\Query $query
     *
     * @return void
     */
    public function setKeywordsFromQuery(Representations\Query $query);

    /**
     * Get a record by a criteria.
     *
     * @param Representations\Query $query
     *
     * @return array
     */
    public function fetchByQuery(Representations\Query $query);

    /**
     * Set the record as keywords for re-use.
     *
     * @param string $entity
     * @param array $record
     */
    public function setKeywordsFromRecord($entity, array $record);

    /**
     * Do what needs to be done with the last insert id.
     *
     * @param Entity $entity
     * @param int $id
     */
    public function handleLastId(Entity $entity, $id);

    /**
     * @param  TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToQueries(TableNode $node);

    /**
     * Filter keywords and convert queries to an array.
     *
     * @param array $values
     *
     * @return array
     */
    public function convertToResolvedArray(array $values);

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
     *
     * @param mixed $entity
     */
    public function resolveEntity($entity);

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
