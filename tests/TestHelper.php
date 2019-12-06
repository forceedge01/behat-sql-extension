<?php

namespace Genesis\SQLExtension\Tests;

use IteratorAggregate;

// Mock pdo class for testing.
class PDO
{
    const FETCH_NUM = 3;
    const FETCH_ORI_FIRST = 2;

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

class TestPDOStatement implements IteratorAggregate
{
    public $queryString;

    /* Methods */
    public function bindColumn($column, $param, $type, $maxlen, $driverdata)
    {}
    public function bindParam($parameter, $variable, $data_type = \PDO::PARAM_STR, $length = 0, $driver_options)
    {}
    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {}
    public function closeCursor()
    {}
    public function columnCount()
    {}
    public function debugDumpParams()
    {}
    public function errorCode()
    {}
    public function errorInfo()
    {}
    public function execute(array $input_parameters = [])
    {}
    public function fetch($fetch_style = 0, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {}
    public function fetchAll($fetch_style = 0, $fetch_argument = 0, array $ctor_args = array())
    {}
    public function fetchColumn($column_number = 0)
    {}
    public function fetchObject($class_name = "stdClass", array $ctor_args)
    {}
    public function getAttribute($attribute)
    {}
    public function getColumnMeta($column)
    {}
    public function nextRowset()
    {}
    public function rowCount()
    {}
    public function setAttribute($attribute, $value)
    {}
    public function setFetchMode($mode)
    {}
    public function getIterator()
    {}
}

namespace Genesis\SQLExtension\Tests;

use Exception;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

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
        $statementMock = $this->getMockBuilder(TestPDOStatement::class)
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

            $statementMock->expects($this->any())
                ->method('fetch')
                ->willReturn($fetchAll[0]);
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

    /**
     * @param string $class
     *
     * @return PHPUnit_Mock
     */
    protected function createMock($class)
    {
        return $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param string $method The method to make accessible.
     *
     * @return ReflectionMethod
     */
    protected function accessMethod($method)
    {
        $reflectionMethod = new ReflectionMethod(get_class($this->testObject), $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod;
    }


    /**
     * @param string $property The property to make accesible.
     *
     * @return ReflectionProperty
     */
    protected function accessProperty($property)
    {
        $reflection = new ReflectionClass(get_class($this->testObject));
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection;
    }
}
