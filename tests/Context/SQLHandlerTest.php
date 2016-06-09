<?php

namespace Genesis\SQLExtension\Tests\Context;

use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;
use Genesis\SQLExtension\Context\Interfaces\KeyStoreInterface;
use Genesis\SQLExtension\Context\Interfaces\SQLBuilderInterface;
use Genesis\SQLExtension\Context\SQLHandler;
use PHPUnit_Framework_TestCase;
use Exception;

/**
 * @group sqlHandler
 */
class SQLHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Object being tested.
     */
    private $testObject;

    /**
     * Test object dependencies.
     */
    private $dependencies;

    /**
     * Setup test object.
     */
    public function setup()
    {
        ini_set('error_reporting', E_ALL | E_STRICT);
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 'On');

        $this->dependencies['dbHelperMock'] = $this->getMockBuilder(DBManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dependencies['dbHelperMock']->expects($this->any())
            ->method('getPrimaryKeyForTable')
            ->will($this->returnValue('id'));

        $this->dependencies['dbHelperMock']->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($this->getPdoStatementWithRows(true, [[123]])));

        $this->dependencies['dbHelperMock']->expects($this->any())
            ->method('getParams')
            ->will($this->returnValue(
                ['DBPREFIX' => 'dev_', 'DBNAME' => 'mydb', 'DBSCHEMA' => 'myschema']
            ));

        $this->dependencies['sqlBuilderMock'] = $this->getMockBuilder(SQLBuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dependencies['keyStoreMock'] = $this->getMockBuilder(KeyStoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testObject = new SQLHandler(
            $this->dependencies['dbHelperMock'],
            $this->dependencies['sqlBuilderMock'],
            $this->dependencies['keyStoreMock']
        );
    }

    /**
     * testSampleData Test that sampleData executes as expected.
     */
    public function testSampleData()
    {
        $type = 'a type';
        $return = 'something';

        $this->mockDependency('sqlBuilderMock', 'sampleData', [$type], $return);

        $result = $this->testObject->sampleData($type);

        $this->assertEquals($return, $result);
    }

    /**
     * Test that the setCommandType works as expected.
     *
     * @expectedException Exception
     */
    public function testSetCommandType()
    {
        $type = 'random';

        $this->testObject->setCommandType($type);
    }

    public function testGetCommandType()
    {
        $type = 'delete';

        $this->testObject->setCommandType($type);

        $result = $this->testObject->getCommandType();

        $this->assertEquals($type, $result);
    }

    /**
     * Tests that constructSQLClause executes as expected with LIKE values.
     */
    public function testConstructSQLClauseLikeValues()
    {
        $commandType = 'select';
        $glue = ' AND ';
        $columns = ['abc', 123];

        $this->mockDependency('sqlBuilderMock', 'constructSQLClause', [$commandType, $glue, $columns], true);

        // Execute
        $result = $this->testObject->constructSQLClause('select', $glue, $columns);

        $this->assertTrue($result);
    }

    /**
     * testFilterAndConvertToArray Test that filterAndConvertToArray executes as expected.
     */
    public function testFilterAndConvertToArray()
    {
        $queries = 'abc:123';
        $expected = ['abc' => 123];

        $this->mockDependency('sqlBuilderMock', 'convertToArray', [$queries], $expected);
        $this->mockDependency('keyStoreMock', 'getKeywordFromConfigForKeyIfExists', [123], 123);

        // Execute
        $result = $this->testObject->filterAndConvertToArray($queries);

        $this->assertEquals($expected, $result);
    }

    /**
     * testDebugLog Test that debugLog executes as expected.
     */
    public function testDebugLog()
    {
        define('DEBUG_MODE', 1);

        // Start capturing the output to the screen.
        ob_start();

        // Message that is expected to be outputted.
        $msg = 'This is a message';

        // Execute.
        $this->testObject->debugLog($msg);

        // Output debug information.
        $log = ob_get_clean();

        // Assert Result
        $this->assertContains($msg, $log);
    }

    /**
     * testQuoteOrNot Test that quoteOrNot executes as expected.
     */
    public function testQuoteOrNot()
    {
        $value = 'quote this?';

        $this->mockDependency('sqlBuilderMock', 'quoteOrNot', [$value], true);

        $result = $this->testObject->quoteOrNot($value);

        $this->assertTrue($result);
    }

    /**
     * Test that convertTableNodeToQueries works as expected.
     */
    public function testConvertTableNodeToQueries()
    {
        $tableNodeMock = $this->getTableNodeMock();

        $this->mockDependency('sqlBuilderMock', 'convertTableNodeToQueries', [$tableNodeMock]);

        // Run.
        $result = $this->testObject->convertTableNodeToQueries($tableNodeMock);

        $this->assertTrue($result);
    }

    /**
     * Check if false is returned when rows are affected.
     */
    public function testThrowErrorsIfNoRowsAffectedNoException()
    {
        $statementMock = $this->getPdoStatementWithRows();

        $this->mockDependency('dbHelperMock', 'throwErrorIfNoRowsAffected', [$statementMock, false]);

        $result = $this->testObject->throwErrorIfNoRowsAffected($statementMock);

        $this->assertTrue($result);
    }

    /**
     * Check if the method returns false if no errors are found.
     */
    public function testThrowExceptionIfErrorsNoErrors()
    {
        $sqlStatementMock = $this->getPdoStatementWithRows();

        $this->mockDependency('dbHelperMock', 'throwExceptionIfErrors', [$sqlStatementMock]);

        $result = $this->testObject->throwExceptionIfErrors($sqlStatementMock);

        $this->assertTrue($result);
    }

    /**
     * Test that this method works as expected.
     */
    public function testMakeSQLSafe()
    {
        $string = 'databaseName.tableName.more';

        $result = $this->testObject->makeSQLSafe($string);

        $this->assertEquals('databaseName.tableName.more', $result);
    }

    /**
     * Test that this method works as expected.
     */
    public function testMakeSQLUnsafe()
    {
        $string = '`databaseName`.`tableName`.`more`';

        $result = $this->testObject->makeSQLUnsafe($string);

        $this->assertEquals('databaseName.tableName.more', $result);
    }

    /**
     * Test that the entity can be set using the setter.
     */
    public function testSetEntity()
    {
        $this->testObject->setEntity('abc');

        $this->assertEquals('dev_abc', $this->testObject->getEntity());
        $this->assertEquals('mydb', $this->testObject->getDatabaseName());
        $this->assertEquals('abc', $this->testObject->getTableName());

        $this->testObject->setEntity('random_abc');

        $this->assertEquals('dev_random_abc', $this->testObject->getEntity());
        $this->assertEquals('mydb', $this->testObject->getDatabaseName());
        $this->assertEquals('random_abc', $this->testObject->getTableName());

        $this->testObject->setEntity('abc.user');

        $this->assertEquals('dev_abc.user', $this->testObject->getEntity());
        $this->assertEquals('dev_abc', $this->testObject->getDatabaseName());
        $this->assertEquals('user', $this->testObject->getTableName());
    }

    /**
     * Test that convertTableNodeToSingleContextClause works as expected.
     */
    public function testConvertTableNodeToSingleContextClauseTableNode()
    {
        $tableNodeMokc = $this->getTableNodeMock();

        $this->mockDependency('sqlBuilderMock', 'convertTableNodeToSingleContextClause', [$tableNodeMokc]);

        $result = $this->testObject->convertTableNodeToSingleContextClause($tableNodeMokc);

        $this->assertTrue($result);
    }

    /**
     * Test that setCommandType works as expected.
     *
     * @expectedException Exception
     */
    public function testSetClauseType()
    {
        $this->testObject->setCommandType('random');
    }

    /**
     * Test that setCommandType works as expected.
     */
    public function testSetClauseTypeWithValidValues()
    {
        $clauseTypes = ['update', 'insert', 'select', 'delete'];

        foreach ($clauseTypes as $clauseType) {
            $this->testObject->setCommandType($clauseType);

            $this->assertEquals($clauseType, $this->testObject->getCommandType());
        }
    }

    /**
     * testGetKeyFromDuplicateError Test that getKeyFromDuplicateError executes as expected.
     */
    public function testGetKeyFromDuplicateErrorNoError()
    {
        // Prepare / Mock
        $error = ['', '', null];

        // Execute
        $result = $this->testObject->getKeyFromDuplicateError($error);

        // Assert Result
        $this->assertFalse($result);
    }

    /**
     * testGetKeyFromDuplicateError Test that getKeyFromDuplicateError executes as expected.
     */
    public function testGetKeyFromDuplicateErrorDuplicateError()
    {
        // Prepare / Mock
        $error = ['', '', 'error DETAIL: Key asdf=123 already exists.'];

        // Execute
        $result = $this->testObject->getKeyFromDuplicateError($error);

        // Assert Result
        $this->assertEquals('asdf', $result);
    }

    /**
     * testGetKeyFromDuplicateError Test that getKeyFromDuplicateError executes as expected.
     */
    public function testGetKeyFromDuplicateErrorOtherError()
    {
        // Prepare / Mock
        $error = ['', '', 'This is an unknown error.'];

        // Execute
        $result = $this->testObject->getKeyFromDuplicateError($error);

        // Assert Result
        $this->assertFalse($result);
    }

    /**
     * testGetTableColumns Test that getTableColumns executes as expected.
     */
    public function testGetTableColumns()
    {
        // Prepare / Mock
        $entity = 'user';

        $this->mockDependency('dbHelperMock', 'getRequiredTableColumns', ['user'], ['id' => 'int', 'name' => 'string', 'email' => 'string']);

        $sqlBuilderMock = $this->dependencies['sqlBuilderMock'];

        $sqlBuilderMock->expects($this->any())
            ->method('getColumns')
            ->willReturn(['name' => 'Abdul', 'role' => 'admin']);

        $sqlBuilderMock->expects($this->any())
            ->method('quoteOrNot')
            ->will($this->returnValueMap(array(
                array('Abdul', '"Abdul"'),
                array('admin', '"admin"')
            )));

        $sqlBuilderMock->expects($this->any())
            ->method('sampleData')
            ->will($this->returnValueMap(array(
                array('int', 234234),
                array('string', '"Abdul@random.com"')
            )));

        $expectedResult = array(
            0 => 'id, name, email, role',
            1 => '234234, "Abdul", "Abdul@random.com", "admin"'
        );

        // Execute
        $result = $this->testObject->getTableColumns($entity);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Get PDO statement with 1 row, used for testing.
     */
    private function getPdoStatementWithRows($rowCount = true, $fetchAll = false)
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
    private function mockDependency($dependency, $method, array $with = null, $return = true)
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
     * Get table node mock.
     */
    private function getTableNodeMock()
    {
        return $this->getMockBuilder(\Behat\Gherkin\Node\TableNode::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
