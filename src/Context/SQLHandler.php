<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Exception;
use Genesis\SQLExtension\Context\Exceptions\ExternalRefResolutionException;
use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;
use Genesis\SQLExtension\Context\Representations\Entity;
use Genesis\SQLExtension\Context\Representations\QueryParams;
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
class SQLHandler implements Context, Interfaces\SQLHandlerInterface
{
    /**
     * @var Entity[]
     */
    private static $entityCollection;

    /**
     * Entity being worked on i.e the table.
     */
    protected $entity;

    /**
     * Last query executed.
     */
    private $lastQuery;


    private $lastQueries = [];

    /**
     * The id of the last sql statement executed.
     */
    private $lastId;

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
     * @param Interfaces\DBManagerInterface       $dbManager
     * @param Interfaces\SQLBuilderInterface      $sqlBuilder
     * @param Interfaces\KeyStoreInterface        $keyStore
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

        $this->sqlBuilder->setDatabaseProvider($this->dbManager->getDatabaseProvider());
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
     * Set a dependency.
     *
     * @param string $dependency.
     *
     * @return object
     */
    public function set($dependency)
    {
        if (! property_exists($this, $dependency)) {
            throw new Exception(sprintf('Dependency "%s" not found', $dependency));
        }

        $this->$dependency = $dependency;

        return $this;
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
     * @param array  $columns
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

        $this->setCommandType($query->getType());
        Debugger::log('Executing External Ref SQL: ' . $query->getSql());

        $statement = $this->get('dbManager')->execute($query->getSql());

        $this->recordHistory(
            $query->getType(),
            $query->getQueryParams()->getEntity()->getEntityName(),
            $query->getSql(),
            null
        );

        $value = $this->get('dbManager')->getFirstValueFromStatement($statement);

        if (! isset($value[0])) {
            throw new ExternalRefResolutionException($externalRef, $query->getSql());
        }

        $placeholderValue = $value[0];
        $this->get('dbManager')->closeStatement($statement);

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
     * @param mixed  $value
     */
    public function setKeyword($key, $value)
    {
        $this->debugLog(sprintf(
            'Saving keyword "%s" with value "%s"',
            $key,
            print_r($value, true)
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
        $this->debugLog('Executing SQL: ' . $sql);
        $this->lastQuery = $sql;
        $this->lastQueries[$this->getCommandType()] = $sql;

        $statement = $this->dbManager->execute($sql);

        if (in_array($this->getCommandType(), ['insert'])) {
            $this->lastId = $this->procureLastId(
                $this->getEntity(),
                $this->queryParams,
                $this->dbManager,
                $this->getCommandType()
            );
        }

        $this->recordHistory(
            $this->getCommandType(),
            $this->getEntity()->getEntityName(),
            $sql,
            $this->lastId
        );

        return $statement;
    }

    /**
     * For postgres we need to specify the sequence to return the last id. Check if the value for
     * the primary key was supplied as input and use that, if not use whatever is fetched. Works
     * better with tables that have no auto generation on the primary key column.
     *
     * @return mixed
     */
    private function procureLastId(Entity $entity, QueryParams $queryParams, DBManagerInterface $dbManager, $commandType)
    {
        $lastId = null;
        $primaryKey = $this->getEntity()->getPrimaryKey();
        if (isset($queryParams->getRawValues()[$primaryKey]) &&
            $commandType === Interfaces\SQLHandlerInterface::COMMAND_TYPE_INSERT
        ) {
            $lastId = $queryParams->getRawValues()[$primaryKey];
            Debugger::log('Last Id provided in values: ' . $lastId);
        } else {
            $lastId = $dbManager->getLastInsertId(
                $entity->getTableName(),
                $entity->getPrimaryKey()
            );
            Debugger::log('Last Id returned by db: ' . $lastId);
        }

        // If their is an id, save it!
        if ($lastId) {
            $this->handleLastId($entity, $lastId);
        }

        return $lastId;
    }

    /**
     * @return string
     */
    public function getLastQuery()
    {
        return $this->lastQuery;
    }

    /**
     * @return array
     */
    public function getLastQueries()
    {
        return $this->lastQueries;
    }

    /**
     * @param string $commandType
     * @param string $entityName
     * @param string $sql
     * @param int    $lastId
     *
     * @return $this
     */
    private function recordHistory($commandType, $entityName, $sql, $lastId)
    {
        if ($this->get('sqlHistory') instanceof Interfaces\SQLHistoryInterface) {
            $this->get('sqlHistory')->addToHistory(
                $commandType,
                $entityName,
                $sql,
                $lastId
            );
        }

        return $this;
    }

    /**
     * Save the last insert id in the session for later retrieval.
     *
     * @param mixed $entity
     * @param mixed $id
     * @param mixed $rawTable
     */
    protected function saveLastId($rawTable, $id)
    {
        $this->debugLog(sprintf('Last ID fetched: %d', $id));

        $_SESSION['behat']['GenesisSqlExtension']['last_id'][$rawTable][$this->getCommandType()][] = $id;
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
     * Gets the last insert id.
     */
    public function getLastId()
    {
        try {
            return $this->getKeyword($this->getEntity()->getRawInput() . '.' . $this->getEntity()->getPrimaryKey());
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
        $this->setCommandType($query->getType());
        $statement = $this->execute($query->getSql());
        $result = $this->get('dbManager')->getFirstValueFromStatement($statement);

        if (! $result) {
            throw new Exceptions\RecordNotFoundException(
                $query->getSql(),
                $query->getQueryParams()->getEntity()->getEntityName()
            );
        }

        $this->get('dbManager')->closeStatement($statement);

        return $this->setKeywordsFromRecord(
            $query->getQueryParams()->getEntity()->getRawInput(),
            $result
        );
    }

    /**
     * Set the record as keywords for re-use.
     *
     * @param string $entity
     * @param array  $record
     * @param mixed  $rawTable
     *
     * @return array
     */
    public function setKeywordsFromRecord($rawTable, array $record)
    {
        // Set all columns as reusable.
        foreach ($record as $column => $value) {
            if (! is_numeric($column)) {
                $this->setKeyword(sprintf('%s.%s', $rawTable, $column), $value);
            }
        }

        return $record;
    }

    /**
     * Do what needs to be done with the last insert id.
     *
     * @param Entity $entity
     * @param mixed  $id
     */
    public function handleLastId(Entity $entity, $id)
    {
        $this->lastId = $id;
        $this->saveLastId($entity->getRawInput(), $this->lastId);
        $this->setKeyword($entity->getRawInput() . '.' . $entity->getPrimaryKey(), $this->lastId);
    }

    /**
     * Gets table columns and its values.
     *
     * @param string $entity            The table to get the columns of.
     * @param array  $overridingColumns The overriding columns.
     *
     * @return array
     */
    public function getTableColumns(Entity $entity, array $overridingColumns = array())
    {
        $columnClause = [];

        if (! $entity->getRequiredColumns()) {
            $entity->setRequiredColumns($this->getRequiredTableColumns($entity));
        }

        // Get all columns for insertion
        $allColumns = array_merge($entity->getRequiredColumns(), $overridingColumns);
        $leftDelimiter = $this->get('dbManager')->getLeftDelimiterForReservedWord();
        $rightDelimiter = $this->get('dbManager')->getRightDelimiterForReservedWord();

        // Set values for columns
        foreach ($allColumns as $col => $type) {
            $delimitedColumn = $leftDelimiter . $col . $rightDelimiter;
            // Check if a column is provided, if not use sample data to fill in.
            if (array_key_exists($col, $overridingColumns)) {
                // If the value is provided get value and check if its a keyword.
                $value = $this->checkForKeyword($overridingColumns[$col]);

                // If the value is not a keyword then use the value provided as is.
                if (! $value) {
                    $value = $overridingColumns[$col];
                }
                // Assign value back to the column.
                $columnClause[$delimitedColumn] = $this->quoteOrNot($value);
            } else {
                $columnClause[$delimitedColumn] = $this->sqlBuilder->sampleData($type);
            }
        }

        $columnNames = implode(', ', array_keys($columnClause));
        $columnValues = implode(', ', $columnClause);

        return [$columnNames, $columnValues];
    }

    /**
     * @param TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToQueries(TableNode $node)
    {
        return $this->sqlBuilder->convertTableNodeToQueries($node);
    }

    /**
     * @param TableNode $node The node with all fields and data.
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
     * Set the entity for further processing.
     *
     * @param Entity $entity
     * @param mixed  $inputEntity
     */
    public function resolveEntity($inputEntity)
    {
        if (! $inputEntity) {
            throw new Exception('Blank entity/table provided!');
        }

        $this->debugLog(sprintf('ENTITY: %s', $inputEntity));

        if (isset(self::$entityCollection[$inputEntity]) && self::$entityCollection[$inputEntity] instanceof Entity) {
            $this->entity = self::$entityCollection[$inputEntity];

            $this->debugLog(sprintf('SET ENTITY: %s', $this->entity->getEntityName()));
            $this->debugLog(sprintf('PRIMARY KEY: %s', $this->entity->getPrimaryKey()));

            return $this->entity;
        }

        // An entity references a table, with or without the database and the schema information.
        // Check accordingly.
        $result = explode('.', $inputEntity);

        switch (count($result)) {
            // Just the table name.
            case 1:
                $dbname = null;
                $schema = null;
                $table = $result[0];
                break;
            // Database and table name;
            case 2:
                $dbname = $this->getParams()['DBPREFIX'] . $result[0];
                $schema = null;
                $table = $result[1];
                break;
            // Scheme provided as well.
            case 3:
                $dbname = $this->getParams()['DBPREFIX'] . $result[0];
                $schema = $result[1];
                $table = $result[2];
                break;
            default:
                throw new Exception('Explode produced too many chunks of the entity to handle.');
        }

        $this->entity = new Entity(
            $inputEntity,
            $table,
            $dbname,
            $schema
        );

        $this->debugLog(sprintf('SET ENTITY: %s', $this->entity->getEntityName()));

        // Set the primary key for the current table.
        $primaryKey = $this->dbManager->getPrimaryKeyForTable(
            $this->entity->getDatabaseName(),
            $this->entity->getSchemaName(),
            $this->entity->getTableName()
        );

        if (!$primaryKey) {
            $primaryKey = null;
        }

        $this->entity->setPrimaryKey($primaryKey);

        // Add it to the collection for caching.
        self::$entityCollection[$inputEntity] = $this->entity;

        $this->debugLog(sprintf('PRIMARY KEY: %s', $this->entity->getPrimaryKey()));

        return $this->entity;
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
     * @param Entity $entity
     *
     * @return array
     */
    public function getRequiredTableColumns(Entity $entity)
    {
        $requiredColumns = $this->dbManager->getRequiredTableColumns(
            $entity->getDatabaseName(),
            $entity->getSchemaName(),
            $entity->getTableName()
        );

        // If we've got a primary key, we'd filter it out to let the db handle auto generation.
        if ($entity->getPrimaryKey()) {
            unset($requiredColumns[$entity->getPrimaryKey()]);
        }

        return $requiredColumns;
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
        $statement = $this->get('dbManager')->execute($query->getSql());
        $result = $statement->fetchAll();

        if (! $result) {
            throw new Exceptions\RecordNotFoundException(
                $query->getSql(),
                $query->getQueryParams()->getEntity()->getEntityName()
            );
        }

        $this->recordHistory(
            $query->getType(),
            $query->getQueryParams()->getEntity()->getEntityName(),
            $query->getSql(),
            null
        );

        $this->get('dbManager')->closeStatement($statement);

        return $result;
    }

    /**
     * Errors found then throw exception.
     */
    public function throwExceptionIfErrors(Traversable $sqlStatement)
    {
        return $this->get('dbManager')->throwExceptionIfErrors($sqlStatement);
    }

    /**
     * @return Representations\Query
     * @param  mixed                 $table
     * @param  mixed                 $values
     */
    public function getSampleInsertQuery($table, $values)
    {
        $entity = $this->resolveEntity($table);
        $resolvedValues = $this->resolveQuery($values);
        $queryParams = new Representations\QueryParams($entity, $values, $resolvedValues);
        list($columnNames, $columnValues) = $this->getTableColumns(
            $queryParams->getEntity(),
            $queryParams->getResolvedValues()
        );
        $insertQueryBuilder = new Builder\InsertQueryBuilder($queryParams, $columnNames, $columnValues);

        return Builder\QueryDirector::build($insertQueryBuilder);
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
     * @param array  $query       The query to resolve to sql clause.
     *
     * @return string
     */
    protected function resolveQueryToSQLClause($commandType, array $query)
    {
        $resolveQuery = $this->resolveQuery($query);
        $sqlClause = $this->constructSQLClause($commandType, ' AND ', $resolveQuery);

        return $sqlClause;
    }

    protected function getSelectCountAlias()
    {
        return 'SELECT_COUNT_' . $this->entity->getTableName();
    }
}
