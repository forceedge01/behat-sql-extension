<?php

namespace Genesis\SQLExtension\Tests\Unit\Context;

use ReflectionClass;
use Genesis\SQLExtension\Context\SQLHistory;
use Genesis\SQLExtension\Tests\TestHelper;

/**
 * @group unit
 * @group sqlHistory
 */
class SQLHistoryTest extends TestHelper
{
    /**
     * @var object  The object to be tested.
     */
    protected $testObject;

    /**
     * @var array  The test object dependencies.
     */
    protected $dependencies = [];

    /**
     * Set up the testing object.
     */
    public function setUp()
    {
        $reflection = new ReflectionClass(SQLHistory::class);
        $this->testObject = $reflection->newInstanceArgs($this->dependencies);
    }

    /**
     * testGetHistory Test that getHistory executes as expected.
     */
    public function testGetHistory()
    {
        $expected = [
            'select' => [],
            'insert' => [],
            'delete' => [],
            'update' => []
        ];

        // Execute
        $result = $this->testObject->getHistory();

        // Assert Result
        $this->assertEquals($expected, $result);

        $modified = [
            'select' => ['lajhdsf' => 234],
            'insert' => [],
            'delete' => ['aljkshdjflhasdf' => 37],
            'update' => []
        ];

        $this->accessProperty('history')->setValue($this->testObject, $modified);

        // Execute
        $result = $this->testObject->getHistory();

        // Assert Result
        $this->assertEquals($modified, $result);
    }

    /**
     * testResetHistory Test that resetHistory executes as expected.
     */
    public function testResetHistory()
    {
        $modified = [
            'select' => ['lajhdsf' => 234],
            'insert' => [],
            'delete' => ['aljkshdjflhasdf' => 37],
            'update' => []
        ];

        $this->accessProperty('history')->setValue($this->testObject, $modified);

        // Execute
        $result = $this->testObject->getHistory();

        // Assert Result
        $this->assertEquals($modified, $result);

        $expected = [
            'select' => [],
            'insert' => [],
            'delete' => [],
            'update' => []
        ];

        // Execute
        $this->testObject->resetHistory();
        $result = $this->testObject->getHistory();

        // Assert Result
        $this->assertEquals($expected, $result);
    }

    /**
     * testAddToHistory Test that addToHistory executes as expected.
     */
    public function testAddToHistory()
    {
        // Prepare / Mock
        $this->testObject->addToHistory('select', 'SELECT * FROM user');
        $this->testObject->addToHistory('select', 'SELECT * FROM ya');
        $this->testObject->addToHistory('update', 'UPDATE user', 1232);
        $this->testObject->addToHistory('delete', 'DELETE from user');
        $this->testObject->addToHistory('insert', 'INSERT INTO user', 123);

        $expectedHistory = [
            'select' => [
                ['sql' => 'SELECT * FROM user', 'last_id' => ''],
                ['sql' => 'SELECT * FROM ya', 'last_id' => '']
            ],
            'insert' => [
                ['sql' => 'INSERT INTO user', 'last_id' => 123]
            ],
            'delete' => [
                ['sql' => 'DELETE from user', 'last_id' => '']
            ],
            'update' => [
                ['sql' => 'UPDATE user', 'last_id' => 1232]
            ]
        ];

        // Execute
        $result = $this->testObject->getHistory();

        // Assert Result
        $this->assertEquals($expectedHistory, $result);
    }
}
