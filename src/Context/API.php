<?php

namespace Genesis\SQLExtension\Context;

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
 * SQL API.
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
class API extends SQLHandler implements Interfaces\APIInterface
{
    /**
     * {@inheritDoc}
     */
    public function select($table, array $columns)
    {
        $this->debugLog('------- SELECT -------');

        $this->setEntity($table);
        $this->setCommandType('select');

        // This makes the library only support AND and not OR in sql queries.
        // $query = $this->convertToQuery($columns);
        $resolvedValues = $this->resolveQuery($columns);

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $columns, $resolvedValues);

        // $searchConditionOperator = $this->get('sqlBuilder')->getSearchConditionOperatorForColumns($query);
        $searchConditionOperator = ' AND ';
        $selectWhereClause = $this->constructSQLClause(
            $this->getCommandType(),
            $searchConditionOperator,
            $this->queryParams->getResolvedValues()
        );

        $selectQueryBuilder = new Builder\SelectQueryBuilder($this->queryParams);
        $selectQueryBuilder->setWhereClause($selectWhereClause);
        $query = Builder\QueryDirector::build($selectQueryBuilder);

        try {
            // Execute sql for setting last id.
            return $this->setKeywordsFromQuery(
                $query
            );
        } catch (Exception $e) {
            throw new Exceptions\SelectException($this->getEntity(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function insert($table, array $values)
    {
        $this->debugLog('------- INSERT -------');

        // Normalize data.
        $this->setEntity($table);

        // $query = $this->convertToQuery($values);
        $resolvedValues = $this->resolveQuery($values);

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $values, $resolvedValues);
        list($columnNames, $columnValues) = $this->getTableColumns(
            $this->queryParams->getTable(),
            $this->queryParams->getResolvedValues()
        );

        $insertQueryBuilder = new Builder\InsertQueryBuilder($this->queryParams, $columnNames, $columnValues);
        $query = Builder\QueryDirector::build($insertQueryBuilder);

        try {
            $this->setCommandType('insert');
            $statement = $this->execute($query->getSql());

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

        $queryBuilder = new Builder\SelectQueryBuilder($this->queryParams);
        $queryBuilder->setWhereClause("{$this->primaryKey} = {$this->quoteOrNot($lastId)}");
        $selectQuery = Builder\QueryDirector::build($queryBuilder);

        $this->setKeywordsFromQuery($selectQuery);
        $this->get('dbManager')->closeStatement($statement);

        return $query->getSql();
    }

    /**
     * {@inheritDoc}
     */
    public function update($table, array $with, array $where)
    {
        $this->debugLog('------- UPDATE -------');

        if (! $where) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($table);
        $this->setCommandType('update');

        // Build up the update clause.
        // $query = $this->convertToQuery($with);
        $with = $this->resolveQuery($with);
        $updateClause = $this->constructSQLClause($this->getCommandType(), ', ', $with);

        // $query = $this->convertToQuery($columns);
        $resolvedValues = $this->resolveQuery($where);

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $where, $resolvedValues);

        // $searchConditionOperator = $this->get('sqlBuilder')->getSearchConditionOperatorForColumns($query);
        $whereClause = $this->constructSQLClause(
            $this->getCommandType(),
            ' AND ',
            $this->queryParams->getResolvedValues()
        );

        $updateQueryBuilder = new Builder\UpdateQueryBuilder($this->queryParams, $updateClause);
        $updateQueryBuilder->setWhereClause($whereClause);
        $query = Builder\QueryDirector::build($updateQueryBuilder);

        try {
            // Execute statement.
            $statement = $this->execute($query->getSql());

            $queryBuilder = new Builder\SelectQueryBuilder($this->queryParams);
            $queryBuilder->setWhereClause($whereClause);
            $selectQuery = Builder\QueryDirector::build($queryBuilder);

            // If no exception is throw, save the last id.
            $this->setKeywordsFromQuery(
                $selectQuery
            );
        } catch (Exception $e) {
            throw new Exceptions\UpdateException($this->getEntity(), $e);
        }

        $this->get('dbManager')->closeStatement($statement);

        return $query->getSql();
    }

    /**
     * {@inheritDoc}
     */
    public function delete($table, array $where)
    {
        $this->debugLog('------- DELETE -------');

        if (! $where) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($table);
        $this->setCommandType('delete');

        // $query = $this->convertToQuery($where);
        $resolvedValues = $this->resolveQuery($where);

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $where, $resolvedValues);

        // $searchConditionOperator = $this->get('sqlBuilder')->getSearchConditionOperatorForColumns($query);
        $whereClause = $this->constructSQLClause(
            $this->getCommandType(),
            ' AND ',
            $this->queryParams->getResolvedValues()
        );

        $deleteQueryBuilder = new Builder\DeleteQueryBuilder($this->queryParams);
        $deleteQueryBuilder->setWhereClause($whereClause);
        $query = Builder\QueryDirector::build($deleteQueryBuilder);

        try {
            // Execute statement.
            $statement = $this->execute($query->getSql());

            // Throw an exception if errors are found.
            $this->throwExceptionIfErrors($statement);
        } catch (Exception $e) {
            throw new Exceptions\DeleteException($this->getEntity(), $e);
        }

        $this->get('dbManager')->closeStatement($statement);

        return $query->getSql();
    }

    /**
     * {@inheritDoc}
     */
    public function assertExists($table, array $where)
    {
        $this->debugLog('------- EXISTS -------');
        $this->setEntity($table);
        $this->setCommandType('select');

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $where);

        // $query = $this->convertToQuery($where);
        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $where);

        $selectQueryBuilder = new Builder\SelectQueryBuilder($this->queryParams);
        $selectQueryBuilder->setWhereClause($selectWhereClause);
        $query = Builder\QueryDirector::build($selectQueryBuilder);

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        $statement = $this->execute($query->getSql());
        if (! $this->hasFetchedRows($statement)) {
            throw new Exceptions\RecordNotFoundException(
                $selectWhereClause,
                $this->getEntity()
            );
        }

        $this->get('dbManager')->closeStatement($statement);

        return $query->getSql();
    }

    /**
     * {@inheritDoc}
     */
    public function assertNotExists($table, array $with)
    {
        $this->debugLog('------- NOT-EXISTS -------');

        $this->setEntity($table);
        $this->setCommandType('select');

        $this->queryParams = new Representations\QueryParams($this->getEntity(), $with);

        // $query = $this->convertToQuery($with);
        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $with);

        $selectQueryBuilder = new Builder\SelectQueryBuilder($this->queryParams);
        $selectQueryBuilder->setWhereClause($selectWhereClause);
        $query = Builder\QueryDirector::build($selectQueryBuilder);

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        $statement = $this->execute($query->getSql());

        if ($this->hasFetchedRows($statement)) {
            throw new Exceptions\RecordFoundException(
                $selectWhereClause,
                $this->getEntity()
            );
        }

        $this->get('dbManager')->closeStatement($statement);

        return $query->getSql();
    }
}
