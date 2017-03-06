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
class SQLContext extends API implements Interfaces\SQLContextInterface
{
    /**
     * @Given /^(?:|I )have(?:| an| a) "([^"]*)" where:$/
     */
    public function iHaveWhere($entity, TableNode $nodes)
    {
        $queries = $this->convertTableNodeToQueries($nodes);
        $sqls = [];

        foreach ($queries as $query) {
            $sqls[] = $this->insert($entity, $query);
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
            $sqls[] = $this->insert($node[0], $node[1]);
        }

        return $sqls;
    }

    /**
     * @Given /^(?:|I )have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     */
    public function iHaveAWhere($entity, $columns)
    {
        return $this->insert($entity, $columns);
    }

    /**
     * @Given /^(?:|I )don't have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )don't have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     * @Given /^(?:|I )do not have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     */
    public function iDontHaveAWhere($entity, $columns)
    {
        return $this->delete($entity, $columns);
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
            $sqls[] = $this->delete($node[0], $node[1]);
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
            $sqls[] = $this->delete($entity, $query);
        }

        return $sqls;
    }

    /**
     * @Given /^(?:|I )have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns)
    {
        return update($entity, $with, $columns);
    }

    /**
     * @Given /^(?:|I )have(?:| an| a) existing "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWhere($entity, $where)
    {
        return $this->select($entity, $where);
    }

    /**
     * @Then /^(?:|I )should have(?:| an| a) "([^"]*)" with:$/
     */
    public function iShouldHaveAWithTable($entity, TableNode $with)
    {
        // Convert the table node to parse able string.
        $clause = $this->convertTableNodeToSingleContextClause($with);

        // Run through the shouldHaveWith step definition.
        $sql = $this->assertExists($entity, $clause);

        return $sql;
    }

    /**
     * @Then /^(?:|I )should have(?:| an| a) "([^"]*)" with "([^"]*)"(?:| in the database)$/
     */
    public function iShouldHaveAWith($entity, $with)
    {
        return $this->assertExists($entity, $with);
    }

    /**
     * @Then /^(?:|I )should not have(?:| an| a) "([^"]*)" with "([^"]*)"(?:| in the database)$/
     */
    public function iShouldNotHaveAWith($entity, $with)
    {
        return $this->assertNotExists($entity, $with);
    }

    /**
     * @Then /^(?:|I )should not have(?:| an| a) "([^"]*)" with:$/
     */
    public function iShouldNotHaveAWithTable($entity, TableNode $with)
    {
        // Convert the table node to parse able string.
        $clause = $this->convertTableNodeToSingleContextClause($with);

        // Run through the shouldNotHave step definition.
        $sql = $this->assertNotExists($entity, $clause);

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
