<?php

namespace Genesis\SQLExtension\Tests\Context;

use Behat\Gherkin\Node\TableNode;
use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;
use Genesis\SQLExtension\Context\Interfaces\KeyStoreInterface;
use Genesis\SQLExtension\Context\Interfaces\SQLBuilderInterface;
use Genesis\SQLExtension\Context\SQLContext;
use Exception;

/**
 * @group sqlContext
 */
class SQLContextTest extends TestHelper
{
    /**
     * @var object $testObject The object to be tested.
     */
    private $testObject;

    /**
     * Sample connection string.
     */
    const CONNECTION_STRING = 'BEHAT_ENV_PARAMS=DBENGINE:mysql;DBSCHEMA:;DBNAME:abc;DBHOST:localhost;DBUSER:root;DBPASSWORD:toor;DBPREFIX:';

    public function setup()
    {
        // $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

        putenv(self::CONNECTION_STRING);

        $this->dependencies['dbHelperMock'] = $this->getMockBuilder(DBManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dependencies['dbHelperMock']->expects($this->any())
            ->method('getPrimaryKeyForTable')
            ->will($this->returnValue('id'));

        $this->dependencies['dbHelperMock']->expects($this->any())
            ->method('getLastInsertId')
            ->will($this->returnValue(5));

        $this->dependencies['dbHelperMock']->expects($this->any())
            ->method('getParams')
            ->will($this->returnValue(
                ['DBPREFIX' => 'dev_', 'DBNAME' => 'mydb', 'DBSCHEMA' => 'myschema']
            ));

        $this->dependencies['sqlBuilder'] = $this->getMockBuilder(SQLBuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dependencies['keyStoreMock'] = $this->getMockBuilder(KeyStoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dependencies['keyStoreMock']->expects($this->any())
            ->method('getKeywordFromConfigForKeyIfExists')
            ->will($this->returnArgument(0));

        $this->dependencies['keyStoreMock']->expects($this->any())
            ->method('getKeyword')
            ->will($this->returnValue(5));

        $this->testObject = new SQLContext(
            $this->dependencies['dbHelperMock'],
            $this->dependencies['sqlBuilder'],
            $this->dependencies['keyStoreMock']
        );
    }

    /**
     */
    public function testIHaveWhere()
    {
        $entity = 'database.unique';
        $node = new TableNode();
        // Add title row.
        $node->addRow([
            'email',
            'name'
        ]);

        // Add data.
        $node->addRow([
            'its.inevitable@hotmail.com',
            'Abdul'
        ]);

        // Add more data.
        $node->addRow([
            'forceedge01@gmail.com',
            'Qureshi'
        ]);

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(1, [[
                    'name' => 'Abdul',
                    'email' => 'its.inevitable@hotmail.com'
                ]]),
                'hasFetchedRows' => true
            ]
        );

        $queries = [
            'email:its.inevitable@hotmail.com,name:Abdul',
            'email:forceedge01@gmail.com,name:Qureshi'
        ];

        $this->mockDependency('sqlBuilder', 'convertTableNodeToQueries', [$node], $queries);

        $sqls = $this->testObject->iHaveWhere($entity, $node);

        $this->assertCount(2, $sqls);
        $this->assertEquals(5, $this->testObject->getKeyword(sprintf('%s_id', $entity)));
    }

    /**
     */
    public function testIHave()
    {
        $node = new TableNode();
        // Add title row.
        $node->addRow([
            'table',
            'values'
        ]);

        // Add data.
        $node->addRow([
            'table1',
            "id:34234, name:abdul"
        ]);

        // Add more data.
        $node->addRow([
            'table2',
            'id:34234, name:Jenkins'
        ]);

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(1, [[
                    'name' => 'Abdul',
                    'email' => 'its.inevitable@hotmail.com'
                ]]),
                'hasFetchedRows' => true
            ]
        );

        $sqls = $this->testObject->iHave($node);

        $this->assertCount(2, $sqls);
    }

    /**
     */
    public function testIDontHaveWhere()
    {
        $entity = 'database.unique';
        $node = new TableNode();
        // Add title row.
        $node->addRow([
            'email',
            'name'
        ]);

        // Add data.
        $node->addRow([
            'its.inevitable@hotmail.com',
            'Abdul'
        ]);

        // Add more data.
        $node->addRow([
            'forceedge01@gmail.com',
            'Qureshi'
        ]);

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(1, [['id' => 234324]]),
                'hasFetchedRows' => true
            ]
        );

        $sqls = $this->testObject->iDontHaveWhere($entity, $node);

        $this->assertCount(2, $sqls);
    }

    /**
     */
    public function testIDontHave()
    {
        $node = new TableNode();
        // Add title row.
        $node->addRow([
            'table',
            'values'
        ]);

        // Add data.
        $node->addRow([
            'table1',
            'id:34234, name:abdul'
        ]);

        // Add more data.
        $node->addRow([
            'table2',
            'id:34234, name:Jenkins'
        ]);

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(1, [['id' => 234324]]),
                'hasFetchedRows' => true
            ]
        );

        $sqls = $this->testObject->iDontHave($node);

        $this->assertCount(2, $sqls);
    }

    /**
     * Test that this method works with values provided.
     */
    public function testIHaveAWhereWithValuesRecordAlreadyExists()
    {
        $entity = 'database.unique';
        $column = "column1:abc,column2:xyz,column3:NULL, column4:what\'s up doc";

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(1, [['name' => 'Abdul']]),
                'hasFetchedRows' => true
            ]
        );

        $result = $this->testObject->iHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.unique WHERE column1 = 'abc' AND column2 = 'xyz' AND column3 is NULL AND column4 = 'what\'s up doc'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());
    }

    /**
     * Test that this method works with values provided.
     */
    public function testIHaveAWhereWithValuesRecordDoesNotExists()
    {
        $entity = 'database.unique1';
        $column = "column1:abc,column2:xyz,column3:NULL,column4:what's up doc";

        $this->dependencies['dbHelperMock']->expects($this->any())
            ->method('getRequiredTableColumns')
            ->with($this->isType('string'))
            ->will($this->returnValue([]));

        $this->dependencies['dbHelperMock']->expects($this->any())
            ->method('hasFetchedRows')
            ->will($this->onConsecutiveCalls(
                false,
                true,
                true
            ));

        $this->dependencies['dbHelperMock']->expects($this->any())
            ->method('execute')
            ->with($this->isType('string'))
            ->will($this->onConsecutiveCalls(
                $this->getPdoStatementWithRows(0),
                $this->getPdoStatementWithRows(1, [['id' => 237463]]),
                $this->getPdoStatementWithRows(1, [['id' => 237463]])
            ));

        $result = $this->testObject->iHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "INSERT INTO dev_database.unique1 (column1, column2, column3, column4) VALUES ('abc', 'xyz', NULL, 'what\'s up doc')";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('insert', $this->testObject->getCommandType());
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
        $column = "column1:abc,column2:xyz,column3:NULL,column4:what's up doc";

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(),
                'hasFetchedRows' => true
            ]
        );

        $result = $this->testObject->iDontHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "DELETE FROM dev_database.someTable WHERE column1 = 'abc' AND column2 = 'xyz' AND column3 is NULL AND column4 = 'what\'s up doc'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('delete', $this->testObject->getCommandType());
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
        $with = "column1:abc,column2:xyz,column3:NULL,column4:what's up doc";
        $columns = "id:134,photo:!NULL,column:what's up doc";

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(1, [
                    ['email' => 'its.inevitable@hotmail.com', 'name' => 'Abdul']
                ]),
                'hasFetchedRows' => true
            ]
        );

        $result = $this->testObject->iHaveAnExistingWithWhere($entity, $with, $columns);

        // Expected SQL.
        $expectedSQL = "UPDATE dev_database.someTable2 SET column1 = 'abc', column2 = 'xyz', column3 = NULL, column4 = 'what\'s up doc' WHERE id = 134 AND photo is not NULL AND column = 'what\'s up doc'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('update', $this->testObject->getCommandType());
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
        $with = "column1:abc,column2:xyz,column3:what's up doc";

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(),
                'hasFetchedRows' => true
            ]
        );

        $result = $this->testObject->iShouldNotHaveAWith($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable3 WHERE column1 = 'abc' AND column2 = 'xyz' AND column3 = 'what\'s up doc'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());
    }

    /**
     * Test that this method works with values provided.
     */
    public function testiShouldNotHaveAWithWithTableNode()
    {
        $entity = 'database.someTable3';
        $with = new TableNode();
        $with->addRow([
            'title',
            'value'
        ]);
        $with->addRow([
            'column1',
            'abc'
        ]);
        $with->addRow([
            'column2',
            'xyz'
        ]);

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(),
                'hasFetchedRows' => true
            ]
        );

        $result = $this->testObject->iShouldNotHaveAWithTable($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable3 WHERE column1 = 'abc' AND column2 = 'xyz'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());
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
        $with = new TableNode();
        $with->addRow([
            'title',
            'value'
        ]);
        $with->addRow([
            'column1',
            'abc'
        ]);
        $with->addRow([
            'column2',
            'xyz'
        ]);
        $with->addRow([
            'column3',
            'NULL'
        ]);

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(),
                'hasFetchedRows' => true
            ]
        );

        $result = $this->testObject->iShouldHaveAWithTable($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable4 WHERE column1 = 'abc' AND column2 = 'xyz' AND column3 is NULL";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());
    }

    /**
     * Test that this method works with values provided.
     */
    public function testiShouldHaveAWithWithValues()
    {
        $entity = 'database.someTable4';
        $with = "column1:abc,column2:xyz,column3:NULL,column4:!NULL,column5:what's up doc";

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(),
                'hasFetchedRows' => true
            ]
        );

        $result = $this->testObject->iShouldHaveAWith($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable4 WHERE column1 = 'abc' AND column2 = 'xyz' AND column3 is NULL AND column4 is not NULL AND column5 = 'what\'s up doc'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());
    }

    /**
     * Test that this method works with values containing wildcards for a LIKE search.
     */
    public function testiShouldHaveAWithWithLikeValues()
    {
        $entity = 'database.someTable4';
        $with = 'column1:abc,column2:%xyz%';

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(),
                'hasFetchedRows' => true
            ]
        );

        $result = $this->testObject->iShouldHaveAWith($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable4 WHERE column1 = 'abc' AND column2 LIKE '%xyz%'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
    }
}
