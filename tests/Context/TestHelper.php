<?php

namespace Genesis\SQLExtension\Tests\Context;

use PHPUnit_Framework_TestCase;
use Exception;

class TestHelper extends PHPUnit_Framework_TestCase
{
    /**
     * The test object dependencies.
     */
    protected $dependencies = [];

    /**
     * Get PDO statement with 1 row, used for testing.
     */
    public function getPdoStatementWithRows($rowCount = true, $fetchAll = false)
    {
        $statementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($rowCount) {
            $statementMock->expects($this->any())
                ->method('rowCount')
                ->willReturn($rowCount);
        }

        if ($fetchAll) {
            $statementMock->expects($this->any())
                ->method('fetchAll')
                ->willReturn($fetchAll);
        }

        $statementMock->expects($this->any())
            ->method('execute')
            ->willReturn(true);

        return $statementMock;

        $this->checkIfDependencyCalled(
            'setCommandType',
            ['select'],
            'sqlBuilder',
            'setCommandType',
            ['select']
        );
    }

    /**
     * @param string $dependency
     * @param string $method
     * @param array $with
     * @param mixed $return This will return the string.
     */
    public function mockDependency($dependency, $method, array $with = null, $return = true)
    {
        if (! in_array($dependency, array_keys($this->dependencies))) {
            throw new Exception(sprintf('Dependency "%s" not found, available deps are: %s', $dependency, print_r(array_keys($this->dependencies), true)));
        }

        $mock = $this->dependencies[$dependency]->expects($this->any())
            ->method($method);

        if ($with) {
            $mock = call_user_func_array(array($mock, 'with'), $with);
        }

        if ($return !== false) {
            $mock->willReturn($return);
        }

        return $mock;
    }

    /**
     * Mock dependency methods.
     * 
     * @param string $dependency
     * @param array $methods
     */
    public function mockDependencyMethods($dependency, array $methods)
    {
        foreach ($methods as $method => $value) {
            $this->dependencies[$dependency]->expects($this->any())
                ->method($method)
                ->will($this->returnValue($value));
        }
    }

    /**
     * Get table node mock.
     */
    public function getTableNodeMock()
    {
        return $this->getMockBuilder(\Behat\Gherkin\Node\TableNode::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
