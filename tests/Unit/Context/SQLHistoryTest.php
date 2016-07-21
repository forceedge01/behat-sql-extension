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
     *
     * @expectedException Exception
     */
    public function testAddToHistoryInvalidCommandType()
    {
        // Execute
        $this->testObject->addToHistory('random', 'user', 'SELECT * FROM user');
    }

    /**
     * testAddToHistory Test that addToHistory executes as expected.
     */
    public function testAddToHistory()
    {
        // Prepare / Mock
        $this->testObject->addToHistory('select', 'user', 'SELECT * FROM user');
        $this->testObject->addToHistory('select', 'ya', 'SELECT * FROM ya');
        $this->testObject->addToHistory('update', 'user', 'UPDATE user', 1232);
        $this->testObject->addToHistory('delete', 'user', 'DELETE from user');
        $this->testObject->addToHistory('insert', 'user', 'INSERT INTO user', 123);

        $sqlHistory1 = \Genesis\SQLExtension\Context\Representations\History::instance()
            ->setEntity('user')
            ->setSql('SELECT * FROM user');

        $sqlHistory2 = \Genesis\SQLExtension\Context\Representations\History::instance()
            ->setEntity('ya')
            ->setSql('SELECT * FROM ya');

        $sqlHistory3 = \Genesis\SQLExtension\Context\Representations\History::instance()
            ->setEntity('user')
            ->setSql('UPDATE user')
            ->setLastId(1232);

        $sqlHistory4 = \Genesis\SQLExtension\Context\Representations\History::instance()
            ->setEntity('user')
            ->setSql('DELETE from user');

        $sqlHistory5 = \Genesis\SQLExtension\Context\Representations\History::instance()
            ->setEntity('user')
            ->setSql('INSERT INTO user')
            ->setLastId(123);

        $expectedHistory = [
            'select' => [
                $sqlHistory1,
                $sqlHistory2
            ],
            'insert' => [
                $sqlHistory5
            ],
            'delete' => [
                $sqlHistory4
            ],
            'update' => [
                $sqlHistory3
            ]
        ];

        // Execute
        $result = $this->testObject->getHistory();

        // Assert Result
        $this->assertEquals($expectedHistory, $result);
        $this->assertEquals('user', $result['insert'][0]->getEntity());
        $this->assertEquals('INSERT INTO user', $result['insert'][0]->getSql());
        $this->assertEquals(123, $result['insert'][0]->getLastId());
    }
}
