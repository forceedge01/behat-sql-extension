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

        // $result = $pdoConnection->query('select * from User');

        // // BEGIN PREX
        // $vars = array(
        //     $result->fetchAll(),
        //     //get_class($result),
        //     //get_class_methods($result),
        //     //get_defined_vars(),
        //     //get_defined_constants(),
        // );
        // echo '<pre>';
        // foreach ($vars as $key => $var) {
        //     var_dump($var);
        //     echo PHP_EOL . '===============================' . PHP_EOL . PHP_EOL;
        // }
        // echo 'Debug backtrace ' . PHP_EOL;
        // print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5));
        // echo 'PREX output from: ' . __FILE__ . ', Line: ' . __LINE__;
        // exit;
        // // END PREX
        

        $pdoConnection->query(
            'DROP TABLE User;'
        );
    }
}
