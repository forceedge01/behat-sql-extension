<?php

use Genesis\SQLExtension\Context\DatabaseProviders\mysql;
use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;

class mysqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var mysqlInterface The object to be tested.
     */
    private $testObject;

    /**
     * @var ReflectionClass The reflection class.
     */
    private $reflection;

    /**
     * @var array The test object dependencies.
     */
    private $dependencies = [];

    /**
     * Set up the testing object.
     */
    public function setUp()
    {
        $this->dependencies = [
            'executor' => $this->createMock(DBManagerInterface::class)
        ];

        $this->reflection = new ReflectionClass(mysql::class);
        $this->testObject = $this->reflection->newInstanceArgs($this->dependencies);
    }

    // public function testGetPrimaryKeyForTableReturnNothing()
    // {
    //     $database = 'my_app';
    //     $table = 'user';
    //     $expectedSql = '
    //         SELECT `COLUMN_NAME`
    //         FROM `information_schema`.`COLUMNS`
    //         WHERE (`TABLE_SCHEMA` = "my_app")
    //         AND (`TABLE_NAME` = "user")
    //         AND (`COLUMN_KEY` = "PRI")';

    //     $this->testObject->getConnection()->expects($this->once())
    //         ->method('prepare')
    //         ->with($expectedSql)
    //         ->will($this->returnValue($this->getPdoStatementWithRows(0, [])));

    //     $result = $this->testObject->getPrimaryKeyForTable($database, null, $table);

    //     $this->assertFalse($result);
    // }

    // public function testGetPrimaryKeyForTableReturnSomeColumn()
    // {
    //     $database = 'my_app';
    //     $table = 'user';
    //     $expectedSql = '
    //         SELECT `COLUMN_NAME`
    //         FROM `information_schema`.`COLUMNS`
    //         WHERE (`TABLE_SCHEMA` = "my_app")
    //         AND (`TABLE_NAME` = "user")
    //         AND (`COLUMN_KEY` = "PRI")';

    //     $this->testObject->getConnection()->expects($this->once())
    //         ->method('prepare')
    //         ->with($expectedSql)
    //         ->will($this->returnValue($this->getPdoStatementWithRows(1, [[0 => 'coid']])));

    //     $result = $this->testObject->getPrimaryKeyForTable($database, null, $table);

    //     $this->assertEquals('coid', $result);
    // }

    // public function testGetPrimaryKeyForTableReturnSomething()
    // {
    //     $database = 'my_app';
    //     $table = 'user';
    //     $expectedSql = '
    //         SELECT `COLUMN_NAME`
    //         FROM `information_schema`.`COLUMNS`
    //         WHERE (`TABLE_SCHEMA` = "my_app")
    //         AND (`TABLE_NAME` = "user")
    //         AND (`COLUMN_KEY` = "PRI")';

    //     $this->testObject->getConnection()->expects($this->once())
    //         ->method('prepare')
    //         ->with($expectedSql)
    //         ->will($this->returnValue($this->getPdoStatementWithRows(true, [['primary_key_id']])));

    //     $result = $this->testObject->getPrimaryKeyForTable($database, null, $table);

    //     $this->assertEquals('primary_key_id', $result);
    // }

    // public function testGetRequiredTableColumnsNoResult()
    // {
    //     $table = 'user';
    //     $expectedSql = "
    //         SELECT 
    //             `column_name`, `data_type` 
    //         FROM 
    //             information_schema.columns 
    //         WHERE 
    //             is_nullable = 'NO'
    //         AND 
    //             table_name = 'user'
    //         AND 
    //             table_schema = 'myschema';";

    //     $this->testObject->getConnection()->expects($this->once())
    //         ->method('prepare')
    //         ->with($expectedSql)
    //         ->will($this->returnValue($this->getPdoStatementWithRows(0, [])));

    //     $result = $this->testObject->getRequiredTableColumns(null, null, $table);

    //     $this->assertTrue([] === $result);
    // }

    // public function testGetRequiredTableColumnsNoDBSchemaSet()
    // {
    //     $dbSchema = 'awsomeschema';
    //     $table = 'awsometable';
    //     $expectedSql = "
    //         SELECT 
    //             `column_name`, `data_type` 
    //         FROM 
    //             information_schema.columns 
    //         WHERE 
    //             is_nullable = 'NO'
    //         AND 
    //             table_name = 'awsometable'
    //         AND 
    //             table_schema = 'awsomeschema';";

    //     // Override schema value.
    //     $property = $this->accessProperty('params');
    //     $value = $property->getValue($this->testObject);
    //     $property->setValue(
    //         $this->testObject,
    //         array_merge($value, ['DBSCHEMA' => null])
    //     );

    //     $this->testObject->getConnection()->expects($this->once())
    //         ->method('prepare')
    //         ->with($expectedSql)
    //         ->will($this->returnValue($this->getPdoStatementWithRows(0, [])));

    //     $result = $this->testObject->getRequiredTableColumns(null, $dbSchema, $table);

    //     $this->assertTrue([] === $result);
    // }

    // public function testGetRequiredTableColumnsResults()
    // {
    //     $dbSchema = 'myschema';
    //     $table = 'user';
    //     $expectedSql = "
    //         SELECT 
    //             `column_name`, `data_type` 
    //         FROM 
    //             information_schema.columns 
    //         WHERE 
    //             is_nullable = 'NO'
    //         AND 
    //             table_name = 'user'
    //         AND 
    //             table_schema = 'myschema';";

    //     $this->testObject->getConnection()->expects($this->once())
    //         ->method('prepare')
    //         ->with($expectedSql)
    //         ->will($this->returnValue($this->getPdoStatementWithRows(2, [
    //                 ['column_name' => 'id', 'data_type' => 'int'],
    //                 ['column_name' => 'name', 'data_type' => 'string'
    //                 ]
    //             ])));

    //     $result = $this->testObject->getRequiredTableColumns(null, $dbSchema, $table);

    //     $this->assertTrue(['name' => 'string'] === $result);
    // }

    // public function testGetRequiredTableColumnsNoSchemaInparams()
    // {
    //     $dbSchema = 'myapp';
    //     $table = 'user';
    //     $expectedSql = "
    //         SELECT 
    //             `column_name`, `data_type` 
    //         FROM 
    //             information_schema.columns 
    //         WHERE 
    //             is_nullable = 'NO'
    //         AND 
    //             table_name = 'user'
    //         AND 
    //             table_schema = 'myapp';";

    //     // Override the preset schema to be null as params take precedence over defined constants.
    //     // This should make then make use of the 'myapp' table schema.
    //     $dbParams = ['schema' => ''];
    //     $this->accessMethod('setDBParams')->invokeArgs(
    //         $this->testObject,
    //         [$dbParams]
    //     );

    //     $this->testObject->getConnection()->expects($this->once())
    //         ->method('prepare')
    //         ->with($expectedSql)
    //         ->will($this->returnValue($this->getPdoStatementWithRows(2, [
    //                 ['column_name' => 'id', 'data_type' => 'int'],
    //                 ['column_name' => 'name', 'data_type' => 'string'
    //                 ]
    //             ])));

    //     $result = $this->testObject->getRequiredTableColumns(null, $dbSchema, $table);

    //     $this->assertTrue(['name' => 'string'] === $result);
    // }
}
