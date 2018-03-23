<?php

namespace Genesis\SQLExtension\Context;

use Exception;

session_start();

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

        $this->resolveEntity($table);
        $this->setCommandType('select');

        $resolvedValues = $this->resolveQuery($columns);
        $this->queryParams = new Representations\QueryParams($this->getEntity(), $columns, $resolvedValues);

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
    public function subSelect($table, $column, array $values)
    {
        $where = $this->get('sqlBuilder')->convertArrayToContextQueryFormat($values);

        // Use existing external ref resolution mechanism.
        return "[$table.$column|$where]";
    }

    /**
     * {@inheritDoc}
     */
    public function insert($table, array $values)
    {
        $this->debugLog('------- INSERT -------');

        // Normalize data.
        $entity = $this->resolveEntity($table);
        $resolvedValues = $this->resolveQuery($values);

        $this->queryParams = new Representations\QueryParams($entity, $values, $resolvedValues);
        list($columnNames, $columnValues) = $this->getTableColumns(
            $this->queryParams->getEntity(),
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
            if (! $lastId && isset($resolvedValues[$entity->getPrimaryKey()])) {
                $lastId = $resolvedValues[$entity->getPrimaryKey()];
            }
        } catch (Exception $e) {
            throw new Exceptions\InsertException($entity, $e);
        }

        $queryBuilder = new Builder\SelectQueryBuilder($this->queryParams);
        $queryBuilder->setWhereClause("{$entity->getPrimaryKey()} = {$this->quoteOrNot($lastId)}");
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

        $entity = $this->resolveEntity($table);
        $this->setCommandType('update');

        // Build up the update clause.
        $with = $this->resolveQuery($with);
        $updateClause = $this->constructSQLClause($this->getCommandType(), ', ', $with);

        // $query = $this->convertToQuery($columns);
        $resolvedValues = $this->resolveQuery($where);

        $this->queryParams = new Representations\QueryParams($entity, $where, $resolvedValues);

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

        $entity = $this->resolveEntity($table);
        $this->setCommandType('delete');
        $resolvedValues = $this->resolveQuery($where);

        $this->queryParams = new Representations\QueryParams($entity, $where, $resolvedValues);

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
        $entity = $this->resolveEntity($table);
        $this->setCommandType('select');

        $this->queryParams = new Representations\QueryParams($entity, $where);
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

        $entity = $this->resolveEntity($table);
        $this->setCommandType('select');

        $this->queryParams = new Representations\QueryParams($entity, $with);
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
