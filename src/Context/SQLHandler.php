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
     * Columns exploded.
     */
    private $columns = [];

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
     * @param Interfaces\KeyStoreInterface $keyStore
     */
    public function __construct(
        Interfaces\DBManagerInterface $dbManager,
        Interfaces\KeyStoreInterface $keyStore
    ) {
        $this->dbManager = $dbManager;
        $this->keyStore = $keyStore;
    }

    /**
     * returns sample data for a data type.
     */
    public function sampleData($type)
    {
        switch (strtolower($type)) {
            case 'boolean':
                return 'false';
            case 'integer':
            case 'double':
            case 'int':
                return rand();
            case 'tinyint':
                return rand(0, 9);
            case 'string':
            case 'text':
            case 'varchar':
            case 'character varying':
            case 'tinytext':
            case 'longtext':
                return $this->quoteOrNot(sprintf("behat-test-string-%s", time()));
            case 'char':
                return "'f'";
            case 'timestamp':
            case 'timestamp with time zone':
                return 'NOW()';
            case 'null':
                return null;
            default:
                return $this->quoteOrNot(sprintf("behat-test-string-%s", time()));
        }
    }

    /**
     * Get the clause type.
     */
    public function getCommandType()
    {
        return $this->commandType;
    }

    /**
     * Set the clause type.
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
     * Constructs a clause based on the glue, to be used for where and update clause.
     * 
     * @param string $glue
     * @param array $columns
     */
    public function constructSQLClause($glue, array $columns)
    {
        $whereClause = [];

        foreach ($columns as $column => $value) {
            $newValue = ltrim($value, '!');
            $quotedValue = $this->quoteOrNot($newValue);
            $comparator = '%s=';
            $notOperator = '';

            // Check if the supplied value is null and that the construct is not for insert and update,
            // if so change the format.
            if (strtolower($newValue) == 'null' and
                trim($glue) != ',' and
                in_array($this->getCommandType(), ['update', 'select', 'delete'])) {
                $comparator = 'is%s';
            }

            // Check if a not is applied to the value.
            if (strpos($value, '!') === 0) {
                if (strtolower($newValue) == 'null' and
                trim($glue) != ',' and
                in_array($this->getCommandType(), ['update', 'select', 'delete'])) {
                    $notOperator = ' not';
                } else {
                    $notOperator = '!';
                }
            }

            // Check if the value is surrounded by wildcards. If so, we'll want to use a LIKE comparator.
            if (preg_match('/^%.+%$/', $value)) {
                $comparator = 'LIKE';
            }

            // Make up the sql.
            $comparator = sprintf($comparator, $notOperator);
            $clause = sprintf('%s %s %s', $column, $comparator, $quotedValue);
            $whereClause[] = $clause;
        }

        return implode($glue, $whereClause);
    }

    /**
     * Gets table columns and its values.
     * 
     * @return array
     */
    protected function getTableColumns($entity)
    {
        $columnClause = [];

        // Get all columns for insertion
        $allColumns = array_merge($this->dbManager->getRequiredTableColumns($entity), $this->columns);

        // Set values for columns
        foreach ($allColumns as $col => $type) {
            $columnClause[$col] = isset($this->columns[$col]) ?
                $this->quoteOrNot($this->columns[$col]) :
                $this->sampleData($type);
        }

        $columnNames = implode(', ', array_keys($columnClause));
        $columnValues = implode(', ', $columnClause);

        return [$columnNames, $columnValues];
    }

    /**
     * Converts the incoming string param from steps to array.
     * 
     * @param string $columns
     * 
     * @return array
     */
    public function filterAndConvertToArray($columns)
    {
        $this->columns = [];
        $columns = explode(',', $columns);

        foreach ($columns as $column) {
            try {
                list($col, $val) = explode(':', $column, self::EXPLODE_MAX_LIMIT);
            } catch (Exception $e) {
                throw new Exception('Unable to explode columns based on ":" separator');
            }

            $val = $this->checkForKeyword(trim($val));
            $this->columns[trim($col)] = $val;
        }

        return $this;
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
        if (defined('DEBUG_MODE') and DEBUG_MODE == 1) {
            $log = 'DEBUG >>> ' . $log;
            echo $log . PHP_EOL . PHP_EOL;
        }

        return $this;
    }

    /**
     * Executes sql command.
     * 
     * @param string $sql
     */
    protected function execute($sql)
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
        if (! $this->hasFetchedRows($sqlStatement)) {
            $error = print_r($sqlStatement->errorInfo(), true);

            if ($ignoreDuplicate and preg_match('/duplicate/i', $error)) {
                return $sqlStatement->errorInfo();
            }

            throw new Exception(
                sprintf(
                    'No rows were effected!%sSQL: "%s",%sError: %s',
                    PHP_EOL,
                    $sqlStatement->queryString,
                    PHP_EOL,
                    $error
                )
            );
        }

        return false;
    }

    /**
     * Errors found then throw exception.
     */
    public function throwExceptionIfErrors(Traversable $sqlStatement)
    {
        if ((int) $sqlStatement->errorCode()) {
            throw new Exception(
                print_r($sqlStatement->errorInfo(), true)
            );
        }

        return false;
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
     */
    public function quoteOrNot($val)
    {
        return ((is_string($val) || is_numeric($val)) and !$this->isNotQuotable($val)) ?
            sprintf(
                "'%s'",
                str_replace(
                    ['\\', "'"],
                    ['', "\\'"],
                    $val
                )
            ) :
            $val;
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

        $this->setKeywordsFromRecord(
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
            throw new Exception('Unable to fetch result');
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
            $this->setKeyword(sprintf('%s_%s', $entity, $column), $value);
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
        $this->setKeyword($entity . '_' . $this->primaryKey, $this->lastId);
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
     * Checks if the value isn't a keyword.
     */
    private function isNotQuotable($val)
    {
        $keywords = [
            'true',
            'false',
            'null',
            'NOW\(\)',
            'COUNT\(.*\)',
            'MAX\(.*\)',
            '\d+'
        ];

        $keywords = array_merge($keywords, $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords']);

        // Check if the val is a keyword
        foreach ($keywords as $keyword) {
            if (preg_match(sprintf('/^%s$/is', $keyword), $val)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToQueries(TableNode $node)
    {
        // Get the title row.
        $columns = $node->getRow(0);

        // Get all rows and extract the heading.
        $rows = $node->getRows();
        unset($rows[0]);

        if (! $rows) {
            throw new Exception('No data provided to loop through.');
        }

        $queries = [];

        // Loop through the rest of the rows and form up the queries.
        foreach ($rows as $row) {
            $query = '';
            foreach ($row as $index => $value) {
                $query .= sprintf('%s:%s,', $columns[$index], $value);
            }
            $queries[] = trim($query, ',');
        }

        return $queries;
    }

    /**
     * @param  TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToSingleContextClause(TableNode $node)
    {
        // Get all rows and extract the heading.
        $rows = $node->getRows();
        // Get rid of the top row as its just represents the title.
        unset($rows[0]);

        if (! $rows) {
            throw new Exception('No data provided to loop through.');
        }

        $clauseArray = [];
        // Loop through the rest of the rows and form up the queries.
        foreach ($rows as $row) {
            $clauseArray[] = implode(':', $row);
        }

        return implode(',', $clauseArray);
    }

    /**
     * Checks if the command executed affected any rows.
     */
    public function hasFetchedRows(Traversable $sqlStatement)
    {
        return $this->dbManager->hasFetchedRows($sqlStatement);
    }

    /**
     * Get the value of columns var.
     */
    public function getColumns()
    {
        return $this->columns;
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
        $this->primaryKey = $this->dbManager->getPrimaryKeyForTable($this->databaseName, $this->tableName);
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
     * @return array
     */
    private function getParams()
    {
        return $this->dbManager->getParams();
    }
}
