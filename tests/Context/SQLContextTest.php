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
     * Make sure that the IHaveWhere method works as expected.
     *
     * @group test
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

        $convertedQuery1 = [
            'email' => 'its.its.inevitable@hotmail.com',
            'name' => 'Abdul'
        ];

        $convertedQuery2 = [
            'email' => 'forceedge01@gmail.com',
            'name' => 'Qureshi'
        ];

        $this->mockDependency('sqlBuilder', 'convertTableNodeToQueries', [$node], $queries);

        $this->mockDependencyValueMap('sqlBuilder', 'convertToArray', array(
                array('email:its.inevitable@hotmail.com,name:Abdul', $convertedQuery1),
                array('email:forceedge01@gmail.com,name:Qureshi', $convertedQuery2)
            ));

        $this->mockDependencyValueMap('sqlBuilder', 'constructSQLClause', array(
                array('select', ' AND ', $convertedQuery1, "email = 'its.inevitable@hotmail.com' AND name = 'Abdul'"),
                array('select', ' AND ', $convertedQuery2, "email = 'forceedge01@gmail.com' AND name = 'Qureshi'")
            ));

        $sqls = $this->testObject->iHaveWhere($entity, $node);

        $this->assertCount(2, $sqls);
        $this->assertEquals(5, $this->testObject->getKeyword(sprintf('%s_id', $entity)));
    }

    /**
     * Make sure that the IHave method works as expected.
     *
     * @group test
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
            "email:its.inevitable@hotmail.com, name:Abdul"
        ]);

        // Add more data.
        $node->addRow([
            'table2',
            'email:forceedge01@gmail.com, name:Qureshi'
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

        $convertedQuery1 = [
            'email' => 'its.its.inevitable@hotmail.com',
            'name' => 'Abdul'
        ];

        $convertedQuery2 = [
            'email' => 'forceedge01@gmail.com',
            'name' => 'Qureshi'
        ];

        $this->mockDependencyValueMap('sqlBuilder', 'convertToArray', array(
                array('email:its.inevitable@hotmail.com, name:Abdul', $convertedQuery1),
                array('email:forceedge01@gmail.com, name:Qureshi', $convertedQuery2)
            ));

        $this->mockDependencyValueMap('sqlBuilder', 'constructSQLClause', array(
                array('select', ' AND ', $convertedQuery1, "email = 'its.inevitable@hotmail.com' AND name = 'Abdul'"),
                array('select', ' AND ', $convertedQuery2, "email = 'forceedge01@gmail.com' AND name = 'Qureshi'")
            ));

        $sqls = $this->testObject->iHave($node);

        $this->assertCount(2, $sqls);
    }

    /**
     * Test that this method works with values provided.
     *
     * @group test
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

        $convertedQuery1 = [
            'column1' => 'abc',
            'column2' => 'xyz',
            'column3' => 'NULL',
            'column4' => 'what\\\'s up doc'
        ];

        $this->mockDependency('sqlBuilder', 'convertToArray',
                array('column1:abc,column2:xyz,column3:NULL, column4:what\\\'s up doc'), $convertedQuery1);

        $this->mockDependency(
            'sqlBuilder',
            'constructSQLClause',
            array(
                'select',
                ' AND ',
                $convertedQuery1
            ),
            "column1 = 'abc' AND column2 = 'xyz' AND column3 is NULL AND column4 = 'what\'s up doc'"
        );

        $this->mockDependency('dbHelperMock', 'getRequiredTableColumns', null, []);

        $this->mockDependency('sqlBuilder', 'getColumns', null, ['column1' => 'abc']);

        // print_r($this->testObject->get('sqlBuilder')->getColumns());

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
     *
     * @group test
     */
    public function testIHaveAWhereWithValuesRecordDoesNotExists()
    {
        $entity = 'database.unique1';
        $column = "column1:abc";

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

        $convertedQuery1 = [
            'column1' => 'abc'
        ];

        $this->mockDependency('sqlBuilder', 'convertToArray',
                array('column1:abc'), $convertedQuery1);

        $this->mockDependency(
            'sqlBuilder',
            'constructSQLClause',
            array(
                'select',
                ' AND ',
                $convertedQuery1
            ),
            "column1 = 'abc'"
        );

        $this->mockDependency('dbHelperMock', 'getRequiredTableColumns', null, []);

        $this->mockDependency('sqlBuilder', 'getColumns', null, ['column1' => 'abc']);

        $this->mockDependency('sqlBuilder', 'quoteOrNot', null, "'abc'");

        $result = $this->testObject->iHaveAWhere($entity, $column);

        // Expected SQL.
        $expectedSQL = "INSERT INTO dev_database.unique1 (column1) VALUES ('abc')";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('insert', $this->testObject->getCommandType());
    }

    /**
     * @expectedException Exception
     *
     * @group test
     */
    public function testIDontHaveAWhere()
    {
        $entity = '';
        $column = '';

        $this->testObject->iDontHaveAWhere($entity, $column);
    }

    /**
     * Test that this method works with values provided.
     *
     * @group test
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

        $convertedQuery1 = [
            'column1' => 'abc',
            'column2' => 'xyz',
            'column3' => 'NULL',
            'column4' => 'what\'s up doc'
        ];

        $this->mockDependency('sqlBuilder', 'convertToArray',
                array('column1:abc,column2:xyz,column3:NULL,column4:what\'s up doc'), $convertedQuery1);

        $this->mockDependency(
            'sqlBuilder',
            'constructSQLClause',
            array(
                'delete',
                ' AND ',
                $convertedQuery1
            ),
            "column1 = 'abc' AND column2 = 'xyz' AND column3 is NULL AND column4 = 'what\\'s up doc'"
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
     * @group test
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

        $convertedQuery1 = [
            'email' => 'its.its.inevitable@hotmail.com',
            'name' => 'Abdul'
        ];

        $convertedQuery2 = [
            'email' => 'forceedge01@gmail.com',
            'name' => 'Qureshi'
        ];

        $queries = [
            'email:its.inevitable@hotmail.com,name:Abdul',
            'email:forceedge01@gmail.com,name:Qureshi'
        ];

        $this->mockDependency('sqlBuilder', 'convertTableNodeToQueries', [$node], $queries);

        $this->mockDependencyValueMap('sqlBuilder', 'convertToArray', array(
                array('email:its.inevitable@hotmail.com,name:Abdul', $convertedQuery1),
                array('email:forceedge01@gmail.com,name:Qureshi', $convertedQuery2)
            ));

        $this->mockDependencyValueMap('sqlBuilder', 'constructSQLClause', array(
                array('delete', ' AND ', $convertedQuery1, "email = 'its.inevitable@hotmail.com' AND name = 'Abdul'"),
                array('delete', ' AND ', $convertedQuery2, "email = 'forceedge01@gmail.com' AND name = 'Qureshi'")
            ));

        $sqls = $this->testObject->iDontHaveWhere($entity, $node);

        $this->assertCount(2, $sqls);
    }

    /**
     * @group test
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
            "email:its.inevitable@hotmail.com,name:Abdul"
        ]);

        // Add more data.
        $node->addRow([
            'table2',
            'email:forceedge01@gmail.com,name:Qureshi'
        ]);

        $this->mockDependencyMethods(
            'dbHelperMock',
            [
                'execute' => $this->getPdoStatementWithRows(1, [['id' => 234324]]),
                'hasFetchedRows' => true
            ]
        );

        $convertedQuery1 = [
            'email' => 'its.its.inevitable@hotmail.com',
            'name' => 'Abdul'
        ];

        $convertedQuery2 = [
            'email' => 'forceedge01@gmail.com',
            'name' => 'Qureshi'
        ];

        $queries = [
            'email:its.inevitable@hotmail.com,name:Abdul',
            'email:forceedge01@gmail.com,name:Qureshi'
        ];

        $this->mockDependency('sqlBuilder', 'convertTableNodeToQueries', [$node], $queries);

        $this->mockDependencyValueMap('sqlBuilder', 'convertToArray', array(
                array('email:its.inevitable@hotmail.com,name:Abdul', $convertedQuery1),
                array('email:forceedge01@gmail.com,name:Qureshi', $convertedQuery2)
            ));

        $sqls = $this->testObject->iDontHave($node);

        $this->assertCount(2, $sqls);
    }

    /**
     * @expectedException Exception
     *
     * @group test
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
     *
     * @group test
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

        $convertedQuery1 = [
            'column1' => 'abc',
            'column2' => 'xyz',
            'column3' => 'NULL',
            'column4' => 'what\\\'s up doc'
        ];

        $convertedQuery2 = [
            'id' => '134',
            'photo' => 'is not NULL',
            'column' => 'what\\\'s up doc'
        ];

        $this->mockDependencyValueMap('sqlBuilder', 'convertToArray', array(
                array('column1:abc,column2:xyz,column3:NULL,column4:what\'s up doc', $convertedQuery1),
                array('id:134,photo:!NULL,column:what\'s up doc', $convertedQuery2)
            ));

        $this->mockDependencyValueMap('sqlBuilder', 'constructSQLClause', array(
                array('update', ', ', $convertedQuery1, "column1 = 'abc', column2 = 'xyz', column3 = NULL, column4 = 'what\'s up doc'"),
                array('update', ' AND ', $convertedQuery2, "id = 134 AND photo is not NULL AND column = 'what\'s up doc'")
            ));

        $result = $this->testObject->iHaveAnExistingWithWhere($entity, $with, $columns);

        // Expected SQL.
        $expectedSQL = "UPDATE dev_database.someTable2 SET column1 = 'abc', column2 = 'xyz', column3 = NULL, column4 = 'what\'s up doc' WHERE id = 134 AND photo is not NULL AND column = 'what\'s up doc'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('update', $this->testObject->getCommandType());
    }

    /**
     * @group test
     */
    public function testiHaveAnExistingWhere()
    {
        $this->markTestIncomplete();

        $this->testObject->iHaveAnExistingWhere();
    }

    /**
     * @expectedException Exception
     *
     * @group test
     */
    public function testiShouldNotHaveAWithThrowException()
    {
        $entity = '';
        $with = '';

        $this->testObject->iShouldNotHaveAWith($entity, $with);
    }

    /**
     * Test that this method works with values provided.
     *
     * @group test
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
