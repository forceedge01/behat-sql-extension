<?php

namespace Genesis\SQLExtension\Context;

use Behat\MinkExtension\Context\MinkContext,
    Behat\Behat\Context\Step\Given;


class SQLContext extends MinkContext
{
    CONST IGNORE_DUPLICATE = true;

    private $columns = [];
    private $lastQuery;
    private $connection;
    private $params;

    public function getConnection()
    {
        if(! $this->connection) {
            list($dns, $username, $password) = $this->setDBParams()->connectionString();
            $this->connection = new \PDO($dns, $username, $password);
        }

        return $this->connection;
    }

    private function setDBParams()
    {
        $params = getenv('BEHAT_ENV_PARAMS');

        if(! $params) {
            throw new \Exception('Could not find "BEHAT_ENV_PARAMS" environment variable.');
        }

        $params = explode(';', $params);

        foreach($params as $param) {
            list($key, $val) = explode(':', $param);
            $this->params[$key] = $val;
        }

        return $this;
    }

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
     * @Given /^I have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^I have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iHaveAWhere($entity, $columns)
    {
        $this->handleParam($columns);

        // Auto generate values for insert
        $allColumns = $this->tableColumns($entity);
        $columnClause = [];

        foreach($allColumns as $col => $type) {
            $columnClause[$col] = isset($this->columns[$col]) ? $this->columns[$col] : $this->sampleData($type);
        }

        $columnNames = implode(', ', array_keys($columnClause));
        $columnValues = implode(', ', $columnClause);

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $entity, $columnNames, $columnValues);
        $this->execute($sql, self::IGNORE_DUPLICATE);

        return $this;
    }

    /**
     * @Given /^I dont have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^I dont have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iDontHaveAWhere($entity, $columns)
    {
        if(! $columns) {
            throw new \Exception('You must provide a where clause!');
        }

        $this->handleParam($columns);
        $whereClause = $this->constructClause(' AND ', $this->columns);

        $sql = sprintf('DELETE FROM %s WHERE %s', $entity, $whereClause);
        $this->execute($sql);

        return $this;
    }

    /**
     * @Given /^I have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns)
    {
        if(! $columns) {
            throw new \Exception('You must provide a where clause!');
        }

        $this->handleParam($with);
        $updateClause = $this->constructClause(', ', $this->columns);
        $this->handleParam($columns);
        $whereClause = $this->constructClause(' AND ', $this->columns);

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $entity, $updateClause, $whereClause);
        $this->execute($sql, self::IGNORE_DUPLICATE);
    }

    private function tableColumns($table)
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

    private function sampleData($type)
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

    private function constructClause($glue, $columns)
    {
        $whereClause = [];

        foreach($columns as $column => $value) {
            $whereClause[] = sprintf('%s = %s', $column, $value);
        }

        return implode($glue, $whereClause);
    }

    private function handleParam($columns)
    {
        $this->columns = [];
        $columns = explode(',', $columns);

        foreach($columns as $column) {
            try {
                list($col, $val) = explode(':', $column);    
            } catch(\Exception $e) {
                throw new \Exception('Unable to explode columns based on ":" separator');
            }

            $val = trim($val);
            $this->columns[trim($col)] = $this->quoteOrNot($val);
        }
    }

    private function execute($sql, $ignoreDuplicate = false)
    {
        $this->lastQuery = $sql;

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        if(! $stmt->rowCount()) {
            $error = print_r($this->connection->errorInfo(), true);

            if($ignoreDuplicate AND strpos($error, 'duplicate key value') !== false) {
                return $stmt;
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

    private function quoteOrNot($val) 
    {
        return ((is_string($val) || is_numeric($val)) AND !$this->isNotQuoteable($val)) ? sprintf("'%s'", $val) : $val;
    }

    private function isNotQuoteable($val)
    {
        $keywords = [
            'true',
            'false',
            'null',
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
