<?php

namespace Genesis\SQLExtension\Context\Interfaces;

interface SQLContextInterface
{
    /**
     * @Given /^I have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^I have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iHaveAWhere($entity, $columns);

    /**
     * @Given /^I don't have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^I don't have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iDontHaveAWhere($entity, $columns);

    /**
     * @Given /^I have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns);

    /**
     * @Then /^I should have a "([^"]*)" with "([^"]*)"$/
     */
    public function iShouldHaveAWith($entity, $with);

    /**
     * @Given /^I save the id as "([^"]*)"$/
     */
    public function iSaveTheIdAs($key);

    /**
     * @Given /^I am in debug mode$/
     */
    public function iAmInDebugMode();
}
