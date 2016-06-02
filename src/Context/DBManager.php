<?php

namespace Genesis\SQLExtension\Context;

use Traversable;

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
     * @param PDO $connection
     */
    public function __construct(array $params = array())
    {
        $this->setDBParams($params);
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
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Gets the connection for query execution.
     */
    public function getConnection()
    {
        if (! $this->connection) {
            list($dns, $username, $password) = $this->getConnectionString();

            $this->connection = new \PDO($dns, $username, $password);
        }

        return $this->connection;
    }

    /**
     * @param string $entity
     * 
     * @result string
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

        if (! $result) {
            return 'id';
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
     * @param Traversable $statement
     */
    public function hasFetchedRows(Traversable $statement)
    {
        return ($statement->rowCount());
    }

    /**
     * Gets a column list for a table with their type.
     * 
     * @param string $table
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
     */
    private function getConnectionString()
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
     * Sets the database param from either the environment variable or params
     * passed in by behat.yml, params have precedence over env variable.
     */
    private function setDBParams(array $dbParams = array())
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
}
