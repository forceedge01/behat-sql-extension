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
class SQLContext extends API implements Interfaces\SQLContextInterface
{
    /**
     * Override any of the extension params through the context declaration.
     *
     * @param string $engine The engine to use.
     * @param string $host The host to connect to.
     * @param string $schema The schema to use.
     * @param string $dbname The dbname to connect to.
     * @param string $username The username.
     * @param string $password The password.
     * @param string $dbprefix The database prefix to use.
     * @param int $port
     */
    public function __construct(
        $engine = null,
        $host = null,
        $schema = null,
        $dbname = null,
        $username = null,
        $password = null,
        $dbprefix = null,
        $port = null
    ) {
        $dbConnectionDetails = [
            'engine' => $engine,
            'host' => $host,
            'port' => $port,
            'schema' => $schema,
            'name' => $dbname,
            'username' => $username,
            'password' => $password,
            'prefix' => $dbprefix,
        ];

        parent::__construct(
            new DBManager(new DatabaseProviders\Factory(), $dbConnectionDetails),
            new SQLBuilder(),
            new LocalKeyStore(),
            new SQLHistory()
        );
    }


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
        $columns = $this->get('sqlBuilder')->parseExternalQueryReferences($columns);
        $columns = $this->get('sqlBuilder')->convertToArray($columns);

        return $this->insert($entity, $columns);
    }

    /**
     * @Given /^(?:|I )don't have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )don't have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     * @Given /^(?:|I )do not have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     */
    public function iDontHaveAWhere($entity, $columns)
    {
        $columns = $this->get('sqlBuilder')->parseExternalQueryReferences($columns);
        $columns = $this->get('sqlBuilder')->convertToArray($columns);

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
     * @Given /^(?:|I )have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns)
    {
        $with = $this->get('sqlBuilder')->parseExternalQueryReferences($with);
        $with = $this->get('sqlBuilder')->convertToArray($with);

        $columns = $this->get('sqlBuilder')->parseExternalQueryReferences($columns);
        $columns = $this->get('sqlBuilder')->convertToArray($columns);

        return $this->update($entity, $with, $columns);
    }

    /**
     * @Given /^(?:|I )have(?:| an| a) existing "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWhere($entity, $where)
    {
        $where = $this->get('sqlBuilder')->parseExternalQueryReferences($where);
        $where = $this->get('sqlBuilder')->convertToArray($where);

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
        $sql = $this->iShouldHaveAWith($entity, $clause);

        return $sql;
    }

    /**
     * @Then /^(?:|I )should have(?:| an| a) "([^"]*)" with "([^"]*)"(?:| in the database)$/
     */
    public function iShouldHaveAWith($entity, $with)
    {
        $with = $this->get('sqlBuilder')->parseExternalQueryReferences($with);
        $with = $this->get('sqlBuilder')->convertToArray($with);

        return $this->assertExists($entity, $with);
    }

    /**
     * @Then /^(?:|I )should not have(?:| an| a) "([^"]*)" with "([^"]*)"(?:| in the database)$/
     */
    public function iShouldNotHaveAWith($entity, $with)
    {
        $with = $this->get('sqlBuilder')->parseExternalQueryReferences($with);
        $with = $this->get('sqlBuilder')->convertToArray($with);

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

        Debugger::enable(Debugger::MODE_ALL);
    }
}
