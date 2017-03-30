<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\Step\Given;
use Behat\Gherkin\Node\TableNode;
use Genesis\SQLExtension\Context\Exceptions;
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
    public function insert($table, array $values)
    {
        $this->debugLog('------- I HAVE WHERE -------');
        $this->debugLog('Trying to select existing record.');

        // Normalize data.
        $this->setEntity($table);

        $query = $this->convertToQuery($values);
        $resolvedValues = $this->resolveQuery($query);

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $values, $resolvedValues);

        // If the record does not already exist, create it.
        list($columnNames, $columnValues) = $this->getTableColumns(
            $this->queryParams->getTable(),
            $this->queryParams->getResolvedValues()
        );

        // Build up the sql.
        $this->setCommandType('insert');
        $sql = "INSERT INTO {$this->getEntity()} ({$columnNames}) VALUES ({$columnValues})";

        try {
            $statement = $this->execute($sql);

            // Throw exception if no rows were effected.
            $this->throwErrorIfNoRowsAffected($statement, Interfaces\SQLHandlerInterface::IGNORE_DUPLICATE);

            // If an ID was generated for us, use that to store results in keystore,
            // else use criteria.
            $lastId = $this->getLastId();
            if (! $lastId && isset($resolvedValues[$this->primaryKey])) {
                $lastId = $resolvedValues[$this->primaryKey];
            }
        } catch (Exception $e) {
            throw new Exceptions\InsertException($this->getEntity(), $e);
        }

        $this->setKeywordsFromId($lastId);
        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($table, array $columns)
    {
        $this->debugLog('------- I DONT HAVE WHERE -------');

        if (! $columns) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($table);
        $this->setCommandType('delete');

        $query = $this->convertToQuery($columns);
        $resolvedValues = $this->resolveQuery($query);

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $columns, $resolvedValues);

        $searchConditionOperator = $this->get('sqlBuilder')->getSearchConditionOperatorForColumns($query);
        $whereClause = $this->constructSQLClause(
            $this->getCommandType(),
            $searchConditionOperator,
            $this->queryParams->getResolvedValues()
        );

        // Construct the delete statement.
        $sql = "DELETE FROM {$this->getEntity()} WHERE {$whereClause}";

        try {
            // Execute statement.
            $statement = $this->execute($sql);

            // Throw an exception if errors are found.
            $this->throwExceptionIfErrors($statement);
        } catch (Exception $e) {
            throw new Exceptions\DeleteException($this->getEntity(), $e);
        }

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function update($table, array $with, array $columns)
    {
        $this->debugLog('------- I HAVE AN EXISTING WITH WHERE -------');

        if (! $columns) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($table);
        $this->setCommandType('update');

        // Build up the update clause.
        $query = $this->convertToQuery($with);
        $with = $this->resolveQuery($query);
        $updateClause = $this->constructSQLClause($this->getCommandType(), ', ', $with);

        $query = $this->convertToQuery($columns);
        $resolvedValues = $this->resolveQuery($query);

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $columns, $resolvedValues);

        $searchConditionOperator = $this->get('sqlBuilder')->getSearchConditionOperatorForColumns($query);
        $whereClause = $this->constructSQLClause(
            $this->getCommandType(),
            $searchConditionOperator,
            $this->queryParams->getResolvedValues()
        );

        // Build up the update statement.
        $sql = "UPDATE {$this->getEntity()} SET {$updateClause} WHERE {$whereClause}";

        try {
            // Execute statement.
            $statement = $this->execute($sql);

            // If no exception is throw, save the last id.
            $this->setKeywordsFromCriteria(
                $this->getEntity(),
                $whereClause
            );
        } catch (Exception $e) {
            throw new Exceptions\UpdateException($this->getEntity(), $e);
        }

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function select($table, array $columns)
    {
        $this->debugLog('------- I HAVE AN EXISTING WHERE -------');

        $this->setEntity($table);
        $this->setCommandType('select');

        $query = $this->convertToQuery($columns);
        $resolvedValues = $this->resolveQuery($query);

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $columns, $resolvedValues);

        $searchConditionOperator = $this->get('sqlBuilder')->getSearchConditionOperatorForColumns($query);
        $selectWhereClause = $this->constructSQLClause(
            $this->getCommandType(),
            $searchConditionOperator,
            $this->queryParams->getResolvedValues()
        );

        try {
            // Execute sql for setting last id.
            return $this->setKeywordsFromCriteria(
                $this->getEntity(),
                $selectWhereClause
            );
        } catch (Exception $e) {
            throw new Exceptions\SelectException($this->getEntity(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function assertExists($table, array $with)
    {
        $this->debugLog('------- I SHOULD HAVE A WITH -------');
        $this->setEntity($table);
        $this->setCommandType('select');

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $with);

        $query = $this->convertToQuery($with);
        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $query);

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
    public function assertNotExists($table, array $with)
    {
        $this->debugLog('------- I SHOULD NOT HAVE A WHERE -------');

        $this->setEntity($table);
        $this->setCommandType('select');

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $with);

        $query = $this->convertToQuery($with);
        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $query);

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
