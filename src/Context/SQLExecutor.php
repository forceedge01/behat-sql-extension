<?php

namespace Genesis\SQLExtension\Context;

class SQLExecutor
{
    public function __construct(array $params)
    {
        $this->setDBParams($params);
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
}
