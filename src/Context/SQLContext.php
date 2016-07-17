<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\Step\Given;
use Behat\Gherkin\Node\TableNode;
use Exception;

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
        $this->debugLog('Trying to select existing record.');

        // Normalize data.
        $this->setEntity($entity);
        $this->setCommandType('select');

        // Convert columns given to an array.
        $columns = $this->convertToFilteredArray($columns);

        // Check if the record exists.
        $whereClause = $this->constructSQLClause($this->getCommandType(), ' AND ', $columns);

        // Build up the sql command.
        $sql = sprintf('SELECT * FROM %s WHERE %s', $this->getEntity(), $whereClause);

        // Execute statement.
        $statement = $this->execute($sql);

        // If it does, set the last id and return.
        if ($this->hasFetchedRows($statement)) {
            // Set the last id to use from fetched row.
            $result = $statement->fetchAll();

            $this->setKeywordsFromRecord($this->getEntity(), $result[0]);

            if (isset($result[0]['id'])) {
                $this->handleLastId($this->getEntity(), $result[0]['id']);
            }

            return $sql;
        }

        $this->debugLog('No record found, trying to insert.');

        $this->setCommandType('insert');

        // If the record does not already exist, create it.
        list($columnNames, $columnValues) = $this->getTableColumns($this->getEntity());

        // Build up the sql.
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->getEntity(), $columnNames, $columnValues);
        $statement = $this->execute($sql);

        // Throw exception if no rows were effected.
        $this->throwErrorIfNoRowsAffected($statement, self::IGNORE_DUPLICATE);
        $result = $statement->fetchAll();

        // Extract duplicate key and run update using it
        if ($key = $this->getKeyFromDuplicateError($result)) {
            // DEPCRECIATED, Probably need to get rid of this logic.
            $this->debugLog(sprintf('Duplicate key found, running update using key "%s"', $key));

            $this->iHaveAnExistingWithWhere(
                $this->getEntity(),
                $columns,
                sprintf('%s:%s', $key, $columns[$key])
            );

            $whereClause = sprintf('%s = %s', $key, $this->quoteOrNot($columns[$key]));
        }

        try {
            $this->setKeywordsFromCriteria($this->getEntity(), $whereClause);
        } catch (\Exception $e) {
            // ignore, as the keys may not be set because of dynamic function usage.
        }

        return $sql;
    }

    /**
     * User friendly version of iHaveAWith.
     *
     * @param $table The table to insert into.
     * @param $values Values to insert.
     *
     * @return string
     */
    public function insert($table, $values)
    {
        return $this->iHaveAWhere($table, $values);
    }

    /**
     * @Given /^(?:|I )don't have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )don't have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     * @Given /^(?:|I )do not have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     */
    public function iDontHaveAWhere($entity, $columns)
    {
        $this->debugLog('------- I DONT HAVE WHERE -------');

        if (! $columns) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($entity);
        $this->setCommandType('delete');

        // Construct the where clause.
        $columns = $this->convertToFilteredArray($columns);
        $whereClause = $this->constructSQLClause($this->getCommandType(), ' AND ', $columns);

        // Construct the delete statement.
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->getEntity(), $whereClause);

        // Execute statement.
        $statement = $this->execute($sql);

        // Throw an exception if errors are found.
        $this->throwExceptionIfErrors($statement);

        return $sql;
    }

    /**
     * @Given /^(?:|I )don't have:$/
     * @Given /^(?:|I )do not have:$/
     */
    public function iDontHave(TableNode $nodes)
    {
        // Get all table node rows.
        $nodes = $nodes->getRows();

        // Get rid of first row as its just for readability.
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
        // Convert table node to parse able string.
        $queries = $this->convertTableNodeToQueries($nodes);
        $sqls = [];

        // Run through the dontHave step definition for each query.
        foreach ($queries as $query) {
            $sqls[] = $this->iDontHaveAWhere($entity, $query);
        }

        return $sqls;
    }

    /**
     * User friendly version of iDontHaveAWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function delete($table, $where)
    {
        return $this->iDontHaveAWhere($table, $where);
    }

    /**
     * @Given /^(?:|I )have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns)
    {
        $this->debugLog('------- I HAVE AN EXISTING WITH WHERE -------');

        if (! $columns) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($entity);
        $this->setCommandType('update');

        // Build up the update clause.
        $with = $this->convertToFilteredArray($with);
        $updateClause = $this->constructSQLClause($this->getCommandType(), ', ', $with);

        // Build up the where clause.
        $columns = $this->convertToFilteredArray($columns);
        $whereClause = $this->constructSQLClause($this->getCommandType(), ' AND ', $columns);

        // Build up the update statement.
        $sql = sprintf('UPDATE %s SET %s WHERE %s', $this->getEntity(), $updateClause, $whereClause);

        // Execute statement.
        $statement = $this->execute($sql);

        // Throw an exception if no rows are effected.
        $this->throwErrorIfNoRowsAffected($statement, self::IGNORE_DUPLICATE);

        // If no exception is throw, save the last id.
        $this->setKeywordsFromCriteria(
            $this->getEntity(),
            $whereClause
        );

        return $sql;
    }

    /**
     * User friendly version of iHaveAnExistingWithWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function update($table, $update, $where)
    {
        return $this->iHaveAnExistingWithWhere($table, $update, $where);
    }

    /**
     * @Given /^(?:|I )have(?:| an| a) existing "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWhere($entity, $where)
    {
        $this->debugLog('------- I HAVE AN EXISTING WHERE -------');

        $this->setEntity($entity);
        $this->setCommandType('select');

        // Create array out of the with string given.
        $columns = $this->convertToFilteredArray($where);

        // Create a usable sql clause.
        $selectWhereClause = $this->constructSQLClause($this->getCommandType(), ' AND ', $columns);

        // Execute sql for setting last id.
        return $this->setKeywordsFromCriteria(
            $this->getEntity(),
            $selectWhereClause
        );
    }

    /**
     * User friendly version of iHaveAnExistingWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function select($table, $where)
    {
        return $this->iHaveAnExistingWhere($table, $where);
    }

    /**
     * @Then /^(?:|I )should have(?:| an| a) "([^"]*)" with:$/
     */
    public function iShouldHaveAWithTable($entity, TableNode $with)
    {
        // Convert the table node to parse able string.
        $clause = $this->convertTableNodeToSingleContextClause($with);

        // Run through the shouldHaveWith step definition.
        $sql = $this->iShouldHaveAWith($entity, $clause);

        return $sql;
    }

    /**
     * @Then /^(?:|I )should have(?:| an| a) "([^"]*)" with "([^"]*)"(?:| in the database)$/
     */
    public function iShouldHaveAWith($entity, $with)
    {
        $this->debugLog('------- I SHOULD HAVE A WITH -------');
        $this->setEntity($entity);

        // Create array out of the with string given.
        $columns = $this->convertToFilteredArray($with);

        // Set the clause type.
        $this->setCommandType('select');

        // Create a usable sql clause.
        $selectWhereClause = $this->constructSQLClause($this->getCommandType(), ' AND ', $columns);

        // Create the sql to be inserted.
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s',
            $this->getEntity(),
            $selectWhereClause
        );

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        $statement = $this->execute($sql);
        if (! $this->hasFetchedRows($statement)) {
            throw new Exceptions\RecordNotFoundException(
                $selectWhereClause,
                $this->getEntity()
            );
        }

        return $sql;
    }

    /**
     * @Then /^(?:|I )should not have(?:| an| a) "([^"]*)" with "([^"]*)"(?:| in the database)$/
     */
    public function iShouldNotHaveAWith($entity, $with)
    {
        $this->debugLog('------- I SHOULD NOT HAVE A WHERE -------');

        $this->setEntity($entity);

        // Create array out of the with string given.
        $columns = $this->convertToFilteredArray($with);

        // Set clause type.
        $this->setCommandType('select');

        // Create a usable sql clause.
        $selectWhereClause = $this->constructSQLClause($this->getCommandType(), ' AND ', $columns);

        // Create the sql to be inserted.
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s',
            $this->getEntity(),
            $selectWhereClause
        );

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        $statement = $this->execute($sql);

        if ($this->hasFetchedRows($statement)) {
            throw new Exceptions\RecordFoundException(
                $selectWhereClause,
                $this->getEntity()
            );
        }

        return $sql;
    }

    /**
     * @Then /^(?:|I )should not have(?:| an| a) "([^"]*)" with:$/
     */
    public function iShouldNotHaveAWithTable($entity, TableNode $with)
    {
        // Convert the table node to parse able string.
        $clause = $this->convertTableNodeToSingleContextClause($with);

        // Run through the shouldNotHave step definition.
        $sql = $this->iShouldNotHaveAWith($entity, $clause);

        return $sql;
    }

    /**
     * @Given /^(?:|I )save the id as "([^"]*)"$/
     */
    public function iSaveTheIdAs($key)
    {
        $this->debugLog('------- I SAVE THE ID -------');

        $this->setKeyword($key, $this->getLastId());

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
