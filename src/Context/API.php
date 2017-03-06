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
class API extends SQLHandler implements Interfaces\APIInterface
{
    /**
     * {@inheritDoc}
     */
    public function insert($table, $values)
    {
        $this->debugLog('------- I HAVE WHERE -------');
        $this->debugLog('Trying to select existing record.');

        // Normalize data.
        $this->setEntity($entity);
        $columns = $this->resolveQuery($columns);

        // $this->debugLog('No record found, trying to insert.');
        $this->setCommandType('insert');

        // If the record does not already exist, create it.
        list($columnNames, $columnValues) = $this->getTableColumns($this->getEntity(), $columns);

        // Build up the sql.
        $sql = "INSERT INTO {$this->getEntity()} ({$columnNames}) VALUES ({$columnValues})";
        $statement = $this->execute($sql);

        // Throw exception if no rows were effected.
        $this->throwErrorIfNoRowsAffected($statement, self::IGNORE_DUPLICATE);
        $this->setKeywordsFromId($this->getLastId());

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($table, $where)
    {
        $this->debugLog('------- I DONT HAVE WHERE -------');

        if (! $columns) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($entity);
        $this->setCommandType('delete');

        $whereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $columns);

        // Construct the delete statement.
        $sql = "DELETE FROM {$this->getEntity()} WHERE {$whereClause}";

        // Execute statement.
        $statement = $this->execute($sql);

        // Throw an exception if errors are found.
        $this->throwExceptionIfErrors($statement);
        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function update($table, $update, $where)
    {
        $this->debugLog('------- I HAVE AN EXISTING WITH WHERE -------');

        if (! $columns) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($entity);
        $this->setCommandType('update');

        // Build up the update clause.
        $with = $this->resolveQuery($with);
        $updateClause = $this->constructSQLClause($this->getCommandType(), ', ', $with);

        $whereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $columns);

        // Build up the update statement.
        $sql = "UPDATE {$this->getEntity()} SET {$updateClause} WHERE {$whereClause}";

        // Execute statement.
        $statement = $this->execute($sql);

        // If no exception is throw, save the last id.
        $this->setKeywordsFromCriteria(
            $this->getEntity(),
            $whereClause
        );

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function select($table, $where)
    {
        $this->debugLog('------- I HAVE AN EXISTING WHERE -------');

        $this->setEntity($entity);
        $this->setCommandType('select');

        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $where);

        // Execute sql for setting last id.
        return $this->setKeywordsFromCriteria(
            $this->getEntity(),
            $selectWhereClause
        );
    }

    /**
     * {@inheritDoc}
     */
    public function assertExists($table, $where)
    {
        $this->debugLog('------- I SHOULD HAVE A WITH -------');
        $this->setEntity($entity);

        // Set the clause type.
        $this->setCommandType('select');

        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $with);

        // Create the sql to be inserted.
        $sql = "SELECT * FROM {$this->getEntity()} WHERE {$selectWhereClause}";

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        $statement = $this->execute($sql);
        if (! $this->hasFetchedRows($statement)) {
            throw new Exceptions\RecordNotFoundException(
                $selectWhereClause,
                $this->getEntity()
            );
        }

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function assertNotExists($table, $where)
    {
        $this->debugLog('------- I SHOULD NOT HAVE A WHERE -------');

        $this->setEntity($entity);

        // Set clause type.
        $this->setCommandType('select');

        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $with);

        // Create the sql to be inserted.
        $sql = "SELECT * FROM {$this->getEntity()} WHERE {$selectWhereClause}";

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        $statement = $this->execute($sql);

        if ($this->hasFetchedRows($statement)) {
            throw new Exceptions\RecordFoundException(
                $selectWhereClause,
                $this->getEntity()
            );
        }

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }
}
