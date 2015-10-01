<?php

use Behat\Behat\Context\BehatContext,
    Behat\Behat\Context\Step\Given;


class SQLExtension extends BehatContext
{
    private $columns = [];
    private $lastQuery;
    private $connection;
    private $params;

    public function __construct(array $parameters)
    {
        $this->params = $parameters;
        list($dns, $username, $password) = $this->connectionString($parameters['connection_details']);
        $this->connection = new \PDO($dns, $username, $password);
    }

    private function connectionString(array $params)
    {
        return [
            sprintf('%s:dbname=%s;host=%s', 
                $params['engine'], 
                $params['dbname'], 
                $params['host']
            ), 
            $params['username'], 
            $params['password']
        ];
    }

    /**
     * @Given /^I have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^I have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iHaveAWhere($entity, $columns)
    {
        $this->handleParam($columns);

        $columnNames = implode(', ', array_keys($this->columns));
        $columnValues = implode(', ', $this->columns);

        $sql = sprintf('INSERT INTO %s.%s (%s) VALUES (%s)', $this->params['connection_details']['dbname'], $entity, $columnNames, $columnValues);
        $this->execute($sql);

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
        $whereClause = $this->constructWhere($this->columns);

        $sql = sprintf('DELETE FROM %s.%s WHERE %s', $this->params['connection_details']['dbname'], $entity, $whereClause);
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

        $sql = sprintf('UPDATE %s.%s SET %s WHERE %s', $this->params['connection_details']['dbname'], $entity, $updateClause, $whereClause);
        $this->execute($sql);
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

    private function execute($sql)
    {
        $this->lastQuery = $sql;

        if(! $this->connection->exec($sql)) {
            throw new \Exception(
                sprintf('Query did not affect any rows!%sSQL: "%s",%sError: %s', 
                    PHP_EOL, 
                    $sql, 
                    PHP_EOL, 
                    print_r($this->connection->errorInfo(), true)
                )
            );
        }
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
