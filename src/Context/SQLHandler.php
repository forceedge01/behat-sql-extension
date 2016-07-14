<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\BehatContext;
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
class SQLHandler extends BehatContext implements Interfaces\SQLHandlerInterface
{
    /**
     * Entity being worked on.
     */
    protected $entity;

    /**
     * Last query executed.
     */
    private $lastQuery;

    /**
     * The id of the last sql statement executed.
     */
    private $lastId;

    /**
     * The database name.
     */
    private $databaseName;

    /**
     * The table name.
     */
    private $tableName;

    /**
     * The table's primary key.
     */
    private $primaryKey;

    /**
     * The clause type being executed.
     */
    private $commandType;

    /**
     * The allowed command types to be executed.
     */
    private $allowedCommandTypes = [
        'select',
        'insert',
        'delete',
        'update'
    ];

    /**
     * The database connection manager.
     */
    private $dbManager;

    /**
     * The key store.
     */
    private $keyStore;

    /**
     * Construct the object.
     *
     * @param Interfaces\DBManagerInterface $dbManager
     * @param Interfaces\SQLBuilderInterface $sqlBuilder
     * @param Interfaces\KeyStoreInterface $keyStore
     */
    public function __construct(
        Interfaces\DBManagerInterface $dbManager,
        Interfaces\SQLBuilderInterface $sqlBuilder,
        Interfaces\KeyStoreInterface $keyStore
    ) {
        $this->dbManager = $dbManager;
        $this->keyStore = $keyStore;
        $this->sqlBuilder = $sqlBuilder;
    }

    /**
     * Get a dependency.
     *
     * @param string $dependency.
     *
     * @return object
     */
    public function get($dependency)
    {
        if (! property_exists($this, $dependency)) {
            throw new Exception(sprintf('Dependency "%s" not found', $dependency));
        }

        return $this->$dependency;
    }

    /**
     * returns sample data for a data type.
     *
     * @param string $type
     */
    public function sampleData($type)
    {
        return $this->sqlBuilder->sampleData($type);
    }

    /**
     * Set the clause type.
     *
     * @param string $commandType
     */
    public function setCommandType($commandType)
    {
        // Set the clause type debug message.
        $this->debugLog(sprintf('Command type set to: %s', $commandType));

        // Check if the clause given is one of the allowed ones.
        if (! in_array($commandType, $this->allowedCommandTypes)) {
            throw new Exception(sprintf(
                'Invalid command type provided "%s", command type must be one of "%s"',
                $commandType,
                implode(', ', $this->allowedCommandTypes)
            ));
        }

        $this->commandType = $commandType;

        return $this;
    }

    /**
     * Get the clause type.
     *
     * @return string
     */
    public function getCommandType()
    {
        return $this->commandType;
    }

    /**
     * Construct the sql clause.
     *
     * @param string $commandType
     * @param string $glue
     * @param array $columns
     *
     * @return array
     */
    public function constructSQLClause($commandType, $glue, array $columns)
    {
        return $this->sqlBuilder->constructSQLClause($commandType, $glue, $columns);
    }

    public function filterAndConvertToArray($queries)
    {
        // Convert column string to array.
        $columns = $this->sqlBuilder->convertToArray($queries);

        $filteredColumns = [];

        // Check for keywords.
        foreach ($columns as $column => $value) {
            $filteredColumns[$column] = $this->checkForKeyword($value);
        }

        return $filteredColumns;
    }

    /**
     * Sets a behat keyword.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setKeyword($key, $value)
    {
        $this->debugLog(sprintf(
            'Saving keyword "%s" with value "%s"',
            $key,
            $value
        ));

        return $this->keyStore->setKeyword($key, $value);
    }

    /**
     * Fetches a specific keyword from the behat keywords store.
     *
     * @param string $key
     */
    public function getKeyword($key)
    {
        $this->debugLog(sprintf(
            'Retrieving keyword "%s"',
            $key
        ));

        $value = $this->keyStore->getKeyword($key);

        $this->debugLog(sprintf(
            'Retrieved keyword "%s" with value "%s"',
            $key,
            $value
        ));

        return $value;
    }

    /**
     * Checks the value for possible keywords set in behat.yml file.
     *
     * @param string $key
     */
    public function checkForKeyword($key)
    {
        return $this->keyStore->getKeywordFromConfigForKeyIfExists($key);
    }

    /**
     * Prints out messages when in debug mode.
     */
    public function debugLog($log)
    {
        Debugger::log($log);
    }

    /**
     * Executes sql command.
     *
     * @param string $sql
     */
    public function execute($sql)
    {
        $this->debugLog(sprintf('Executing SQL: %s', $sql));
        $this->lastQuery = $sql;
        $statement = $this->dbManager->execute($sql);
        $this->lastId = $this->dbManager->getLastInsertId($this->getEntity());

        // If their is an id, save it!
        if ($this->lastId) {
            $this->handleLastId($this->getEntity(), $this->lastId);
        }

        return $statement;
    }

    /**
     * Save the last insert id in the session for later retrieval.
     */
    protected function saveLastId($entity, $id)
    {
        $this->debugLog(sprintf('Last ID fetched: %d', $id));

        $_SESSION['behat']['GenesisSqlExtension']['last_id'][$entity][$this->getCommandType()][] = $id;
    }

    /**
     * Get all id's inserted for an entity.
     */
    public function getLastIds($entity = null)
    {
        if ($entity) {
            if (isset($_SESSION['behat']['GenesisSqlExtension']['last_id'][$entity])) {
                return $_SESSION['behat']['GenesisSqlExtension']['last_id'][$entity];
            }

            return false;
        }

        return $_SESSION['behat']['GenesisSqlExtension']['last_id'];
    }

    /**
     * Check for any mysql errors.
     */
    public function throwErrorIfNoRowsAffected(Traversable $sqlStatement, $ignoreDuplicate = false)
    {
        return $this->dbManager->throwErrorIfNoRowsAffected($sqlStatement, $ignoreDuplicate);
    }

    /**
     * Errors found then throw exception.
     */
    public function throwExceptionIfErrors(Traversable $sqlStatement)
    {
        return $this->dbManager->throwExceptionIfErrors($sqlStatement);
    }

    /**
     * Gets the last insert id.
     */
    public function getLastId()
    {
        $entity = $this->getUserInputEntity($this->getEntity());

        return $this->getKeyword($entity . '_id');
    }

    /**
     * Quotes value if needed for sql.
     *
     * @param string $val
     *
     * @return string
     */
    public function quoteOrNot($val)
    {
        return $this->sqlBuilder->quoteOrNot($val);
    }

    /**
     * Get the duplicate key from the error message.
     */
    public function getKeyFromDuplicateError($error)
    {
        if (! isset($error[2])) {
            return false;
        }

        // Extract duplicate key and run update using it
        $matches = [];

        if (preg_match('/.*DETAIL:\s*Key (.*)=.*/sim', $error[2], $matches)) {
            // Index 1 holds the name of the key matched
            $key = trim($matches[1], '()');
            echo sprintf('Duplicate record, running update using "%s"...%s', $key, PHP_EOL);

            return $key;
        }

        return false;
    }

    /**
     * Set all keys from the current entity.
     *
     * @param string $entity
     * @param string $criteria
     */
    public function setKeywordsFromCriteria($entity, $criteria)
    {
        $result = $this->fetchByCriteria(
            $entity,
            $criteria
        );

        return $this->setKeywordsFromRecord(
            $entity,
            $result[0]
        );
    }

    /**
     * Get a record by a criteria.
     *
     * @param string $entity
     * @param string $criteria
     */
    public function fetchByCriteria($entity, $criteria)
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s', $entity, $criteria);
        $statement = $this->execute($sql);
        $this->throwErrorIfNoRowsAffected($statement);
        $result = $statement->fetchAll();

        if (! $result) {
            throw new Exception(
                sprintf(
                    'Unable to fetch result using criteria "%s" on "%s"',
                    $criteria,
                    $entity
                )
            );
        }

        return $result;
    }

    /**
     * Set the record as keywords for re-use.
     *
     * @param string $entity
     * @param array $record
     */
    public function setKeywordsFromRecord($entity, array $record)
    {
        // Normalise the entity.
        $entity = $this->getUserInputEntity($entity);

        // Set all columns as reusable.
        foreach ($record as $column => $value) {
            if (! is_numeric($column)) {
                $this->setKeyword(sprintf('%s.%s', $entity, $column), $value);
                // For backward compatibility.
                $this->setKeyword(sprintf('%s_%s', $entity, $column), $value);
            }
        }

        return $record;
    }

    /**
     * Do what needs to be done with the last insert id.
     */
    public function handleLastId($entity, $id)
    {
        $entity = $this->getUserInputEntity($entity);
        $this->lastId = $id;
        $entity = $this->makeSQLUnsafe($entity);
        $this->saveLastId($entity, $this->lastId);
        $this->setKeyword($entity . '.' . $this->primaryKey, $this->lastId);
        // For backward compatibility.
        $this->setKeyword($entity . '_' . $this->primaryKey, $this->lastId);
    }

    /**
     * Gets table columns and its values.
     *
     * @return array
     */
    public function getTableColumns($entity)
    {
        $columnClause = [];

        // Get all columns for insertion
        $allColumns = array_merge($this->getRequiredTableColumns($entity), $this->sqlBuilder->getColumns());

        // Set values for columns
        foreach ($allColumns as $col => $type) {
            // Check if a column is provided, if not use sample data to fill in.
            if (isset($this->sqlBuilder->getColumns()[$col])) {
                // If the value is provided get value and check if its a keyword.
                $value = $this->checkForKeyword($this->sqlBuilder->getColumns()[$col]);

                // If the value is not a keyword then use the value provided as is.
                if (! $value) {
                    $value = $this->sqlBuilder->getColumns()[$col];
                }

                // Assign value back to the column.
                $columnClause['`'.$col.'`'] = $this->quoteOrNot($value);
            } else {
                $columnClause['`'.$col.'`'] = $this->sampleData($type);
            }
        }

        $columnNames = implode(', ', array_keys($columnClause));
        $columnValues = implode(', ', $columnClause);

        return [$columnNames, $columnValues];
    }

    /**
     * Get the entity the way the user had inputted it.
     */
    public function getUserInputEntity($entity)
    {
        // Get rid of any special chars introduced.
        $entity = $this->makeSQLUnsafe($entity);

        // Only replace first occurrence.
        return preg_replace('/' . $this->getParams()['DBPREFIX'] . '/', '', $entity, 1);
    }

    /**
     * @param  TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToQueries(TableNode $node)
    {
        return $this->sqlBuilder->convertTableNodeToQueries($node);
    }

    /**
     * Convert an array to a genesis query.
     *
     * @param array $columns
     *
     * @return string
     */
    public function convertToQuery(array $columns)
    {
        $query = '';

        foreach ($columns as $column => $value) {
            $query .= sprintf('%s:%s,', $column, $value);
        }

        return trim($query, ',');
    }

    /**
     * @param  TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToSingleContextClause(TableNode $node)
    {
        return $this->sqlBuilder->convertTableNodeToSingleContextClause($node);
    }

    /**
     * Checks if the command executed affected any rows.
     */
    public function hasFetchedRows(Traversable $sqlStatement)
    {
        return $this->dbManager->hasFetchedRows($sqlStatement);
    }

    /**
     * Make a string SQL safe.
     */
    public function makeSQLSafe($string)
    {
        $string = str_replace('', '', $string);

        $chunks = explode('.', $string);

        return implode('.', $chunks);
    }

    /**
     * Remove any quote chars.
     */
    public function makeSQLUnsafe($string)
    {
        return str_replace('`', '', $string);
    }

    /**
     * Set the entity for further processing.
     */
    public function setEntity($entity)
    {
        $this->debugLog(sprintf('ENTITY: %s', $entity));

        $expectedEntity = $this->makeSQLSafe($this->getParams()['DBPREFIX'] . $entity);

        $this->debugLog(sprintf('SET ENTITY: %s', $expectedEntity));

        // Concatinate the entity with the sqldbprefix value only if not already done.
        if ($expectedEntity !== $this->entity) {
            $this->entity = $expectedEntity;
        }

        // Set the database and table name.
        if (strpos($this->entity, '.') !== false) {
            list($this->databaseName, $this->tableName) = explode('.', $this->entity, 2);
        } else {
            $this->databaseName = $this->getParams()['DBNAME'];
            $this->tableName = $entity;
        }

        // Set the primary key for the current table.
        $primaryKey = $this->dbManager->getPrimaryKeyForTable($this->databaseName, $this->tableName);

        if (! $primaryKey) {
            $primaryKey = 'id';
        }

        $this->primaryKey = $primaryKey;

        $this->debugLog(sprintf('PRIMARY KEY: %s', $this->primaryKey));

        return $this;
    }

    /**
     * Get the entity on which actions are being performed.
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Get the database name.
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    /**
     * Get the table name.
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get the required table columns for a table.
     *
     * @param string $entity
     *
     * @return array
     */
    public function getRequiredTableColumns($table)
    {
        return $this->dbManager->getRequiredTableColumns($table);
    }

    /**
     * @return array
     */
    private function getParams()
    {
        return $this->dbManager->getParams();
    }
}
