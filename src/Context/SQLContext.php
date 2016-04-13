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
     * @Given /^(?:|I )have(?:| an| a) "([^"]*)" where:$/
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
     * @Given /^(?:|I )have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     */
    public function iHaveAWhere($entity, $columns)
    {
        $this->debugLog('------- I HAVE WHERE -------');

        // Normalize data.
        $this->setEntity($entity);

        $this->setClauseType('select');
        $this->filterAndConvertToArray($columns);

        $this->debugLog('Trying to select existing record.');

        // Check if the record exists.
        // This needs to be done in two ways.
        $whereClause = $this->constructSQLClause(' AND ', $this->getColumns());
        $sql = sprintf('SELECT * FROM %s WHERE %s', $this->getEntity(), $whereClause);
        $statement = $this->execute($sql);

        // If it does, set the last id and return.
        if ($this->hasFetchedRows($statement)) {
            // Set the last id to use from fetched row.
            $result = $statement->fetchAll();
            if (isset($result[0]['id'])) {
                $this->handleLastId($this->getEntity(), $result[0]['id']);
            }

            return $sql;
        }

        $this->debugLog('No record found, trying to insert.');

        $this->setClauseType('insert');
        // If the record does not already exist, create it.
        list($columnNames, $columnValues) = $this->getTableColumns($this->getEntity());
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->getEntity(), $columnNames, $columnValues);
        $statement = $this->execute($sql);
        $this->throwErrorIfNoRowsAffected($statement, self::IGNORE_DUPLICATE);
        $result = $statement->fetchAll();

        // Extract duplicate key and run update using it
        if ($key = $this->getKeyFromDuplicateError($result)) {
            $this->debugLog(sprintf('Duplicate key found, running update using key "%s"', $key));

            $this->iHaveAnExistingWithWhere(
                $this->getEntity(),
                $columns,
                sprintf('%s:%s', $key, $this->getColumns()[$key])
            );

            $this->setLastIdWhere(
                $this->getEntity(),
                sprintf('%s = %s', $key, $this->quoteOrNot($this->getColumns()[$key]))
            );
        }

        return $sql;
    }

    /**
     * @Given /^(?:|I )dont have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )dont have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     */
    public function iDontHaveAWhere($entity, $columns)
    {
        $this->debugLog('------- I DONT HAVE WHERE -------');

        if (! $columns) {
            throw new \Exception('You must provide a where clause!');
        }

        $this->setEntity($entity);

        $this->setClauseType('delete');
        $this->filterAndConvertToArray($columns);
        $whereClause = $this->constructSQLClause(' AND ', $this->getColumns());

        $sql = sprintf('DELETE FROM %s WHERE %s', $this->getEntity(), $whereClause);
        $statement = $this->execute($sql);
        $this->throwExceptionIfErrors($statement);

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
     * @Given /^(?:|I )do not have(?:| an| a) "([^"]*)" where:$/
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
     * @Given /^(?:|I )have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns)
    {
        $this->debugLog('------- I HAVE AN EXISTING WITH WHERE -------');

        if (! $columns) {
            throw new \Exception('You must provide a where clause!');
        }

        $this->setEntity($entity);

        $this->setClauseType('update');
        $this->filterAndConvertToArray($with);
        $updateClause = $this->constructSQLClause(', ', $this->getColumns());
        $this->filterAndConvertToArray($columns);
        $whereClause = $this->constructSQLClause(' AND ', $this->getColumns());

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $this->getEntity(), $updateClause, $whereClause);
        $statement = $this->execute($sql);
        $this->throwErrorIfNoRowsAffected($statement, self::IGNORE_DUPLICATE);

        $this->setLastIdWhere(
            $this->getEntity(),
            $whereClause
        );

        return $sql;
    }

    /**
     * @Given /^(?:|I )have(?:| an| a) existing "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWhere($entity, $where)
    {
        $this->debugLog('------- I HAVE AN EXISTING WHERE -------');

        $this->setEntity($entity);

        // Create array out of the with string given.
        $this->filterAndConvertToArray($where);
        // Create a usable sql clause.
        $selectWhereClause = $this->constructSQLClause(' AND ', $this->getColumns());

        return $this->setLastIdWhere(
            $this->getEntity(),
            $selectWhereClause
        );
    }

    /**
     * @Then /^(?:|I )should have(?:| an| a) "([^"]*)" with:$/
     */
    public function iShouldHaveAWithTable($entity, TableNode $with)
    {
        $clause = $this->convertTableNodeToSingleContextClause($with);
        $sql = $this->iShouldHaveAWith($entity, $clause);

        return $sql;
    }

    /**
     * @Then /^(?:|I )should have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     */
    public function iShouldHaveAWith($entity, $with)
    {
        $this->debugLog('------- I SHOULD HAVE A WITH -------');

        $this->setEntity($entity);

        // Create array out of the with string given.
        $this->filterAndConvertToArray($with);

        // Set the clause type.
        $this->setClauseType('select');

        // Create a usable sql clause.
        $selectWhereClause = $this->constructSQLClause(' AND ', $this->getColumns());

        // Create the sql to be inserted.
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s',
            $this->getEntity(),
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
                    $this->getEntity()
                ));
            }
        }

        return $sql;
    }

    /**
     * @Then /^(?:|I )should not have(?:| an| a) "([^"]*)" with:$/
     */
    public function iShouldNotHaveAWithTable($entity, TableNode $with)
    {
        $clause = $this->convertTableNodeToSingleContextClause($with);
        $sql = $this->iShouldNotHaveAWith($entity, $clause);

        return $sql;
    }

    /**
     * @Then /^(?:|I )should not have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     */
    public function iShouldNotHaveAWith($entity, $with)
    {
        $this->debugLog('------- I SHOULD NOT HAVE A WHERE -------');

        $this->setEntity($entity);

        // Create array out of the with string given.
        $this->filterAndConvertToArray($with);

        // Set clause type.
        $this->setClauseType('select');

        // Create a usable sql clause.
        $selectWhereClause = $this->constructSQLClause(' AND ', $this->getColumns());

        // Create the sql to be inserted.
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s',
            $this->getEntity(),
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
                    $this->getEntity()
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
        $this->debugLog('------- I SAVE THE ID -------');

        $this->setKeyword($key, $this->getLastInsertId());

        return $this;
    }

    /**
     * @Given /^(?:|I )am in debug mode$/
     */
    public function iAmInDebugMode()
    {
        $this->debugLog('------- I AM IN DEBUG MODE -------');

        if (! defined('DEBUG_MODE')) {
            define('DEBUG_MODE', 1);
        }
    }
}
