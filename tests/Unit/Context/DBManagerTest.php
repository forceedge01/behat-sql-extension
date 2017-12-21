<?php

namespace Genesis\SQLExtension\Tests\Unit\Context;

use Genesis\SQLExtension\Context\DBManager;
use Genesis\SQLExtension\Context\PDO;
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
     * Setup unit testing.
     */
    public function setup()
    {
        $params = [];

        $connection = $this->getMockBuilder(PDO::class)
            ->getMock();

        $this->testObject = new DBManager($params);
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

    public function testGetPrimaryKeyForTableReturnNothing()
    {
        $database = 'my_app';
        $table = 'user';
        $expectedSql = '
            SELECT `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE (`TABLE_SCHEMA` = "my_app")
            AND (`TABLE_NAME` = "user")
            AND (`COLUMN_KEY` = "PRI")';

        $this->testObject->getConnection()->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(0, [])));

        $result = $this->testObject->getPrimaryKeyForTable($database, $table);

        $this->assertFalse($result);
    }

    public function testGetPrimaryKeyForTableReturnSomeColumn()
    {
        $database = 'my_app';
        $table = 'user';
        $expectedSql = '
            SELECT `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE (`TABLE_SCHEMA` = "my_app")
            AND (`TABLE_NAME` = "user")
            AND (`COLUMN_KEY` = "PRI")';

        $this->testObject->getConnection()->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(1, [[0 => 'coid']])));

        $result = $this->testObject->getPrimaryKeyForTable($database, $table);

        $this->assertEquals('coid', $result);
    }

    public function testGetPrimaryKeyForTableReturnSomething()
    {
        $database = 'my_app';
        $table = 'user';
        $expectedSql = '
            SELECT `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE (`TABLE_SCHEMA` = "my_app")
            AND (`TABLE_NAME` = "user")
            AND (`COLUMN_KEY` = "PRI")';

        $this->testObject->getConnection()->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(true, [['primary_key_id']])));

        $result = $this->testObject->getPrimaryKeyForTable($database, $table);

        $this->assertEquals('primary_key_id', $result);
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

        $this->assertInstanceOf(get_class($pdoStatement), $result);
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

    public function testGetRequiredTableColumnsNoResult()
    {
        $table = 'user';
        $expectedSql = "
            SELECT 
                `column_name`, `data_type` 
            FROM 
                information_schema.columns 
            WHERE 
                is_nullable = 'NO'
            AND 
                table_name = 'user'
            AND 
                table_schema = 'myschema';";

        $this->testObject->getConnection()->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(0, [])));

        $result = $this->testObject->getRequiredTableColumns($table);

        $this->assertTrue([] === $result);
    }

    public function testGetRequiredTableColumnsNoDBSchemaSet()
    {
        $table = 'awsomeschema.awsometable';
        $expectedSql = "
            SELECT 
                `column_name`, `data_type` 
            FROM 
                information_schema.columns 
            WHERE 
                is_nullable = 'NO'
            AND 
                table_name = 'awsometable'
            AND 
                table_schema = 'awsomeschema';";

        // Override schema value.
        $property = $this->accessProperty('params');
        $value = $property->getValue($this->testObject);
        $property->setValue(
            $this->testObject,
            array_merge($value, ['DBSCHEMA' => null])
        );

        $this->testObject->getConnection()->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(0, [])));

        $result = $this->testObject->getRequiredTableColumns($table);

        $this->assertTrue([] === $result);
    }

    public function testGetRequiredTableColumnsResults()
    {
        $table = 'user';
        $expectedSql = "
            SELECT 
                `column_name`, `data_type` 
            FROM 
                information_schema.columns 
            WHERE 
                is_nullable = 'NO'
            AND 
                table_name = 'user'
            AND 
                table_schema = 'myschema';";

        $this->testObject->getConnection()->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(2, [
                    ['column_name' => 'id', 'data_type' => 'int'],
                    ['column_name' => 'name', 'data_type' => 'string'
                    ]
                ])));

        $result = $this->testObject->getRequiredTableColumns($table);

        $this->assertTrue(['name' => 'string'] === $result);
    }

    public function testGetRequiredTableColumnsNoSchemaInparams()
    {
        $table = 'myapp.user';
        $expectedSql = "
            SELECT 
                `column_name`, `data_type` 
            FROM 
                information_schema.columns 
            WHERE 
                is_nullable = 'NO'
            AND 
                table_name = 'user'
            AND 
                table_schema = 'myapp';";

        // Override the preset schema to be null as params take precedence over defined constants.
        // This should make then make use of the 'myapp' table schema.
        $dbParams = ['schema' => ''];
        $this->accessMethod('setDBParams')->invokeArgs(
            $this->testObject,
            [$dbParams]
        );

        $this->testObject->getConnection()->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(2, [
                    ['column_name' => 'id', 'data_type' => 'int'],
                    ['column_name' => 'name', 'data_type' => 'string'
                    ]
                ])));

        $result = $this->testObject->getRequiredTableColumns($table);

        $this->assertTrue(['name' => 'string'] === $result);
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
            ->method('rowCount')
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
            ->method('rowCount')
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
            ->method('rowCount')
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

        // Execute
        $result = $this
            ->accessMethod('getConnectionDetails')
            ->invoke($this->testObject);

        $expectedConnectionString = 'banana:dbname=hot;host=cup';

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

        // Execute
        $result = $this
            ->accessMethod('getConnectionDetails')
            ->invoke($this->testObject);

        $expectedConnectionString = 'banana:dbname=hot;host=cup;port=3380';

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
