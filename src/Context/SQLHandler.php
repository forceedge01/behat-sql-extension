<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\TableNode;
use Exception;

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
class SQLHandler extends BehatContext
{
    const IGNORE_DUPLICATE = true;
    const EXPLODE_MAX_LIMIT = 2;

    protected $entity;

    private $lastQuery;
    private $connection;
    private $params;
    private $lastId;
    private $sqlStatement;
    private $columns = [];
    private $clauseType;

    /**
     * Construct the object.
     */
    public function __construct(array $dbParams = array())
    {
        // Set the database creds.
        $this->setDBParams($dbParams);
    }

    /**
     * Gets the connection for query execution.
     */
    public function getConnection()
    {
        if (! $this->connection) {
            list($dns, $username, $password) = $this->connectionString();

            $this->setConnection(new \PDO($dns, $username, $password));
        }

        return $this->connection;
    }

    /**
     * Set the pdo connection.
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Sets the database param from either the environment variable or params
     * passed in by behat.yml, params have precedence over env variable.
     */
    public function setDBParams(array $dbParams = array())
    {
        if (defined('SQLDBENGINE')) {
            $this->params = [
                'DBSCHEMA' => SQLDBSCHEMA,
                'DBNAME' => SQLDBNAME,
                'DBPREFIX' => SQLDBPREFIX
            ];

            // Allow params to be over-ridable.
            $this->params['DBHOST'] = (isset($dbParams['host']) ? $dbParams['host'] : SQLDBHOST);
            $this->params['DBUSER'] = (isset($dbParams['username']) ? $dbParams['username'] : SQLDBUSERNAME);
            $this->params['DBPASSWORD'] = (isset($dbParams['password']) ? $dbParams['password'] : SQLDBPASSWORD);
            $this->params['DBENGINE'] = (isset($dbParams['engine']) ? $dbParams['engine'] : SQLDBENGINE);
        } else {
            $params = getenv('BEHAT_ENV_PARAMS');

            if (! $params) {
                throw new Exception('"BEHAT_ENV_PARAMS" environment variable was not found.');
            }

            $params = explode(';', $params);

            foreach ($params as $param) {
                list($key, $val) = explode(':', $param);
                $this->params[$key] = trim($val);
            }
        }

        return $this;
    }

    /**
     * Get db params set.
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Creates the connection string for the pdo object.
     */
    private function connectionString()
    {
        return [
            sprintf(
                '%s:dbname=%s;host=%s',
                $this->params['DBENGINE'],
                $this->params['DBNAME'],
                $this->params['DBHOST']
            ),
            $this->params['DBUSER'],
            $this->params['DBPASSWORD']
        ];
    }

    /**
     * Gets a column list for a table with their type.
     */
    protected function requiredTableColumns($table)
    {
        // If the DBSCHEMA is not set, try using the database name if provided with the table.
        if (! $this->params['DBSCHEMA']) {
            preg_match('/(.*)\./', $table, $db);

            if (isset($db[1])) {
                $this->params['DBSCHEMA'] = $db[1];
            }
        }

        // Parse out the table name.
        $table = preg_replace('/(.*\.)/', '', $table);
        $table = trim($table, '`');

        // Statement to extract all required columns for a table.
        $sqlStatement = "
            SELECT 
                column_name, data_type 
            FROM 
                information_schema.columns 
            WHERE 
                is_nullable = 'NO'
            AND 
                table_name = '%s'
            AND 
                table_schema = '%s';";

        // Get not null columns
        $sql = sprintf(
            $sqlStatement,
            $table,
            $this->params['DBSCHEMA']
        );

        $statement = $this->execute($sql);
        $this->throwExceptionIfErrors($statement);
        $result = $statement->fetchAll();

        if (! $result) {
            return [];
        }

        $cols = [];
        foreach ($result as $column) {
            $cols[$column['column_name']] = $column['data_type'];
        }

        // Dont populate primary key, let db handle that
        unset($cols['id']);

        return $cols;
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
    public function getClauseType()
    {
        return $this->clauseType;
    }

    /**
     * Set the clause type.
     */
    public function setClauseType($clauseType)
    {
        // Set the clause type debug message.
        $this->debugLog(sprintf('Clause type set to: %s', $clauseType));

        $allowedClauseTypes = ['select', 'insert', 'update', 'delete'];

        // Check if the clause given is one of the allowed ones.
        if (! in_array($clauseType, $allowedClauseTypes)) {
            throw new Exception(sprintf(
                'Invalid clause type provided "%s", clause type must be one of "%s"',
                $clauseType,
                implode(', ', $allowedClauseTypes)
            ));
        }

        $this->clauseType = $clauseType;

        return $this;
    }

    /**
     * Constructs a clause based on the glue, to be used for where and update clause.
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
                in_array($this->getClauseType(), ['update', 'select', 'delete'])) {
                $comparator = 'is%s';
            }

            // Check if a not is applied to the value.
            if (strpos($value, '!') === 0) {
                if (strtolower($newValue) == 'null' and
                trim($glue) != ',' and
                in_array($this->getClauseType(), ['update', 'select', 'delete'])) {
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
     * Gets table columns and its values, returns array.
     */
    protected function getTableColumns($entity)
    {
        $columnClause = [];

        // Get all columns for insertion
        $allColumns = array_merge($this->requiredTableColumns($entity), $this->columns);

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
     */
    public function setKeyword($key, $value)
    {
        $this->debugLog(sprintf(
            'Saving keyword "%s" with value "%s"',
            $key,
            $value
        ));

        $_SESSION['behat']['GenesisSqlExtension']['keywords'][$key] = $value;

        return $this;
    }

    /**
     * Fetches a specific keyword from the behat keywords store.
     */
    public function getKeyword($key)
    {
        $this->debugLog(sprintf(
            'Retrieving keyword "%s"',
            $key
        ));

        if (! isset($_SESSION['behat']['GenesisSqlExtension']['keywords'][$key])) {
            if (! isset($_SESSION['behat']['GenesisSqlExtension']['keywords'])) {
                throw new Exception('No keywords found.');
            }

            throw new Exception(sprintf(
                'Key "%s" not found in behat store, all keys available: %s',
                $key,
                print_r($_SESSION['behat']['GenesisSqlExtension']['keywords'], true)
            ));
        }

        $value = $_SESSION['behat']['GenesisSqlExtension']['keywords'][$key];

        $this->debugLog(sprintf(
            'Retrieved keyword "%s" with value "%s"',
            $key,
            $value
        ));

        return $value;
    }

    /**
     * Checks the value for possible keywords set in behat.yml file.
     */
    private function checkForKeyword($value)
    {
        if (! isset($_SESSION['behat']['GenesisSqlExtension']['keywords'])) {
            return $value;
        }

        foreach ($_SESSION['behat']['GenesisSqlExtension']['keywords'] as $keyword => $val) {
            $key = sprintf('{%s}', $keyword);

            if ($value == $key) {
                $value = str_replace($key, $val, $value);
            }
        }

        return $value;
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
     */
    protected function execute($sql)
    {
        $this->lastQuery = $sql;

        $this->debugLog(sprintf('Executing SQL: %s', $sql));

        $this->sqlStatement = $this->getConnection()->prepare($sql, []);
        $this->sqlStatement->execute();
        $this->lastId = $this->connection->lastInsertId(sprintf('%s_id_seq', $this->getEntity()));

        // If their is an id, save it!
        if ($this->lastId) {
            $this->handleLastId($this->getEntity(), $this->lastId);
        }

        return $this->sqlStatement;
    }

    /**
     * Save the last insert id in the session for later retrieval.
     */
    protected function saveLastId($entity, $id)
    {
        $this->debugLog(sprintf('Last ID fetched: %d', $id));

        $_SESSION['behat']['GenesisSqlExtension']['last_id'][$entity][] = $id;
    }

    /**
     * Get all id's inserted for an entity.
     */
    protected function getLastIds($entity)
    {
        if (isset($_SESSION['behat']['GenesisSqlExtension']['last_id'][$entity])) {
            return $_SESSION['behat']['GenesisSqlExtension']['last_id'][$entity];
        }

        return false;
    }

    /**
     * Check for any mysql errors.
     */
    public function throwErrorIfNoRowsAffected($sqlStatement, $ignoreDuplicate = false)
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
    public function throwExceptionIfErrors($sqlStatement)
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
    protected function getLastId()
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
    protected function setKeywordsFromCriteria($entity, $criteria)
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
    protected function fetchByCriteria($entity, $criteria)
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s', $entity, $criteria);
        $statement = $this->execute($sql);
        $this->throwErrorIfNoRowsAffected($statement);
        $result = $statement->fetchAll();

        if (! $result[0]) {
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
    protected function setKeywordsFromRecord($entity, array $record)
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
    protected function handleLastId($entity, $id)
    {
        $entity = $this->getUserInputEntity($entity);
        $this->lastId = $id;
        $entity = $this->makeSQLUnsafe($entity);
        $this->saveLastId($entity, $this->lastId);
        $this->setKeyword($entity . '_id', $this->lastId);
    }

    /**
     * Get the entity the way the user had inputted it.
     */
    private function getUserInputEntity($entity)
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
    protected function hasFetchedRows($sqlStatement)
    {
        return ($sqlStatement->rowCount());
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

        return $this;
    }

    /**
     * Get the entity on which actions are being performed.
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
