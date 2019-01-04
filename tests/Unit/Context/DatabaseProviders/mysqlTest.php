<?php

use Genesis\SQLExtension\Context\DatabaseProviders\mysql;
use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;
use Genesis\SQLExtension\Tests\TestHelper;

class mysqlTest extends TestHelper
{
    /**
     * @var mysqlInterface The object to be tested.
     */
    protected $testObject;

    /**
     * @var ReflectionClass The reflection class.
     */
    private $reflection;

    /**
     * @var array The test object dependencies.
     */
    protected $dependencies = [];

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

    /**
     * testGetPdoDnsStirng Test that getPdoDnsString executes as expected.
     */
    public function testGetPdo()
    {
        // Execute
        $result = $this->testObject->getPdoDnsString($dbname = 'testing', $host = 'myhost', $port = 55454);
    
        // Assert Result
        self::assertEquals($result, 'mysql:dbname=testing;host=myhost;port=55454');
    }

    /**
     * testGetPdoDnsStirng Test that getPdoDnsString executes as expected.
     */
    public function testGetPdoNoPort()
    {
        // Execute
        $result = $this->testObject->getPdoDnsString($dbname = 'testing', $host = 'myhost');
    
        // Assert Result
        self::assertEquals($result, 'mysql:dbname=testing;host=myhost;port=3306');
    }

    /**
     * testGetLeftDelimiterForReservedWord Test that getLeftDelimiterForReservedWord executes as expected.
     */
    public function testGetLeftDelimiterForReservedWord()
    {
        // Execute
        $result = $this->testObject->getLeftDelimiterForReservedWord();
    
        // Assert Result
        self::assertEquals('`', $result);
    }

    /**
     * testGetRightDelimiterForReservedWord Test that getRightDelimiterForReservedWord executes as expected.
     */
    public function testGetRightDelimiterForReservedWord()
    {
        // Execute
        $result = $this->testObject->getRightDelimiterForReservedWord();
    
        // Assert Result
        self::assertEquals('`', $result);
    }

    /**
     * Test that the primary key if null returns false.
     */
    public function testGetPrimaryKeyForTableReturnNothing()
    {
        $database = 'my_app';
        $table = 'user';
        $expectedSql = '
            SELECT `COLUMN_NAME` AS `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE (`TABLE_SCHEMA` = "my_app")
            AND (`TABLE_NAME` = "user")
            AND (`COLUMN_KEY` = "PRI")';

        $this->testObject->getExecutor()->expects($this->once())
            ->method('execute')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(0, [])));

        $result = $this->testObject->getPrimaryKeyForTable($database, null, $table);

        $this->assertFalse($result);
    }

    /**
     * Test that the primary key if found returns what was found.
     */
    public function testGetPrimaryKeyForTableReturnSomeColumn()
    {
        $database = 'my_app';
        $table = 'user';
        $expectedSql = '
            SELECT `COLUMN_NAME` AS `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE (`TABLE_SCHEMA` = "my_app")
            AND (`TABLE_NAME` = "user")
            AND (`COLUMN_KEY` = "PRI")';

        $this->testObject->getExecutor()->expects($this->once())
            ->method('execute')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(1, [[0 => 'coid']])));

        $result = $this->testObject->getPrimaryKeyForTable($database, null, $table);

        $this->assertEquals('coid', $result);
    }

    /**
     * Test when no required columns are found an empty array is returned.
     */
    public function testGetRequiredTableColumnsNoResult()
    {
        $table = 'user';
        $schema = 'myschema';
        $database = 'mydb';
        $expectedSql = "
            SELECT 
                `column_name` AS `column_name`,
                `data_type` AS `data_type`,
                `character_maximum_length` AS `data_length`
            FROM 
                information_schema.columns 
            WHERE 
                is_nullable = 'NO'
            AND 
                table_name = 'user'
            AND 
                table_schema = 'myschema';";

        $this->testObject->getExecutor()->expects($this->once())
            ->method('execute')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(0, [])));

        $result = $this->testObject->getRequiredTableColumns($database, $schema, $table);

        $this->assertTrue([] === $result);
    }

    /**
     * Test when no required columns are found they are returned in the right format.
     */
    public function testGetRequiredTableColumnsResults()
    {
        $dbSchema = 'myschema';
        $table = 'user';
        $expectedSql = "
            SELECT 
                `column_name` AS `column_name`,
                `data_type` AS `data_type`,
                `character_maximum_length` AS `data_length`
            FROM 
                information_schema.columns 
            WHERE 
                is_nullable = 'NO'
            AND 
                table_name = 'user'
            AND 
                table_schema = 'myschema';";

        $this->testObject->getExecutor()->expects($this->once())
            ->method('execute')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(2, [
                    ['column_name' => 'id', 'data_type' => 'int', 'data_length' => null],
                    ['column_name' => 'name', 'data_type' => 'string', 'data_length' => '500'],
            ])));

        $result = $this->testObject->getRequiredTableColumns(null, $dbSchema, $table);
        $expectedResult = [
            'id' => ['type' => 'int', 'length' => 5000],
            'name' => ['type' => 'string', 'length' => 500]
        ];

        $this->assertEquals($expectedResult, $result);
    }
}
