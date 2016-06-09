<?php

namespace Genesis\SQLExtension\Tests\Context;

use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;
use Genesis\SQLExtension\Context\Interfaces\KeyStoreInterface;
use Genesis\SQLExtension\Context\Interfaces\SQLBuilderInterface;
use Genesis\SQLExtension\Context\SQLHandler;
use PHPUnit_Framework_TestCase;

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
     * Tests that constructSQLClause executes as expected with LIKE values.
     */
    public function testConstructSQLClauseLikeValues()
    {
        // Execute
        $result = $this->testObject->constructSQLClause($glue, $columns);

        $this->assertTrue($result);
    }

    /**
     * testFilterAndConvertToArray Test that filterAndConvertToArray executes as expected.
     */
    public function testFilterAndConvertToArray()
    {
        // Execute
        $result = $this->testObject->filterAndConvertToArray($columns);

        $this->assertTrue($result);
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
        $value = '';

        $result = $this->testObject->quoteOrNot($value);

        $this->assertTrue($result);
    }

    /**
     * Test that convertTableNodeToQueries works as expected.
     */
    public function testConvertTableNodeToQueries()
    {
        // Run.
        $result = $this->testObject->convertTableNodeToQueries($node);

        $this->assertTrue($result);
    }

    /**
     * Check if false is returned when rows are affected.
     */
    public function testThrowErrorsIfNoRowsAffectedNoException()
    {
        $result = $this->testObject->throwErrorIfNoRowsAffected($sqlStatementMock);

        $this->assertTrue($result);
    }

    /**
     * Check if the method returns false if no errors are found.
     */
    public function testThrowExceptionIfErrorsNoErrors()
    {
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
        $result = $this->testObject->convertTableNodeToSingleContextClause($node);

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
        //nm

        // Execute
        $result = $this->testObject->getTableColumns($entity);

        // Assert Result
        //assert
        $this->markTestIncomplete('This test has not been implemented yet.');
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
    private function mockDependency($dependency, $method, array $with, $return = true)
    {
        $mock = $this->dependencies[$dependency]->expects($this->once())
            ->method($method);

        $mock = call_user_func_array(array($mock, 'with'), $with);
        $mock->willReturn($return);
    }
}
