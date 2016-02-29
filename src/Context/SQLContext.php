<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\Step\Given;

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
     * @Given /^(?:|I )have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iHaveAWhere($entity, $columns)
    {
        $this->filterAndConvertToArray($columns);
        list($columnNames, $columnValues) = $this->getTableColumns($entity);

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $entity, $columnNames, $columnValues);
        $result = $this->execute($sql, self::IGNORE_DUPLICATE);

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

        return $this;
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

        $this->filterAndConvertToArray($columns);
        $whereClause = $this->constructClause(' AND ', $this->getColumns());

        $sql = sprintf('DELETE FROM %s WHERE %s', $entity, $whereClause);
        $this->execute($sql);

        return $this;
    }

    /**
     * @Given /^(?:|I )have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns)
    {
        if (! $columns) {
            throw new \Exception('You must provide a where clause!');
        }

        $this->filterAndConvertToArray($with);
        $updateClause = $this->constructClause(', ', $this->getColumns());
        $this->filterAndConvertToArray($columns);
        $whereClause = $this->constructClause(' AND ', $this->getColumns());

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $entity, $updateClause, $whereClause);
        $this->execute($sql, self::IGNORE_DUPLICATE);

        $this->setLastIdWhere(
            $entity,
            $whereClause
        );
    }

    /**
     * @Given /^I have an existing "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWhere($entity, $where)
    {
        // Create array out of the with string given.
        $this->filterAndConvertToArray($where);
        // Create a usable sql clause.
        $selectWhereClause = $this->constructClause(' AND ', $this->getColumns());

        $this->setLastIdWhere(
            $entity,
            $selectWhereClause
        );
    }

    /**
     * @Then /^(?:|I )should have a "([^"]*)" with "([^"]*)"$/
     */
    public function iShouldHaveAWith($entity, $with)
    {
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
            $this->execute($sql);
        } catch (\Exception $e) {
            if (! $this->hasFetchedRows()) {
                throw new \Exception(sprintf(
                    'Record not found with "%s" in "%s"',
                    $selectWhereClause,
                    $entity
                ));
            }
        }
    }

    /**
     * @Then /^(?:|I )should not have a "([^"]*)" with "([^"]*)"$/
     */
    public function iShouldNotHaveAWith($entity, $with)
    {
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
            $this->execute($sql);
        } catch (\Exception $e) {
            if ($this->hasFetchedRows()) {
                throw new \Exception(sprintf(
                    'Record not found with "%s" in "%s"',
                    $selectWhereClause,
                    $entity
                ));
            }
        }
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
