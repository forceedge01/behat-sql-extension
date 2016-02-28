<?php

namespace Genesis\SQLExtension\Tests\Context;

use Genesis\SQLExtension\Context\SQLContext;
use PHPUnit_Framework_TestCase;

class SQLContextTest extends PHPUnit_Framework_TestCase
{
    private $testObject;

    public function __construct()
    {
        $this->testObject = new SQLContext();
    }

    /**
     * @expectedException Exception
     */
    public function testIHaveAWhere()
    {
        $entity = '';
        $column = '';

        $this->testObject->iHaveAWhere($entity, $column);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testIHaveAWhereWithValues()
    {
        $this->markTestIncomplete('To be implemented');

        $entity = '';
        $column = '';

        $this->testObject->iHaveAWhere($entity, $column);
    }

    /**
     * @expectedException Exception
     */
    public function testIDontHaveAWhere()
    {
        $entity = '';
        $column = '';

        $this->testObject->iDontHaveAWhere($entity, $column);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testIDontHaveAWhereWithValues()
    {
        $this->markTestIncomplete('To be implemented');

        $entity = '';
        $column = '';

        $this->testObject->iDontHaveAWhere($entity, $column);
    }

    /**
     * @expectedException Exception
     */
    public function testiHaveAnExistingWithWhere()
    {
        $entity = '';
        $with = '';
        $columns = [];

        $this->testObject->iHaveAnExistingWithWhere($entity, $with, $columns);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testiHaveAnExistingWithWhereWithValues()
    {
        $this->markTestIncomplete('To be implemented');

        $entity = '';
        $with = '';
        $columns = [];

        $this->testObject->iHaveAnExistingWithWhere($entity, $with, $columns);
    }

    /**
     * @expectedException Exception
     */
    public function testiShouldHaveAWith()
    {
        $entity = '';
        $with = '';

        $this->testObject->iShouldHaveAWith($entity, $with);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testiShouldHaveAWithWithValues()
    {
        $this->markTestIncomplete('To be implemented');

        $entity = '';
        $with = '';

        $this->testObject->iShouldHaveAWith($entity, $with);
    }

    /**
     * @expectedException Exception
     */
    public function testISaveTheIdAs()
    {
        $key = 'myval';

        $this->testObject->iSaveTheIdAs($key);
    }
}
