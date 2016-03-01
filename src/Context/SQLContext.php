<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\Step\Given;
use Behat\Gherkin\Node\TableNode;

/*
 * This file is part of the Behat\SQLExtension
 *
 * (c) Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SQL Context.
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
class SQLContext extends SQLHandler implements Interfaces\SQLContextInterface
{
    /**
     * @Given /^(?:|I )have( an| a)? "([^"]*)" where:$/
     */
    public function iHaveWhere($entity, TableNode $nodes)
    {
        $queries = $this->convertTableNodeToQueries($nodes);
        $sqls = [];

        foreach ($queries as $query) {
            $sqls[] = $this->iHaveAWhere($entity, $query);
        }

        return $sqls;
    }

    /**
     * @Given /^(?:|I )have:$/
     */
    public function iHave(TableNode $nodes)
    {
        $nodes = $nodes->getRows();
        unset($nodes[0]);
        $sqls = [];

        // Loop through all nodes and try inserting values.
        foreach ($nodes as $node) {
            $sqls[] = $this->iHaveAWhere($node[0], $node[1]);
        }

        return $sqls;
    }

    /**
     * @Given /^(?:|I )have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iHaveAWhere($entity, $columns)
    {
        // Normalize data.
        $entity = $this->makeSQLSafe($entity);
        $this->setEntity($entity);
        $this->filterAndConvertToArray($columns);

        // Check if the record exists.
        $whereClause = $this->constructClause(' AND ', $this->getColumns());
        $sql = sprintf('SELECT * FROM %s WHERE %s', $entity, $whereClause);
        $statement = $this->execute($sql);

        // If it does, set the last id and return.
        if ($this->hasFetchedRows($statement)) {
            // Set the last id to use from this.
            $statement = $this->setLastIdWhere(
                $entity,
                $whereClause
            );

            return $sql;
        }

        // If the record does not already exist, create it.
        list($columnNames, $columnValues) = $this->getTableColumns($entity);
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $entity, $columnNames, $columnValues);
        $statement = $this->execute($sql);
        $this->throwErrorIfNoRowsAffected($statement, self::IGNORE_DUPLICATE);
        $result = $statement->fetchAll();

        // Extract duplicate key and run update using it
        if ($key = $this->getKeyFromDuplicateError($result)) {
            $this->debugLog(sprintf('Duplicate key found, running update using key "%s"', $key));

            $this->iHaveAnExistingWithWhere(
                $entity,
                $columns,
                sprintf('%s:%s', $key, $this->getColumns()[$key])
            );

            $this->setLastIdWhere(
                $entity,
                sprintf('%s = %s', $key, $this->quoteOrNot($this->getColumns()[$key]))
            );
        }

        return $sql;
    }

    /**
     * @Given /^(?:|I )don't have:$/
     * @Given /^(?:|I )do not have:$/
     */
    public function iDontHave(TableNode $nodes)
    {
        $nodes = $nodes->getRows();
        unset($nodes[0]);
        $sqls = [];

        // Loop through all nodes and try inserting values.
        foreach ($nodes as $node) {
            $sqls[] = $this->iDontHaveAWhere($node[0], $node[1]);
        }

        return $sqls;
    }

    /**
     * @Given /^(?:|I )do not have( an| a)? "([^"]*)" where:$/
     */
    public function iDontHaveWhere($entity, TableNode $nodes)
    {
        $queries = $this->convertTableNodeToQueries($nodes);
        $sqls = [];

        foreach ($queries as $query) {
            $sqls[] = $this->iDontHaveAWhere($entity, $query);
        }

        return $sqls;
    }

    /**
     * @Given /^(?:|I )don't have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )don't have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iDontHaveAWhere($entity, $columns)
    {
        if (! $columns) {
            throw new \Exception('You must provide a where clause!');
        }

        $entity = $this->makeSQLSafe($entity);
        $this->setEntity($entity);
        $this->filterAndConvertToArray($columns);
        $whereClause = $this->constructClause(' AND ', $this->getColumns());

        $sql = sprintf('DELETE FROM %s WHERE %s', $entity, $whereClause);
        $statement = $this->execute($sql);
        $this->throwExceptionIfErrors($statement);

        return $sql;
    }

    /**
     * @Given /^(?:|I )have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns)
    {
        if (! $columns) {
            throw new \Exception('You must provide a where clause!');
        }

        $entity = $this->makeSQLSafe($entity);
        $this->setEntity($entity);
        $this->filterAndConvertToArray($with);
        $updateClause = $this->constructClause(', ', $this->getColumns());
        $this->filterAndConvertToArray($columns);
        $whereClause = $this->constructClause(' AND ', $this->getColumns());

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $entity, $updateClause, $whereClause);
        $statement = $this->execute($sql);
        $this->throwErrorIfNoRowsAffected($statement, self::IGNORE_DUPLICATE);

        $this->setLastIdWhere(
            $entity,
            $whereClause
        );

        return $sql;
    }

    /**
     * @Given /^(?:|I )have an existing "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWhere($entity, $where)
    {
        $entity = $this->makeSQLSafe($entity);
        $this->setEntity($entity);
        // Create array out of the with string given.
        $this->filterAndConvertToArray($where);
        // Create a usable sql clause.
        $selectWhereClause = $this->constructClause(' AND ', $this->getColumns());

        return $this->setLastIdWhere(
            $entity,
            $selectWhereClause
        );
    }

    /**
     * @Then /^(?:|I )should have a "([^"]*)" with "([^"]*)"$/
     */
    public function iShouldHaveAWith($entity, $with)
    {
        $entity = $this->makeSQLSafe($entity);
        $this->setEntity($entity);
        // Create array out of the with string given.
        $this->filterAndConvertToArray($with);
        // Create a usable sql clause.
        $selectWhereClause = $this->constructClause(' AND ', $this->getColumns());

        // Create the sql to be inserted.
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s',
            $entity,
            $selectWhereClause
        );

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        try {
            $statement = $this->execute($sql);
            $this->throwErrorIfNoRowsAffected($statement);
        } catch (\Exception $e) {
            if (! $this->hasFetchedRows($statement)) {
                throw new \Exception(sprintf(
                    'Record not found with "%s" in "%s"',
                    $selectWhereClause,
                    $entity
                ));
            }
        }

        return $sql;
    }

    /**
     * @Then /^(?:|I )should not have a "([^"]*)" with "([^"]*)"$/
     */
    public function iShouldNotHaveAWith($entity, $with)
    {
        $entity = $this->makeSQLSafe($entity);
        $this->setEntity($entity);
        // Create array out of the with string given.
        $this->filterAndConvertToArray($with);
        // Create a usable sql clause.
        $selectWhereClause = $this->constructClause(' AND ', $this->getColumns());

        // Create the sql to be inserted.
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s',
            $entity,
            $selectWhereClause
        );

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        try {
            $statement = $this->execute($sql);
            $this->throwErrorIfNoRowsAffected($statement);
        } catch (\Exception $e) {
            if ($this->hasFetchedRows($statement)) {
                throw new \Exception(sprintf(
                    'Record not found with "%s" in "%s"',
                    $selectWhereClause,
                    $entity
                ));
            }
        }

        return $sql;
    }

    /**
     * @Given /^(?:|I )save the id as "([^"]*)"$/
     */
    public function iSaveTheIdAs($key)
    {
        $this->setKeyword($key, $this->getLastInsertId());

        return $this;
    }

    /**
     * @Given /^(?:|I )am in debug mode$/
     */
    public function iAmInDebugMode()
    {
        define('DEBUG_MODE', 1);

        $this->debugLog('IN DEBUG MODE NOW');
    }
}
