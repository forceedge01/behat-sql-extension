<?php

namespace Genesis\SQLExtension\Context;

use Exception;
use Genesis\SQLExtension\Context\Interfaces\DatabaseProviderFactoryInterface;
use Genesis\SQLExtension\Context\Interfaces\DatabaseProviderInterface;
use PDO;
use Traversable;

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
     * @var DatabaseProviderInterface
     */
    private $databaseProvider;

    /**
     * @var array Caches primary key of tables.
     */
    private static $primaryKeys;

    /**
     * @var array Caches any columns we've retrieved for given table.
     */
    private static $requiredColumns;

    /**
     * @param DatabaseProviderFactoryInterface $dbProviderFactory
     * @param array                            $params
     */
    public function __construct(
        DatabaseProviderFactoryInterface $dbProviderFactory,
        array $params = array()
    ) {
        $this->setDBParams($params);
        $this->databaseProvider = $dbProviderFactory->getProvider(
            $dbProviderFactory->getClass($this->params['DBENGINE']),
            $this
        );
    }

    /**
     * @return DatabaseProviderInterface
     */
    public function getDatabaseProvider()
    {
        return $this->databaseProvider;
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
     * @param mixed $connection
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
            list($dns, $username, $password, $options) = $this->getConnectionDetails();

            $this->connection = new \PDO(
                $dns,
                $username,
                $password,
                $options
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        }

        return $this->connection;
    }

    /**
     * @param string $database
     * @param string $schema
     * @param string $table
     *
     * @return string|bool
     */
    public function getPrimaryKeyForTable($database, $schema, $table)
    {
        $keyReference = $database . $schema . $table;
        $databaseToUse = $this->getParams()['DBPREFIX'] . ($database ? $database : $this->getParams()['DBNAME']);

        if (! isset(self::$primaryKeys[$keyReference])) {
            self::$primaryKeys[$keyReference] = $this->databaseProvider->getPrimaryKeyForTable(
                $databaseToUse,
                $schema,
                $table
            );
        }

        return self::$primaryKeys[$keyReference];
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
     * @return array|null.
     */
    public function getFirstValueFromStatement(Traversable $statement)
    {
        $result = $statement->fetch(PDO::FETCH_BOTH, PDO::FETCH_ORI_FIRST);

        if (! $result) {
            return null;
        }

        return $result;
    }

    /**
     * Use only with select statements.
     *
     * @param Traversable $statement
     *
     * @return bool
     */
    public function hasFetchedRows(Traversable $statement)
    {
        // PDOStatement::rowCount() not reliable, use alternative means.
        return $statement->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_FIRST) ? true : false;
    }

    /**
     * Gets a column list for a table with their type.
     *
     * @param string $database
     * @param string $schema
     * @param string $table
     *
     * @return array
     */
    public function getRequiredTableColumns($database, $schema, $table)
    {
        $requiredColumnReference = $database . $schema . $table;
        $databaseToUse = $this->getParams()['DBPREFIX'] . ($database ? $database : $this->getParams()['DBNAME']);

        if (! isset(self::$requiredColumns[$requiredColumnReference])) {
            self::$requiredColumns[$requiredColumnReference] = $this->databaseProvider->getRequiredTableColumns(
                $databaseToUse,
                $schema,
                $table
            );
        }

        return self::$requiredColumns[$requiredColumnReference];
    }

    /**
     * @param string $database
     * @param string $schema
     * @param string $table
     *
     * @return array
     */
    public function getTableColumns($database, $schema, $table)
    {
        $requiredColumnReference = $database . $schema . $table;
        $databaseToUse = $this->getParams()['DBPREFIX'] . ($database ? $database : $this->getParams()['DBNAME']);

        if (! isset(self::$requiredColumns[$requiredColumnReference])) {
            self::$requiredColumns[$requiredColumnReference] = $this->databaseProvider->getTableColumns(
                $databaseToUse,
                $schema,
                $table
            );
        }

        return self::$requiredColumns[$requiredColumnReference];
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
            $this->databaseProvider->getPdoDnsString(
                $this->params['DBNAME'],
                $this->params['DBHOST'],
                $this->params['DBPORT']
            ),
            $this->params['DBUSER'],
            $this->params['DBPASSWORD'],
            $this->params['DBOPTIONS']
        ];
    }

    /**
     * @param array  $data The array to look into.
     * @param string $if   The index to check in the array.
     * @param mixed  $else If the index is not found, use this value.
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
        if (isset($dbParams['engine'])) {
            $options = [];
            if (isset($_SESSION['behat']['GenesisSqlExtension']['connection_details']['connection_options'])) {
                $options = $_SESSION['behat']['GenesisSqlExtension']['connection_details']['connection_options'];
            }

            // Allow params to be over-ridable.
            $this->params['DBSCHEMA'] = $this->arrayIfElse($dbParams, 'schema', null);
            $this->params['DBNAME'] = $this->arrayIfElse($dbParams, 'dbname', $this->arrayIfElse($dbParams, 'name', null));
            $this->params['DBPREFIX'] = $this->arrayIfElse($dbParams, 'prefix', null);
            $this->params['DBHOST'] = $this->arrayIfElse($dbParams, 'host', null);
            $this->params['DBPORT'] = $this->arrayIfElse($dbParams, 'port', null);
            $this->params['DBUSER'] = $this->arrayIfElse($dbParams, 'username', null);
            $this->params['DBPASSWORD'] = $this->arrayIfElse($dbParams, 'password', null);
            $this->params['DBENGINE'] = $this->arrayIfElse($dbParams, 'engine', null);
            $this->params['DBOPTIONS'] = $options;
        } elseif (defined('SQLDBENGINE')) {
            $options = [];
            if (isset($_SESSION['behat']['GenesisSqlExtension']['connection_details']['connection_options'])) {
                $options = $_SESSION['behat']['GenesisSqlExtension']['connection_details']['connection_options'];
            }

            // Allow params to be over-ridable.
            $this->params['DBSCHEMA'] = SQLDBSCHEMA;
            $this->params['DBNAME'] = SQLDBNAME;
            $this->params['DBPREFIX'] = SQLDBPREFIX;
            $this->params['DBHOST'] = SQLDBHOST;
            $this->params['DBPORT'] = SQLDBPORT;
            $this->params['DBUSER'] = SQLDBUSERNAME;
            $this->params['DBPASSWORD'] = SQLDBPASSWORD;
            $this->params['DBENGINE'] = SQLDBENGINE;
            $this->params['DBOPTIONS'] = $options;
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
     * Check for any db errors. Use only with select statements.
     *
     * @param Traversable $sqlStatement
     * @param bool        $ignoreDuplicate
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
        Debugger::log('Statement error info: ' . print_r($sqlStatement->errorInfo(), true));

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

    /**
     * @return string
     */
    public function getLeftDelimiterForReservedWord()
    {
        return $this->databaseProvider->getLeftDelimiterForReservedWord();
    }

    /**
     * @return string
     */
    public function getRightDelimiterForReservedWord()
    {
        return $this->databaseProvider->getRightDelimiterForReservedWord();
    }
}
