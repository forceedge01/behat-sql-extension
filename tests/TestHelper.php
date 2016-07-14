<?php

namespace Genesis\SQLExtension\Context;

// Mock pdo class for testing.
class PDO
{
    private $dns;
    private $username;
    private $password;

    public function __construct($dns = null, $username = null, $password = null)
    {
        $this->dns = $dns;
        $this->username = $username;
        $this->password = $password;
    }

    public function getDns()
    {
        return $this->dns;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function prepare()
    {
        return;
    }

    public function lastInsertId()
    {
        return;
    }
}

namespace Genesis\SQLExtension\Tests;

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
    protected function mockDependency($dependency, $method, array $with = null, $return = true)
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
    protected function mockDependencyMethods($dependency, array $methods)
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

    /**
     * Mock dependency using value map.
     *
     * @param string $dependency
     * @param string $method
     * @param array $valueMap
     */
    protected function mockDependencyValueMap($dependency, $method, array $valueMap)
    {
        $this->dependencies[$dependency]->expects($this->any())
            ->method($method)
            ->will($this->returnValueMap($valueMap));
    }
}
