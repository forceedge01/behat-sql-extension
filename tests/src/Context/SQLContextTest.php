<?php

namespace Genesis\SQLExtension\Tests\Context;

use Behat\Gherkin\Node\TableNode;
use Genesis\SQLExtension\Context\SQLContext;
use PHPUnit_Framework_TestCase;

class SQLContextTest extends PHPUnit_Framework_TestCase
{
    private $testObject;

    const CONNECTION_STRING = 'BEHAT_ENV_PARAMS=DBENGINE:mysql;DBSCHEMA:;DBNAME:abc;DBHOST:localhost;DBUSER:root;DBPASSWORD:toor;DBPREFIX:';

    public function __construct()
    {
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

        $this->testObject = new SQLContext();

        putenv(self::CONNECTION_STRING);

        $pdoConnectionMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->setMethods(array('prepare', 'lastInsertId', 'execute'))
            ->getMock();

        $pdoConnectionMock->expects($this->any())
            ->method('lastInsertId')
            ->willReturn(5);

        $this->testObject->setConnection($pdoConnectionMock);
    }

    public function testIHaveWhere()
    {
        $entity = 'database.unique';
        $node = new TableNode([
            [
                'email',
                'name'
            ], [
                'its.inevitable@hotmail.com',
                'Abdul'
            ], [
                'forceedge01@gmail.com',
                'Qureshi'
            ]
        ]);

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [['id' => 234324]]));

        $sqls = $this->testObject->iHaveWhere($entity, $node);

        $this->assertCount(2, $sqls);
    }

    public function testIHave()
    {
        $node = new TableNode([
            [
                'table',
                'values'
            ], [
                'table1',
                'id:34234, name:abdul'
            ], [
                'table2',
                'id:34234, name:Jenkins'
            ]
        ]);

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [['id' => 234324]]));

        $sqls = $this->testObject->iHave($node);

        $this->assertCount(2, $sqls);
    }

    public function testIDontHaveWhere()
    {
        $entity = 'database.unique';
        $node = new TableNode([
            [
                'email',
                'name'
            ], [
                'its.inevitable@hotmail.com',
                'Abdul'
            ], [
                'forceedge01@gmail.com',
                'Qureshi'
            ]
        ]);

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [['id' => 234324]]));

        $sqls = $this->testObject->iDontHaveWhere($entity, $node);

        $this->assertCount(2, $sqls);
    }

    public function testIDontHave()
    {
        $node = new TableNode([
            [
                'table',
                'values'
            ], [
                'table1',
                'id:34234, name:abdul'
            ], [
                'table2',
                'id:34234, name:Jenkins'
            ]
        ]);

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [['id' => 234324]]));

        $sqls = $this->testObject->iDontHave($node);

        $this->assertCount(2, $sqls);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testIHaveAWhereWithValuesRecordAlreadyExists()
    {
        $entity = 'database.unique';
        $column = 'column1:abc,column2:xyz';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [['id' => 234324]]));

        $result = $this->testObject->iHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.unique WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(234324, $this->testObject->getKeyword('database.unique_id'));
    }

    /**
     * @group test
     * Test that this method works with values provided.
     */
    public function testIHaveAWhereWithValuesRecordDoesNotExists()
    {
        $entity = 'database.unique1';
        $column = 'column1:abc,column2:xyz';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->onConsecutiveCalls(
                $this->getPdoStatementWithRows(0),
                $this->getPdoStatementWithRows(1),
                $this->getPdoStatementWithRows(1, [['id' => 237463]])
            ));

        $result = $this->testObject->iHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "INSERT INTO dev_database.unique1 (column1, column2) VALUES ('abc', 'xyz')";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(5, $this->testObject->getKeyword('database.unique1_id'));
    }

    /**
     * @expectedException Exception
     */
    public function testIDontHaveAWhere()
    {
        $entity = '';
        $column = '';

        $this->testObject->iDontHaveAWhere($entity, $column);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testIDontHaveAWhereWithValues()
    {
        $entity = 'database.someTable';
        $column = 'column1:abc,column2:xyz';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows());

        $result = $this->testObject->iDontHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "DELETE FROM dev_database.someTable WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(5, $this->testObject->getKeyword('database.someTable_id'));
    }

    /**
     * @expectedException Exception
     */
    public function testiHaveAnExistingWithWhere()
    {
        $entity = '';
        $with = '';
        $columns = '';

        $this->testObject->iHaveAnExistingWithWhere($entity, $with, $columns);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testiHaveAnExistingWithWhereWithValues()
    {
        $entity = 'database.someTable2';
        $with = 'column1:abc,column2:xyz';
        $columns = 'id:134';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [
                ['id' => 1234]
            ]));

        $result = $this->testObject->iHaveAnExistingWithWhere($entity, $with, $columns);

        // Expected SQL.
        $expectedSQL = "UPDATE dev_database.someTable2 SET column1 = 'abc', column2 = 'xyz' WHERE id = 134";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(1234, $this->testObject->getKeyword('database.someTable2_id'));
    }

    /**
     * @expectedException Exception
     */
    public function testiShouldNotHaveAWith()
    {
        $entity = '';
        $with = '';

        $this->testObject->iShouldNotHaveAWith($entity, $with);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testiShouldNotHaveAWithWithValues()
    {
        $entity = 'database.someTable3';
        $with = 'column1:abc,column2:xyz';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows());

        $result = $this->testObject->iShouldNotHaveAWith($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable3 WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(5, $this->testObject->getKeyword('database.someTable3_id'));
    }

    /**
     * Test that this method works with values provided.
     */
    public function testiShouldNotHaveAWithWithTableNode()
    {
        $entity = 'database.someTable3';
        $with = new TableNode([
            [
                'title',
                'value'
            ], [
                'column1',
                'abc'
            ], [
                'column2',
                'xyz'
            ]
        ]);

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows());

        $result = $this->testObject->iShouldNotHaveAWithTable($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable3 WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(5, $this->testObject->getKeyword('database.someTable3_id'));
    }

    /**
     * @expectedException Exception
     */
    public function testiShouldHaveAWith()
    {
        $entity = '';
        $with = '';

        $this->testObject->iShouldHaveAWith($entity, $with);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testiShouldHaveAWithTableNode()
    {
        $entity = 'database.someTable4';
        $with = new TableNode([
            [
                'title',
                'value'
            ], [
                'column1',
                'abc'
            ], [
                'column2',
                'xyz'
            ]
        ]);

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows());

        $result = $this->testObject->iShouldHaveAWithTable($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable4 WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(5, $this->testObject->getKeyword('database.someTable4_id'));
    }

    /**
     * Test that this method works with values provided.
     */
    public function testiShouldHaveAWithWithValues()
    {
        $entity = 'database.someTable4';
        $with = 'column1:abc,column2:xyz';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows());

        $result = $this->testObject->iShouldHaveAWith($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable4 WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(5, $this->testObject->getKeyword('database.someTable4_id'));
    }

    /**
     * @expectedException Exception
     */
    public function testISaveTheIdAs()
    {
        $key = 'myval';

        $this->testObject->iSaveTheIdAs($key);
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
    }
}
