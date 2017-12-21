<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\Context;
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
abstract class SQLHandler implements Context, Interfaces\SQLHandlerInterface
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
    protected $primaryKey;

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
     * Holds the history of commands executed.
     */
    private $sqlHistory;

    /**
     * @var Representations\SQLCommand.
     */
    protected $queryParams;

    /**
     * Construct the object.
     *
     * @param Interfaces\DBManagerInterface $dbManager
     * @param Interfaces\SQLBuilderInterface $sqlBuilder
     * @param Interfaces\KeyStoreInterface $keyStore
     * @param Interfaces\SQLHistoryInterface|null $sqlHistory
     */
    public function __construct(
        Interfaces\DBManagerInterface $dbManager,
        Interfaces\SQLBuilderInterface $sqlBuilder,
        Interfaces\KeyStoreInterface $keyStore,
        Interfaces\SQLHistoryInterface $sqlHistory = null
    ) {
        $this->dbManager = $dbManager;
        $this->keyStore = $keyStore;
        $this->sqlBuilder = $sqlBuilder;
        $this->sqlHistory = $sqlHistory;
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

    /**
     * Filter keywords and convert to array.
     *
     * @param string $query The queries to fitler.
     *
     * @return array
     */
    public function convertToResolvedArray(array $values)
    {
        // Match all external query references.
        $columns = $this->get('sqlBuilder')->parseExternalQueryReferences($this->convertToQuery($values));
        $columns = $this->get('sqlBuilder')->convertToArray($columns);

        $filteredColumns = [];

        // Check for keywords.
        foreach ($columns as $column => $value) {
            // Check if the value provided is a keyword or an external ref, if so get value.
            if ($this->get('sqlBuilder')->isExternalReferencePlaceholder($value)) {
                $value = $this->resolveExternalReferencePlaceholder($value);
            }

            $filteredColumns[$column] = $value;
        }

        return $filteredColumns;
    }

    /**
     * @param string $placeholder The placeholder to resolve.
     *
     * @return string
     */
    private function resolveExternalReferencePlaceholder($placeholder)
    {
        Debugger::log(sprintf('Resolving external ref: "%s"', $placeholder));

        $externalRef = $this->get('sqlBuilder')->getRefFromPlaceholder($placeholder);

        // Execute query and get placeholder back.
        $query = $this
            ->get('sqlBuilder')
            ->getSQLQueryForExternalReference(
                $externalRef,
                $this->getParams()['DBPREFIX']
            );

        $this->setCommandType('select');
        $statement = $this->execute($query->getSql());
        $this->throwExceptionIfErrors($statement);
        $this->throwErrorIfNoRowsAffected($statement);

        $placeholderValue = $this->get('dbManager')->getFirstValueFromStatement($statement)[0];

        Debugger::log(sprintf('Resolved external ref placeholder: "%s"', $placeholderValue));

        return $placeholderValue;
    }

    /**
     * @param string $query
     *
     * @depreciated Use convertToResolvedArray instead.
     */
    public function filterAndConvertToArray($query)
    {
        $this->debugLog(sprintf('Depreciated method "%s", use convertToResolvedArray instead.', __FUNCTION__));

        return $this->convertToResolvedArray($query);
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
        return $this->keyStore->getKeywordIfExists($key);
    }

    /**
     * Prints out messages when in debug mode.
     *
     * @param mixed $log
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

        // If last id is not provided, check if it was supplied in the sql for an insert statement.
        if (! $this->lastId and
            $this->getCommandType() === Interfaces\SQLHandlerInterface::COMMAND_TYPE_INSERT and
            isset($this->queryParams->getRawValues()[$this->primaryKey])) {
            $this->lastId = $this->queryParams->getRawValues()[$this->primaryKey];
        }

        if ($this->get('sqlHistory') instanceof Interfaces\SQLHistoryInterface) {
            $this->get('sqlHistory')->addToHistory(
                $this->getCommandType(),
                $this->getUserInputEntity($this->getEntity()),
                $sql,
                $this->lastId
            );
        }

        // If their is an id, save it!
        if ($this->lastId) {
            $this->handleLastId($this->getEntity(), $this->lastId);
        }

        return $statement;
    }

    /**
     * Save the last insert id in the session for later retrieval.
     *
     * @param mixed $entity
     * @param mixed $id
     */
    protected function saveLastId($entity, $id)
    {
        $this->debugLog(sprintf('Last ID fetched: %d', $id));

        $_SESSION['behat']['GenesisSqlExtension']['last_id'][$entity][$this->getCommandType()][] = $id;
    }

    /**
     * Check for any mysql errors.
     *
     * @param mixed $ignoreDuplicate
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

        try {
            return $this->getKeyword($entity . '.' . $this->primaryKey);
        } catch (Exception $e) {
            return false;
        }
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
        return $this->get('sqlBuilder')->quoteOrNot($val);
    }

    /**
     * Get the duplicate key from the error message.
     *
     * @param mixed $error
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
     * @param Representations\Query $query
     *
     * @return array
     */
    public function setKeywordsFromQuery(Representations\Query $query)
    {
        $result = $this->fetchByQuery(
            $query
        );

        return $this->setKeywordsFromRecord(
            $query->getQueryParams()->getTable(),
            $result[0]
        );
    }

    /**
     * Set the record as keywords for re-use.
     *
     * @param string $entity
     * @param array $record
     *
     * @return array
     */
    public function setKeywordsFromRecord($entity, array $record)
    {
        // Normalise the entity.
        $entity = $this->getUserInputEntity($entity);

        // Set all columns as reusable.
        foreach ($record as $column => $value) {
            if (! is_numeric($column)) {
                $this->setKeyword(sprintf('%s.%s', $entity, $column), $value);
            }
        }

        return $record;
    }

    /**
     * Do what needs to be done with the last insert id.
     *
     * @param mixed $entity
     * @param mixed $id
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
     * @param string $entity The table to get the columns of.
     * @param array $overridingColumns The overriding columns.
     * @param mixed $table
     *
     * @return array
     */
    public function getTableColumns($table, array $overridingColumns = array())
    {
        $columnClause = [];

        // Get all columns for insertion
        $allColumns = array_merge(
            $this->getRequiredTableColumns($table),
            $overridingColumns
        );

        // Set values for columns
        foreach ($allColumns as $col => $type) {
            // Check if a column is provided, if not use sample data to fill in.
            if (isset($overridingColumns[$col])) {
                // If the value is provided get value and check if its a keyword.
                $value = $this->checkForKeyword($overridingColumns[$col]);

                // If the value is not a keyword then use the value provided as is.
                if (! $value) {
                    $value = $overridingColumns[$col];
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
     *
     * @param mixed $entity
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
     *
     * @param mixed $string
     */
    public function makeSQLSafe($string)
    {
        $string = str_replace('', '', $string);

        $chunks = explode('.', $string);

        return implode('.', $chunks);
    }

    /**
     * Remove any quote chars.
     *
     * @param mixed $string
     */
    public function makeSQLUnsafe($string)
    {
        return str_replace('`', '', $string);
    }

    /**
     * Get the database name, prefix or not depending on config.
     *
     * @param mixed $prefix
     * @param mixed $entity
     *
     * @return string
     */
    private function getPrefixedDatabaseName($prefix, $entity)
    {
        // If the entity does not already contain the database name, add that.
        if (strpos($entity, '.')  === false) {
            return $prefix .= $this->getParams()['DBNAME'];
        }

        return $this->get('sqlBuilder')
            ->getPrefixedDatabaseName($prefix, $entity);
    }

    /**
     * Set the entity for further processing.
     *
     * @param mixed $entity
     */
    public function setEntity($entity)
    {
        $this->debugLog(sprintf('ENTITY: %s', $entity));

        $this->databaseName = $this->getPrefixedDatabaseName($this->getParams()['DBPREFIX'], $entity);
        $this->tableName = $this->get('sqlBuilder')->getTableName($entity);

        $this->entity = $this->makeSQLSafe($this->databaseName . '.' . $this->tableName);
        $this->debugLog(sprintf('SET ENTITY: %s', $this->entity));

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
     * @param mixed $table
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

    /**
     * Convert an array to a genesis query.
     *
     * @param array $columns
     *
     * @return string
     */
    public function convertToQuery(array $columnsValuePair)
    {
        $query = '';

        foreach ($columnsValuePair as $column => $value) {
            $query .= sprintf('%s:%s,', $column, $value);
        }

        return trim($query, ',');
    }

    /**
     * Get all id's inserted for an entity.
     *
     * @param null|mixed $entity
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
     * Get a record by a criteria.
     *
     * @param Representations\Query $query
     *
     * @return array
     */
    public function fetchByQuery(Representations\Query $query)
    {
        $this->setCommandType($query->getType());
        $statement = $this->execute($query->getSql());
        $result = $statement->fetchAll();

        if (! $result) {
            throw new Exceptions\RecordNotFoundException(
                $query->getSql(),
                $query->getQueryParams()->getTable()
            );
        }

        $this->get('dbManager')->closeStatement($statement);

        return $result;
    }

    /**
     * @param array $values The values to resolve.
     *
     * @return array
     */
    protected function resolveQuery(array $values)
    {
        foreach ($values as $index => $value) {
            $values[$index] = $this->get('keyStore')->parseKeywordsInString($value);
        }

        return $this->convertToResolvedArray($values);
    }

    /**
     * @param string $commandType The command type.
     * @param array $query The query to resolve to sql clause.
     *
     * @return string
     */
    protected function resolveQueryToSQLClause($commandType, array $query)
    {
        $resolveQuery = $this->resolveQuery($query);
        $sqlClause = $this->constructSQLClause($commandType, ' AND ', $resolveQuery);

        return $sqlClause;
    }
}
