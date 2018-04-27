<?php

namespace Genesis\SQLExtension\Tests\Unit\Context;

use Genesis\SQLExtension\Context\DBManager;
use Genesis\SQLExtension\Context\DatabaseProviders\mysql;
use Genesis\SQLExtension\Context\Interfaces\DatabaseProviderFactoryInterface;
use Genesis\SQLExtension\Tests\PDO;
use Genesis\SQLExtension\Tests\TestHelper;

/**
 * @group DBManager
 * @group unit
 */
class DBManagerTest extends TestHelper
{
    /**
     * @var object The object to be tested.
     */
    protected $testObject;

    /**
     * @var array
     */
    protected $dependencies;

    /**
     * Setup unit testing.
     */
    public function setup()
    {
        $params = [
            'engine' => 'mysql'
        ];

        $connection = $this->getMockBuilder(PDO::class)
            ->getMock();

        $this->dependencies['databaseProvider'] = $this->createMock(mysql::class);
        $providerFactoryMock = $this->createMock(DatabaseProviderFactoryInterface::class);
        $providerFactoryMock->expects($this->any())
            ->method('getProvider')
            ->willReturn($this->dependencies['databaseProvider']);

        $this->testObject = new DBManager(
            $providerFactoryMock,
            $params
        );
        $this->testObject->setConnection($connection);
    }

    /**
     * Test that the get params call works as expected.
     */
    public function testGetParams()
    {
        $result = $this->testObject->getParams();

        $this->assertInternalType('array', $result);
    }

    public function testGetSetConnectionAlreadySet()
    {
        $value = 'abcd';

        $result = $this->testObject->setConnection($value);

        $this->assertInstanceOf(DBManager::class, $result);

        $result = $this->testObject->getConnection();

        $this->assertEquals($value, $result);
    }

    public function testGetPrimaryKeyForTable()
    {
        $database = 'my_app';
        $schema = 'my_schema';
        $table = 'user';

        $this->dependencies['databaseProvider']->expects($this->any())
            ->method('getPrimaryKeyForTable')
            ->with($database, $schema, $table)
            ->willReturn('my_id');

        $result = $this->testObject->getPrimaryKeyForTable($database, $schema, $table);

        $this->assertEquals($result, 'my_id');
    }

    public function testExecute()
    {
        $pdoStatement = $this->getPdoStatementWithRows(true, [['primary_key_id']]);
        $expectedSql = 'this is the query';

        $this->testObject->getConnection()->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->will($this->returnValue($pdoStatement));

        $result = $this->testObject->execute($expectedSql);

        $this->assertEquals($pdoStatement, $result);
    }

    public function testHasFetchedRows()
    {
        $statement = $this->getPdoStatementWithRows(0, []);

        $result = $this->testObject->hasFetchedRows($statement);

        $this->assertEquals(0, $result);

        $statement = $this->getPdoStatementWithRows(1, [['id']]);

        $result = $this->testObject->hasFetchedRows($statement);

        $this->assertEquals(1, $result);
    }

    public function testGetRequiredTableColumns()
    {
        $database = 'mydb';
        $schema = 'myschema';
        $table = 'user';

        $expectedResult = [
            'UserId' => [
                'type' => 'int',
                'length' => null
            ]
        ];

        $this->dependencies['databaseProvider']->expects($this->any())
            ->method('getRequiredTableColumns')
            ->with($database, $schema, $table)
            ->willReturn($expectedResult);

        $result = $this->testObject->getRequiredTableColumns($database, $schema, $table);

        $this->assertTrue($expectedResult === $result);
    }

    public function testGetLastInsertId()
    {
        $table = 'user';

        $this->testObject->getConnection()->expects($this->once())
            ->method('lastInsertId')
            ->with('user_id_seq')
            ->will($this->returnValue(5));

        $result = $this->testObject->getLastInsertId($table);

        $this->assertEquals(5, $result);
    }

    /**
     * @expectedException \Genesis\SQLExtension\Context\Exceptions\NoRowsAffectedException
     */
    public function testThrowErrorsIfNoRowsAffectedNoRowsAffected()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM, PDO::FETCH_ORI_FIRST)
            ->will($this->returnValue(false));

        $this->testObject->throwErrorIfNoRowsAffected($sqlStatementMock);
    }

    /**
     * Check if the error is returned when one is found and is a duplicate error.
     */
    public function testThrowErrorsIfNoRowsAffectedDuplicateError()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM, PDO::FETCH_ORI_FIRST)
            ->will($this->returnValue(false));

        $sqlStatementMock->expects($this->exactly(2))
            ->method('errorInfo')
            ->willReturn([0 => 'Duplicate error key=asdf']);

        $result = $this->testObject->throwErrorIfNoRowsAffected(
            $sqlStatementMock,
            true
        );

        $this->assertContains('Duplicate', $result[0]);
    }

    /**
     * Check if false is returned when rows are affected.
     */
    public function testThrowErrorsIfNoRowsAffectedNoException()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM, PDO::FETCH_ORI_FIRST)
            ->will($this->returnValue(true));

        $result = $this->testObject->throwErrorIfNoRowsAffected($sqlStatementMock);

        $this->assertFalse($result);
    }

    /**
     * @expectedException \Exception
     */
    public function testThrowExceptionIfErrorsWithErrors()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlStatementMock->expects($this->once())
            ->method('errorCode')
            ->willReturn(234);

        $this->testObject->throwExceptionIfErrors($sqlStatementMock);
    }

    /**
     * Check if the method returns false if no errors are found.
     */
    public function testThrowExceptionIfErrorsNoErrors()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        // This is the error code returned by mysql if no errors have occurred.
        $sqlStatementMock->expects($this->once())
            ->method('errorCode')
            ->willReturn('00000');

        $result = $this->testObject->throwExceptionIfErrors($sqlStatementMock);

        $this->assertFalse($result);
    }

    /**
     * testGetConnectionDetails Test that getConnectionDetails executes as expected.
     */
    public function testGetConnectionDetailsWithoutPort()
    {
        // Prepare / Mock
        $paramsValue = [
            'DBENGINE' => 'banana',
            'DBNAME' => 'hot',
            'DBHOST' => 'cup',
            'DBUSER' => 'of',
            'DBPASSWORD' => 'tea',
            'DBPORT' => '',
            'DBOPTIONS' => ['charset_encoding' => 'mb8']
        ];

        $property = $this
            ->accessProperty('params')
            ->setValue(
                $this->testObject,
                $paramsValue
            );

        $expectedConnectionString = 'banana:dbname=hot;host=cup';

        $this->dependencies['databaseProvider']->expects($this->any())
            ->method('getPdoDnsString')
            ->with('hot', 'cup', '')
            ->willReturn($expectedConnectionString);

        // Execute
        $result = $this
            ->accessMethod('getConnectionDetails')
            ->invoke($this->testObject);

        // Assert Result
        $this->assertInternalType('array', $result);
        $this->assertEquals($expectedConnectionString, $result[0]);
        $this->assertEquals($paramsValue['DBUSER'], $result[1]);
        $this->assertEquals($paramsValue['DBPASSWORD'], $result[2]);
        $this->assertEquals($paramsValue['DBOPTIONS'], $result[3]);
    }

    /**
     * testGetConnectionDetails Test that getConnectionDetails executes as expected.
     */
    public function testGetConnectionDetails()
    {
        // Prepare / Mock
        $paramsValue = [
            'DBENGINE' => 'banana',
            'DBNAME' => 'hot',
            'DBHOST' => 'cup',
            'DBUSER' => 'of',
            'DBPASSWORD' => 'tea',
            'DBPORT' => 3380,
            'DBOPTIONS' => []
        ];

        $property = $this
            ->accessProperty('params')
            ->setValue(
                $this->testObject,
                $paramsValue
            );

        $expectedConnectionString = 'banana:dbname=hot;host=cup;port=3380';

        $this->dependencies['databaseProvider']->expects($this->any())
            ->method('getPdoDnsString')
            ->with('hot', 'cup', 3380)
            ->willReturn($expectedConnectionString);

        // Execute
        $result = $this
            ->accessMethod('getConnectionDetails')
            ->invoke($this->testObject);

        // Assert Result
        $this->assertInternalType('array', $result);
        $this->assertEquals($expectedConnectionString, $result[0]);
        $this->assertEquals($paramsValue['DBUSER'], $result[1]);
        $this->assertEquals($paramsValue['DBPASSWORD'], $result[2]);
    }

    /**
     * testGetFirstValueFromStatement Test that getFirstValueFromStatement executes as expected.
     */
    public function testGetFirstValueFromStatementNoRows()
    {
        // Prepare / Mock
        $statement = $this->getPdoStatementWithRows(false, []);

        // Execute
        $result = $this->testObject->getFirstValueFromStatement($statement);

        // Assert Result
        $this->assertNull($result);
    }

    /**
     * testGetFirstValueFromStatement Test that getFirstValueFromStatement executes as expected.
     */
    public function testGetFirstValueFromStatementWithRows()
    {
        // Prepare / Mock
        $statement = $this->getPdoStatementWithRows(true, [['id' => 123], ['id' => 7726]]);

        // Execute
        $result = $this->testObject->getFirstValueFromStatement($statement);

        // Assert Result
        $this->assertEquals(['id' => 123], $result);
    }
}
