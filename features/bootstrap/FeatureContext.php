<?php

require __DIR__ . '/../../vendor/autoload.php';

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Genesis\SQLExtension\Context\SQLContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        // Setup database table here.
    }

    /**
     * @BeforeScenario
     */
    public function createTables(BeforeScenarioScope $scope)
    {
        $sqlContext = $scope->getEnvironment()->getContext(SQLContext::class);
        $pdoConnection = $sqlContext->get('dbManager')->getConnection();

        $pdoConnection->query(
            'CREATE TABLE IF NOT EXISTS User (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255),
                forename VARCHAR(50),
                lastname VARCHAR(50)
            );'
        );
    }

    /**
     * @AfterScenario
     */
    public function removeTable(AfterScenarioScope $scope)
    {
        $sqlContext = $scope->getEnvironment()->getContext(SQLContext::class);
        $pdoConnection = $sqlContext->get('dbManager')->getConnection();

        $pdoConnection->query(
            'DROP TABLE User;'
        );
    }
}
