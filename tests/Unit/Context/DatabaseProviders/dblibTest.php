<?php

use Genesis\SQLExtension\Context\DatabaseProviders\dblib;
use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;
use Genesis\SQLExtension\Tests\TestHelper;

class dblibTest extends TestHelper
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

        $this->reflection = new ReflectionClass(dblib::class);
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
        self::assertEquals('dblib:host=myhost:55454;dbname=testing', $result);
    }

    /**
     * testGetPdoDnsStirng Test that getPdoDnsString executes as expected.
     */
    public function testGetPdoNoPort()
    {
        // Execute
        $result = $this->testObject->getPdoDnsString($dbname = 'testing', $host = 'myhost');

        // Assert Result
        self::assertEquals('dblib:host=myhost;dbname=testing', $result);
    }

    /**
     * testGetLeftDelimiterForReservedWord Test that getLeftDelimiterForReservedWord executes as expected.
     */
    public function testGetLeftDelimiterForReservedWord()
    {
        // Execute
        $result = $this->testObject->getLeftDelimiterForReservedWord();

        // Assert Result
        self::assertEquals('[', $result);
    }

    /**
     * testGetRightDelimiterForReservedWord Test that getRightDelimiterForReservedWord executes as expected.
     */
    public function testGetRightDelimiterForReservedWord()
    {
        // Execute
        $result = $this->testObject->getRightDelimiterForReservedWord();

        // Assert Result
        self::assertEquals(']', $result);
    }

    /**
     * Test that the primary key if null returns false.
     */
    public function testGetPrimaryKeyForTableReturnNothing()
    {
        $table = 'user';
        $expectedSql = "
            SELECT KU.table_name as TABLENAME,column_name as PRIMARYKEYCOLUMN
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS TC
            INNER JOIN
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KU
                      ON TC.CONSTRAINT_TYPE = 'PRIMARY KEY' AND
                         TC.CONSTRAINT_NAME = KU.CONSTRAINT_NAME AND 
                         KU.table_name='$table' ;";

        $expectedSql2 = "
                SELECT
                    TABLE_NAME as TABLENAME, COLUMN_NAME as PRIMARYKEYCOLUMN
                FROM
                    information_schema.columns TC
                WHERE
                    TABLE_NAME = '$table'
                AND
                    IS_NULLABLE = 'NO'
                AND
                    COLUMNPROPERTY(object_id(TABLE_SCHEMA+'.'+TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1
            ";

        $this->testObject->getExecutor()->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive([$expectedSql], [$expectedSql2])
            ->willReturnOnConsecutiveCalls(
                $this->getPdoStatementWithRows(0, []),
                $this->getPdoStatementWithRows(1, [[
                    'TABLENAME' => $table,
                    'PRIMARYKEYCOLUMN' => 'auto_id'
                ]])
            );

        $result = $this->testObject->getPrimaryKeyForTable(null, null, $table);

        $this->assertEquals('auto_id', $result);
    }

    /**
     * Test that the primary key if null returns false.
     */
    public function testGetPrimaryKeyForTableWithSchema()
    {
        $table = 'user';
        $schema = 'mySchema';
        $expectedSql = "
            SELECT KU.table_name as TABLENAME,column_name as PRIMARYKEYCOLUMN
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS TC
            INNER JOIN
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KU
                      ON TC.CONSTRAINT_TYPE = 'PRIMARY KEY' AND
                         TC.CONSTRAINT_NAME = KU.CONSTRAINT_NAME AND 
                         KU.table_name='$table'  AND TC.TABLE_SCHEMA = 'mySchema';";

        $expectedSql2 = "
                SELECT
                    TABLE_NAME as TABLENAME, COLUMN_NAME as PRIMARYKEYCOLUMN
                FROM
                    information_schema.columns TC
                WHERE
                    TABLE_NAME = '$table'
                AND
                    IS_NULLABLE = 'NO'
                AND
                    COLUMNPROPERTY(object_id(TABLE_SCHEMA+'.'+TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1
            ";

        $this->testObject->getExecutor()->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive([$expectedSql], [$expectedSql2])
            ->willReturnOnConsecutiveCalls(
                $this->getPdoStatementWithRows(0, []),
                $this->getPdoStatementWithRows(1, [])
            );

        $result = $this->testObject->getPrimaryKeyForTable(null, $schema, $table);

        $this->assertFalse($result);
    }

    /**
     * Test that the primary key if null returns false.
     */
    public function testGetPrimaryKeyForTableWithSchemaAndDatabase()
    {
        $database = 'my_app';
        $table = 'user';
        $schema = 'mySchema';
        $expectedSql = "
            SELECT KU.table_name as TABLENAME,column_name as PRIMARYKEYCOLUMN
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS TC
            INNER JOIN
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KU
                      ON TC.CONSTRAINT_TYPE = 'PRIMARY KEY' AND
                         TC.CONSTRAINT_NAME = KU.CONSTRAINT_NAME AND 
                         KU.table_name='$table'  AND TC.TABLE_CATALOG = 'my_app' AND TC.TABLE_SCHEMA = 'mySchema';";

        $expectedSql2 = "
                SELECT
                    TABLE_NAME as TABLENAME, COLUMN_NAME as PRIMARYKEYCOLUMN
                FROM
                    information_schema.columns TC
                WHERE
                    TABLE_NAME = '$table'
                AND
                    IS_NULLABLE = 'NO'
                AND
                    COLUMNPROPERTY(object_id(TABLE_SCHEMA+'.'+TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1
            ";

        $this->testObject->getExecutor()->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive([$expectedSql], [$expectedSql2])
            ->willReturnOnConsecutiveCalls(
                $this->getPdoStatementWithRows(0, []),
                $this->getPdoStatementWithRows(0, [])
            );

        $result = $this->testObject->getPrimaryKeyForTable($database, $schema, $table);

        $this->assertFalse($result);
    }

    /**
     * Test that the primary key if found returns what was found.
     */
    public function testGetPrimaryKeyForTableReturnSomeColumn()
    {
        $database = 'my_app';
        $table = 'user';
        $expectedSql = "
            SELECT KU.table_name as TABLENAME,column_name as PRIMARYKEYCOLUMN
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS TC
            INNER JOIN
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KU
                      ON TC.CONSTRAINT_TYPE = 'PRIMARY KEY' AND
                         TC.CONSTRAINT_NAME = KU.CONSTRAINT_NAME AND 
                         KU.table_name='$table'  AND TC.TABLE_CATALOG = 'my_app';";

        $this->testObject->getExecutor()->expects($this->once())
            ->method('execute')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(1, [[
                'TABLENAME' => $table,
                'PRIMARYKEYCOLUMN' => 'coid'
            ]])));

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
                COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
            FROM
                information_schema.columns TC
            WHERE
                TABLE_NAME = '$table'
            AND
                IS_NULLABLE = 'NO'
            AND
                COLUMNPROPERTY(object_id(TABLE_SCHEMA+'.'+TABLE_NAME), COLUMN_NAME, 'IsIdentity') != 1
            AND
                Column_DEFAULT IS null  AND TABLE_CATALOG = 'mydb' AND TABLE_SCHEMA = 'myschema';";

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
                COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
            FROM
                information_schema.columns TC
            WHERE
                TABLE_NAME = '$table'
            AND
                IS_NULLABLE = 'NO'
            AND
                COLUMNPROPERTY(object_id(TABLE_SCHEMA+'.'+TABLE_NAME), COLUMN_NAME, 'IsIdentity') != 1
            AND
                Column_DEFAULT IS null  AND TABLE_SCHEMA = 'myschema';";

        $this->testObject->getExecutor()->expects($this->once())
            ->method('execute')
            ->with($expectedSql)
            ->will($this->returnValue($this->getPdoStatementWithRows(2, [
                    ['COLUMN_NAME' => 'id', 'DATA_TYPE' => 'int', 'CHARACTER_MAXIMUM_LENGTH' => 11],
                    ['COLUMN_NAME' => 'name', 'DATA_TYPE' => 'string', 'CHARACTER_MAXIMUM_LENGTH' => 5000]
            ])));

        $result = $this->testObject->getRequiredTableColumns(null, $dbSchema, $table);
        $expectedResult = [
            'id' => ['type' => 'int', 'length' => 11],
            'name' => ['type' => 'string', 'length' => 5000]
        ];

        $this->assertEquals($expectedResult, $result);
    }
}
