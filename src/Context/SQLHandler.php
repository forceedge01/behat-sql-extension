<?php

namespace Genesis\SQLExtension\Context;

use Behat\MinkExtension\Context\MinkContext;

/*
 * This file is part of the Behat\SQLExtension
 *
 * (c) Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SQL Handler 
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
class SQLHandler extends MinkContext
{
    CONST IGNORE_DUPLICATE = true;

    private $lastQuery;
    private $connection;
    private $params;

    protected $columns = [];

    /**
     * Gets the connection for query execution
     */
    private function getConnection()
    {
        if(! $this->connection) {
            list($dns, $username, $password) = $this->setDBParams()->connectionString();
            $this->connection = new \PDO($dns, $username, $password);
        }

        return $this->connection;
    }

    /**
     * Sets the database param from either the environment variable or params
     * passed in by behat.yml, params have precedence over env variable.
     */
    private function setDBParams()
    {
        if(defined('SQL.DBENGINE')) {
            $this->params = [
                'DBENGINE' => SQLDBENGINE,
                'DBHOST' => SQLDBHOST,
                'DBSCHEMA' => SQLDBSCHEMA,
                'DBNAME' => SQLDBNAME,
                'DBUSER' => SQLDBUSERNAME,
                'DBPASSWORD' => SQLDBPASSWORD
            ];
        } else {
            $params = getenv('BEHAT_ENV_PARAMS');

            if(! $params) {
                throw new \Exception('Could not find "BEHAT_ENV_PARAMS" environment variable.');
            }

            $params = explode(';', $params);

            foreach($params as $param) {
                list($key, $val) = explode(':', $param);
                $this->params[$key] = $val;
            }
        }

        return $this;
    }

    /**
     * Creates the connection string for the pdo object
     */
    private function connectionString()
    {
        return [
            sprintf('%s:dbname=%s;host=%s', 
                $this->params['DBENGINE'], 
                $this->params['DBNAME'], 
                $this->params['DBHOST']
            ), 
            $this->params['DBUSER'], 
            $this->params['DBPASSWORD']
        ];
    }

    /**
     * Gets a column list for a table with their type
     */
    protected function tableColumns($table)
    {
        $this->setDBParams();

        if(isset($this->params['DBSCHEMA'])) {
            $table = str_replace($this->params['DBSCHEMA'].'.' , '', $table);
        }

        $sql = sprintf("SELECT column_name, data_type FROM information_schema.columns WHERE is_nullable = 'NO' and table_name = '%s' and table_schema = '%s';", $table, $this->params['DBSCHEMA']);
        $result = $this->execute($sql);

        $cols = [];
        foreach($result as $column) {
            $cols[$column['column_name']] = $column['data_type'];
        }

        return $cols;
    }

    /**
     * returns sample data for a data type
     */
    protected function sampleData($type)
    {
        switch ($type) {
            case 'boolean':
                return 'false';
            case 'integer':
            case 'double':
                return rand();
            case 'string':
            case 'text':
            case 'varchar':
            case 'character varying':
                return sprintf("'behat-test-string-random-%s'", time());
            case 'char':
                return 'f';
            case 'timestamp':
            case 'timestamp with time zone':
                return 'NOW()';
            case 'null':
                return null;
        }
    }

    /**
     * Constructs a clause based on the glue, to be used for where and update clause
     */
    protected function constructClause($glue, $columns)
    {
        $whereClause = [];

        foreach($columns as $column => $value) {
            $whereClause[] = sprintf('%s = %s', $column, $this->quoteOrNot($value));
        }

        return implode($glue, $whereClause);
    }

    /**
     * Gets table columns and its values, returns array
     */
    protected function getTableColumns($entity)
    {
        $columnClause = [];
        // Auto generate values for insert
        $allColumns = $this->tableColumns($entity);

        foreach($allColumns as $col => $type) {
            $columnClause[$col] = isset($this->columns[$col]) ? $this->quoteOrNot($this->columns[$col]) : $this->sampleData($type);
        }

        $columnNames = implode(', ', array_keys($columnClause));
        $columnValues = implode(', ', $columnClause);

        return [$columnNames, $columnValues];
    }

    /**
     * Converts the incoming string param from steps to array
     */
    protected function handleParam($columns)
    {
        $this->columns = [];
        $columns = explode(',', $columns);

        foreach($columns as $column) {
            try {
                list($col, $val) = explode(':', $column);    
            } catch(\Exception $e) {
                throw new \Exception('Unable to explode columns based on ":" separator');
            }

            $val = $this->checkForKeyword(trim($val));
            $this->columns[trim($col)] = $val;
        }
    }

    /**
     * Checks the value for possible keywords set in behat.yml file
     */
    private function checkForKeyword($value)
    {
        if(! isset($_SESSION['behat']['keywords'])) {
            return $this;
        }

        foreach($_SESSION['behat']['keywords'] as $keyword => $val) {
            $key = sprintf('{%s}', $keyword);

            if($value == $key) {
                $value = str_replace($key, $val, $value);
            }
        }

        return $value;
    }

    /**
     * Executes sql command
     */
    protected function execute($sql, $ignoreDuplicate = false)
    {
        $this->lastQuery = $sql;

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        if(! $stmt->rowCount()) {
            $error = print_r($this->connection->errorInfo(), true);

            if($ignoreDuplicate AND strpos($error, 'duplicate key value') !== false) {
                return $this->connection->errorInfo();
            }

            throw new \Exception(
                sprintf('No rows were effected!%sSQL: "%s",%sError: %s', 
                    PHP_EOL, 
                    $sql, 
                    PHP_EOL, 
                    $error
                )
            );
        }

        return $stmt->fetchAll();
    }

    /**
     * Quotes value if needed for sql
     */
    private function quoteOrNot($val) 
    {
        return ((is_string($val) || is_numeric($val)) AND !$this->isNotQuoteable($val)) ? sprintf("'%s'", $val) : $val;
    }

    /**
     * get the duplicate key from the error message
     */
    protected function getKeyFromError($error)
    {
        if(! isset($error[2])) {
            return false;
        }

        // Extract duplicate key and run update using it
        $matches = [];

        if(preg_match('/.*DETAIL:  Key (.*)=.*/sim', $error[2], $matches)) {
            // Index 1 holds the name of the key matched
            $key = trim($matches[1], '()');
            echo sprintf('Duplicate record, running update using "%s"...%s', $key, PHP_EOL);
            
            return $key;
        }

        return false;
    }

    /**
     * Checks if the value isnt a keyword
     */
    private function isNotQuoteable($val)
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

        // Check if the val is a keyword
        foreach($keywords as $keyword) {
            if(preg_match(sprintf('/^%s$/is', $keyword), $val)) {
                return true;
            }
        }

        return false;
    }
}
