<?php

namespace Genesis\SQLExtension\Context;

use Behat\MinkExtension\Context\MinkContext;


class SQLHandler extends MinkContext
{
    CONST IGNORE_DUPLICATE = true;

    private $lastQuery;
    private $connection;
    private $params;

    protected $columns = [];

    private function getConnection()
    {
        if(! $this->connection) {
            list($dns, $username, $password) = $this->setDBParams()->connectionString();
            $this->connection = new \PDO($dns, $username, $password);
        }

        return $this->connection;
    }

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

    protected function constructClause($glue, $columns)
    {
        $whereClause = [];

        foreach($columns as $column => $value) {
            $whereClause[] = sprintf('%s = %s', $column, $value);
        }

        return implode($glue, $whereClause);
    }

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

            $val = trim($val);
            $this->columns[trim($col)] = $this->quoteOrNot($val);
        }
    }

    protected function execute($sql, $ignoreDuplicate = false)
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
