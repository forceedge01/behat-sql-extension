<?php

namespace Genesis\SQLExtension\Context;

use Traversable;
use Exception;

/**
 * DBManager that handles the database connection.
 */
class DBManager implements Interfaces\DBManagerInterface
{
    /**
     * The database connection.
     */
    private $connection;

    /**
     * DB params passed in.
     */
    private $params;

    /**
     * @param array $params
     */
    public function __construct(array $params = array())
    {
        $this->setDBParams($params);
    }

    /**
     * Close connection on destruct.
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * Get params.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set the connection.
     *
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Gets the connection for query execution.
     *
     * @return PDOConnection
     */
    public function getConnection()
    {
        if (! $this->connection) {
            list($dns, $username, $password) = $this->getConnectionDetails();

            $this->connection = new \PDO($dns, $username, $password);
        }

        return $this->connection;
    }

    /**
     * @param string $database
     * @param string $table
     *
     * @result string|bool
     */
    public function getPrimaryKeyForTable($database, $table)
    {
        $sql = sprintf('
            SELECT `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE (`TABLE_SCHEMA` = "%s")
            AND (`TABLE_NAME` = "%s")
            AND (`COLUMN_KEY` = "PRI")',
            $database,
            $table
        );

        $statement = $this->execute($sql);
        $this->throwExceptionIfErrors($statement);
        $result = $statement->fetchAll();
        $this->closeStatement($statement);

        if (! $result) {
            return false;
        }

        return $result[0][0];
    }

    /**
     * @param string $sql
     *
     * @return Traversable
     */
    public function execute($sql)
    {
        $statement = $this->getConnection()->prepare($sql, []);
        $statement->execute();

        return $statement;
    }

    /**
     * Get the first value from a PDO statement.
     *
     * @param Traversable $statement The statement to work with.
     *
     * @return mixed.
     */
    public function getFirstValueFromStatement(Traversable $statement)
    {
        $result = $statement->fetchAll();

        if (! $result) {
            return null;
        }

        return $result[0];
    }

    /**
     * @param Traversable $statement
     *
     * @return bool
     */
    public function hasFetchedRows(Traversable $statement)
    {
        return ($statement->rowCount());
    }

    /**
     * Gets a column list for a table with their type.
     *
     * @param string $table
     *
     * @return array
     */
    public function getRequiredTableColumns($table)
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
                `column_name`, `data_type` 
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
        $result = $statement->fetchAll();
        $this->closeStatement($statement);

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
     * Get the last insert id.
     *
     * @param string $table For compatibility with postgres.
     *
     * @return int|null
     */
    public function getLastInsertId($table = null)
    {
        return $this->connection->lastInsertId(sprintf('%s_id_seq', $table));
    }

    /**
     * Creates the connection string for the pdo object.
     *
     * @return array
     */
    private function getConnectionDetails()
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
     * @param array $data The array to look into.
     * @param string $if The index to check in the array.
     * @param mixed $else If the index is not found, use this value.
     *
     * @return mixed
     */
    private function arrayIfElse($data, $if, $else)
    {
        if (array_key_exists($if, $data)) {
            return $data[$if];
        }

        return $else;
    }

    /**
     * Sets the database param from either the environment variable or params
     * passed in by behat.yml, params have precedence over env variable.
     *
     * @param array $dbParams
     *
     * @return $this
     */
    private function setDBParams(array $dbParams = array())
    {
        if (defined('SQLDBENGINE') || $dbParams) {
            // Allow params to be over-ridable.
            $this->params['DBSCHEMA'] = $this->arrayIfElse($dbParams, 'schema', SQLDBSCHEMA);
            $this->params['DBNAME'] = $this->arrayIfElse($dbParams, 'name', SQLDBNAME);
            $this->params['DBPREFIX'] = $this->arrayIfElse($dbParams, 'prefix', SQLDBPREFIX);
            $this->params['DBHOST'] = $this->arrayIfElse($dbParams, 'host', SQLDBHOST);
            $this->params['DBUSER'] = $this->arrayIfElse($dbParams, 'username', SQLDBUSERNAME);
            $this->params['DBPASSWORD'] = $this->arrayIfElse($dbParams, 'password', SQLDBPASSWORD);
            $this->params['DBENGINE'] = $this->arrayIfElse($dbParams, 'engine', SQLDBENGINE);
        } else {
            $params = getenv('BEHAT_ENV_PARAMS');

            if (! $params) {
                throw new Exception('Params not passed in and "BEHAT_ENV_PARAMS" environment variable was not found.');
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
     * Check for any mysql errors.
     *
     * @param Traversable $sqlStatement
     * @param bool $ignoreDuplicate
     *
     * @return bool
     */
    public function throwErrorIfNoRowsAffected(Traversable $sqlStatement, $ignoreDuplicate = false)
    {
        if (! $this->hasFetchedRows($sqlStatement)) {
            $error = print_r($sqlStatement->errorInfo(), true);

            if ($ignoreDuplicate and preg_match('/duplicate/i', $error)) {
                return $sqlStatement->errorInfo();
            }

            throw new Exceptions\NoRowsAffectedException(
                $sqlStatement->queryString,
                $error
            );
        }

        return false;
    }

    /**
     * Errors found then throw exception.
     *
     * @param Traverable $sqlStatement
     *
     * @throws Exception
     *
     * @return false if no errors.
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
     * Close the pdo connection.
     *
     * @return void
     */
    public function closeConnection()
    {
        $this->setConnection(null);
    }


    /**
     * Close pdo statement.
     *
     * @param Traversable $statement The statement to close.
     *
     * @return $this self.
     */
    public function closeStatement(Traversable $statement)
    {
        $statement->closeCursor();
        $statement = null;

        return $this;
    }
}
