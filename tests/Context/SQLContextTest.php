<?php

namespace Genesis\SQLExtension\Tests\Context;

use Genesis\SQLExtension\Context\SQLContext;
use PHPUnit_Framework_TestCase;

class SQLContextTest extends PHPUnit_Framework_TestCase
{
    private $testObject;

    const CONNECTION_STRING = 'BEHAT_ENV_PARAMS=DBENGINE:mysql;DBSCHEMA:;DBNAME:abc;DBHOST:localhost;DBUSER:root;DBPASSWORD:toor';

    public function __construct()
    {
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

        $this->testObject = new SQLContext();

        putenv(self::CONNECTION_STRING);

        $pdoConnectionMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testObject->setConnection($pdoConnectionMock);
    }

    /**
     * Check that the expected sql is generated.
     */
    public function testIHaveAWhere()
    {
        $entity = 'database.someTable';
        $column = 'column1:abc,column2:xyz';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->willReturn($this->getPdoStatementWithRows());

        // This call will return the sql to be executed. While at it,
        // it will run through the code and check if anything breaks.
        $result = $this->testObject->iHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "INSERT INTO `database`.`someTable` (column1, column2) VALUES ('abc', 'xyz')";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testIHaveAWhereWithValues()
    {
        $entity = 'database.someTable';
        $column = 'column1:abc,column2:xyz';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->willReturn($this->getPdoStatementWithRows());

        $result = $this->testObject->iHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "INSERT INTO `database`.`someTable` (column1, column2) VALUES ('abc', 'xyz')";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
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
            ->willReturn($this->getPdoStatementWithRows());

        $result = $this->testObject->iDontHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "DELETE FROM `database`.`someTable` WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
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
        $entity = 'database.someTable';
        $with = 'column1:abc,column2:xyz';
        $columns = 'id:134';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->willReturn($this->getPdoStatementWithRows(1, [
                ['id' => 1234]
            ]));

        $result = $this->testObject->iHaveAnExistingWithWhere($entity, $with, $columns);

        // Expected SQL.
        $expectedSQL = "UPDATE `database`.`someTable` SET column1 = 'abc', column2 = 'xyz' WHERE id = 134";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
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
        $entity = 'database.someTable';
        $with = 'column1:abc,column2:xyz';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->willReturn($this->getPdoStatementWithRows());

        $result = $this->testObject->iShouldNotHaveAWith($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM `database`.`someTable` WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
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
    public function testiShouldHaveAWithWithValues()
    {
        $entity = 'database.someTable';
        $with = 'column1:abc,column2:xyz';

        $this->testObject->getConnection()->expects($this->any())
            ->method('prepare')
            ->willReturn($this->getPdoStatementWithRows());

        $result = $this->testObject->iShouldHaveAWith($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM `database`.`someTable` WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
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

        return $statementMock;
    }
}
