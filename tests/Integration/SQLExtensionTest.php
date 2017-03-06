<?php

namespace Genesis\SQLExtension\Tests\Integation\Context;

use Behat\Gherkin\Node\TableNode;
use Genesis\SQLExtension\Context;
use Exception;
use Genesis\SQLExtension\Tests\TestHelper;

/**
 * @group sqlExtension
 * @group integration
 */
class SQLExtensionTest extends TestHelper
{
    /**
     * @var object $testObject The object to be tested.
     */
    private $testObject;

    /**
     * Set the test object.
     */
    public function setup()
    {
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];
        $_SESSION['behat']['GenesisSqlExtension']['keywords'] = [];
        $databaseParams = [];

        $this->testObject = new Context\SQLContext(
            new Context\DBManager($databaseParams),
            new Context\SQLBuilder(),
            new Context\LocalKeyStore(),
            new Context\SQLHistory()
        );

        // This PDO object comes from the testHelper class.
        $connectionMock = $this->getMockBuilder(\Genesis\SQLExtension\Context\PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testObject->get('dbManager')->setConnection($connectionMock);
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
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [[0 => 'id', 'id' => 234324]]));

        $sqls = $this->testObject->iDontHaveWhere($entity, $node);
        $this->assertCount(2, $sqls);

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(2, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
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
            'id:34234, name:abdul'
        ]);
        // Add more data.
        $node->addRow([
            'table2',
            'id:34234, name:Jenkins'
        ]);
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [[0 => 'id', 'id' => 234324]]));

        $sqls = $this->testObject->iDontHave($node);
        $this->assertCount(2, $sqls);

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(2, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * Test that this method works with values provided.
     *
     * @group testing
     */
    public function testIHaveAWhereWithValuesRecordDoesNotExists()
    {
        $entity = 'database.unique1';
        $column = "column1:abc,column2:xyz,column3:NULL,column4:what's up doc";
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->onConsecutiveCalls(
                $this->getPdoStatementWithRows(0),
                $this->getPdoStatementWithRows(1, [['column_name' => 'id', 'data_type' => 'int']]),
                $this->getPdoStatementWithRows(1, [[0 => 'id', 'id' => 237463]]),
                $this->getPdoStatementWithRows(1, [[0 => 'id', 'id' => 237463]])
            ));
        $result = $this->testObject->iHaveAWhere($entity, $column);
        // Expected SQL.
        $expectedSQL = "INSERT INTO dev_database.unique1 (`column1`, `column2`, `column3`, `column4`) VALUES ('abc', 'xyz', NULL, 'what\'s up doc')";
        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(237463, $this->testObject->getKeyword('database.unique1_id'));
        // After execution select all values.
        $this->assertEquals('select', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * @expectedException Exception
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
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(true, [[0 => 'id']]));

        $result = $this->testObject->iDontHaveAWhere($entity, $column);
        // Expected SQL.
        $expectedSQL = "DELETE FROM dev_database.someTable WHERE `column1` = 'abc' AND `column2` = 'xyz' AND `column3` is NULL AND `column4` = 'what\'s up doc'";
        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('delete', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * @expectedException Exception
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
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [
                [0 => 'id', 'id' => 1234, 'name' => 'Abdul']
            ]));

        $result = $this->testObject->iHaveAnExistingWithWhere($entity, $with, $columns);

        // Expected SQL.
        $expectedSQL = "UPDATE dev_database.someTable2 SET `column1` = 'abc', `column2` = 'xyz', `column3` = NULL, `column4` = 'what\'s up doc' WHERE `id` = 134 AND `photo` is not NULL AND `column` = 'what\'s up doc'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(1234, $this->testObject->getKeyword('database.someTable2_id'));
        $this->assertEquals('Abdul', $this->testObject->getKeyword('database.someTable2_name'));
        // After execution select all values.
        $this->assertEquals('select', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * @expectedException Exception
     * @group test
     */
    public function testiShouldNotHaveAWith()
    {
        $entity = '';
        $with = '';
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [
                [0 => 'id', 'id' => 1234, 'name' => 'Abdul']
            ]));

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
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(0, [[0 => 'id']]));

        $result = $this->testObject->iShouldNotHaveAWith($entity, $with);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable3 WHERE `column1` = 'abc' AND `column2` = 'xyz' AND `column3` = 'what\'s up doc'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * Test that this method works with values provided.
     *
     * @expectedException Exception
     *
     * @group test
     */
    public function testiShouldNotHaveAWithWithValuesReturnsRows()
    {
        $entity = 'database.someTable3';
        $with = "column1:abc,column2:xyz,column3:what's up doc";

        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(true, [[0 => 'id']]));

        $this->testObject->iShouldNotHaveAWith($entity, $with);
    }

    /**
     * Test that this method works with values provided.
     *
     * @group test
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
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(0, [[0 => 'id']]));

        $result = $this->testObject->iShouldNotHaveAWithTable($entity, $with);
        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable3 WHERE `column1` = 'abc' AND `column2` = 'xyz'";
        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * Test that this method works with values provided.
     *
     * @expectedException Exception
     *
     * @group test
     */
    public function testiShouldNotHaveAWithWithTableNodeFindsRows()
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
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(true, [[0 => 'id']]));
        $this->testObject->iShouldNotHaveAWithTable($entity, $with);
    }

    /**
     * @expectedException Exception
     *
     * @group test
     */
    public function testiShouldHaveAWith()
    {
        $entity = '';
        $with = '';

        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [
                [0 => 'id', 'id' => 1234, 'name' => 'Abdul']
            ]));

        $this->testObject->iShouldHaveAWith($entity, $with);
    }

    /**
     * Test that this method works with values provided.
     *
     * @group test
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
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(true, [[0 => 'id']]));

        $result = $this->testObject->iShouldHaveAWithTable($entity, $with);
        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable4 WHERE `column1` = 'abc' AND `column2` = 'xyz' AND `column3` is NULL";
        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * Test that this method works with values provided.
     *
     * @group test
     */
    public function testiShouldHaveAWithWithValues()
    {
        $entity = 'database.someTable4';
        $with = "column1:abc,column2:xyz,column3:NULL,column4:!NULL,column5:what's up doc";
        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(true, [[0 => 'id']]));

        $result = $this->testObject->iShouldHaveAWith($entity, $with);
        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable4 WHERE `column1` = 'abc' AND `column2` = 'xyz' AND `column3` is NULL AND `column4` is not NULL AND `column5` = 'what\'s up doc'";
        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * Test that this method works with values containing wildcards for a LIKE search.
     *
     * @group test
     */
    public function testiShouldHaveAWithWithLikeValues()
    {
        $entity = 'database.someTable4';
        $with = 'column1:abc,column2:%xyz%';

        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
             ->method('prepare')
             ->with($this->isType('string'))
             ->willReturn($this->getPdoStatementWithRows(true, [[0 => 'id']]));

        $result = $this->testObject->iShouldHaveAWith($entity, $with);
        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.someTable4 WHERE `column1` = 'abc' AND `column2` LIKE '%xyz%'";
        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * @group test
     */
    public function testISaveTheIdAs()
    {
        $key = 'myval';
        $this->testObject->iSaveTheIdAs($key);
    }

    /**
     * @group test
     */
    public function testiAmInDebugMode()
    {
        ob_start();
        $this->testObject->iAmInDebugMode();
        $string = ob_get_clean();

        $this->assertInternalType('string', $string);
        $this->assertTrue(defined('DEBUG_MODE'));
    }

    /**
     * testIHaveAnExistingWhere Test that iHaveAnExistingWhere executes as expected.
     *
     * @expectedException Exception
     */
    public function testIHaveAnExistingWhereNoRows()
    {
        // Prepare / Mock
        $entity = 'abc.my_entity';
        $where = 'column1:abc, column2:!xyz, column3: %yes%';

        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(0));

        // Execute
        $this->testObject->iHaveAnExistingWhere($entity, $where);
    }

    /**
     * testIHaveAnExistingWhere Test that iHaveAnExistingWhere executes as expected.
     */
    public function testIHaveAnExistingWhereWithRows()
    {
        // Prepare / Mock
        $entity = 'abc.my_entity';
        $where = 'column1:abc, column2:!xyz';
        $expectedResult = [
            0 => 'id',
            'column1' => 'abc',
            'column2' => 'random'
        ];

        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [
                $expectedResult
            ]));

        // Execute
        $result = $this->testObject->iHaveAnExistingWhere($entity, $where);

        $this->assertEquals('dev_abc.my_entity', $this->testObject->getEntity());
        $this->assertEquals($expectedResult, $result);

        $this->assertEquals('abc', $this->testObject->getKeyword('abc.my_entity.column1'));
        $this->assertEquals('abc', $this->testObject->getKeyword('abc.my_entity_column1'));

        $this->assertEquals('random', $this->testObject->getKeyword('abc.my_entity_column2'));
        $this->assertEquals('random', $this->testObject->getKeyword('abc.my_entity.column2'));

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }


    /**
     * test that the keywords resolve using any call.
     *
     * @group keyword
     */
    public function testThatKeywordsResolve()
    {
        $keyword = 'hjlasjdkfhlajksfdhklasdfj';
        $this->testObject->setKeyword('abc', $keyword);

        // Prepare / Mock
        $entity = 'abc.my_entity';
        $where = 'column1:{abc}, column2:!xyz';
        $expectedResult = [
            0 => 'id',
            'column1' => $keyword,
            'column2' => 'random'
        ];

        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [
                $expectedResult
            ]));

        // Execute
        $result = $this->testObject->iHaveAnExistingWhere($entity, $where);

        $this->assertEquals('dev_abc.my_entity', $this->testObject->getEntity());
        $this->assertEquals($expectedResult, $result);

        $this->assertEquals($keyword, $this->testObject->getKeyword('abc.my_entity.column1'));
        $this->assertEquals($keyword, $this->testObject->getKeyword('abc.my_entity_column1'));

        $this->assertEquals('random', $this->testObject->getKeyword('abc.my_entity_column2'));
        $this->assertEquals('random', $this->testObject->getKeyword('abc.my_entity.column2'));

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * testInsertResolvesExternalRefs Test that insert executes as expected.
     *
     * @group externalRef
     */
    public function testInsertResolvesExternalRefs()
    {
        // Set keyword
        $keyword = 'hjlasjdkfhlajksfdhklasdfj';
        $this->testObject->setKeyword('abc', $keyword);

        // Set external ref.
        $externalRefId = 3443;
        $entity = 'database.unique';
        $column = "column1:{abc},column2:[user.id|email:its.its.inevitable@hotmail.com],column3:what\'s up doc";

        $expectedResult = [
            0 => 3443,
            'id' => 3443
        ];

        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->onConsecutiveCalls(
                $this->getPdoStatementWithRows(0, [[0 => 'id']]),
                $this->getPdoStatementWithRows(1, true),
                $this->getPdoStatementWithRows(1, [['column_name' => 'id', 'data_type' => 'int']]),
                $this->getPdoStatementWithRows(1, true),
                $this->getPdoStatementWithRows(1, [[0 => 'id', 'id' => 237463]])
            ));

        $result = $this->testObject->insert($entity, $column);

        // Expected SQL.
        $expectedSQL = "INSERT INTO dev_database.unique (`column1`, `column2`, `column3`) VALUES ('hjlasjdkfhlajksfdhklasdfj', 'behat-test-string-234234234', 'what\'s up doc')";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(2, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }

    /**
     * testInsertResolvesExternalRefs Test that insert executes as expected.
     *
     * @group externalRef
     */
    public function testShouldHaveResolvesExternalRefs()
    {
        // Set keyword
        $keyword = 'hjlasjdkfhlajksfdhklasdfj';
        $this->testObject->setKeyword('abc', $keyword);

        // Set external ref.
        $externalRefId = 3443;
        $entity = 'database.unique';
        $column = "column1:{abc},column2:[user.id|email:its.its.inevitable@hotmail.com],column3:what\'s up doc";

        $expectedResult = [
            0 => 3443,
            'id' => 3443
        ];

        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getPdoStatementWithRows(1, [
                $expectedResult
            ]));

        $result = $this->testObject->iShouldHaveAWith($entity, $column);

        // Expected SQL.
        $expectedSQL = "SELECT * FROM dev_database.unique WHERE `column1` = 'hjlasjdkfhlajksfdhklasdfj' AND `column2` = 3443 AND `column3` = 'what\'s up doc'";

        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals('select', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(2, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }


    /**
     * Test that this method works with values provided.
     */
    public function testIHaveAWhereWithOrStatementAndExternalRef()
    {
        $entity = 'database.unique';
        $column = "column1:abc||column2:[user.id|abc:1||name:Abdul]||column3:what\'s up doc";
        // $this->testObject->get('dbManager')->getConnection()->expects($this->any())
        //     ->method('prepare')
        //     ->with($this->isType('string'))
        //     ->willReturn($this->getPdoStatementWithRows(1, [[0 => 234324, 'id' => 234324]]));

        $this->testObject->get('dbManager')->getConnection()->expects($this->any())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->onConsecutiveCalls(
                $this->getPdoStatementWithRows(0, [[0 => 'id']]),
                $this->getPdoStatementWithRows(1, true),
                $this->getPdoStatementWithRows(1, [['column_name' => 'id', 'data_type' => 'int']]),
                $this->getPdoStatementWithRows(1, true),
                $this->getPdoStatementWithRows(1, [[0 => 'id', 'id' => 237463]])
            ));

        $result = $this->testObject->iHaveAWhere($entity, $column);
        // Expected SQL.
        $expectedSQL = "INSERT INTO dev_database.unique (`column1`, `column2`, `column3`) VALUES ('abc', 'behat-test-string-234234234', 'what\'s up doc')";
        // Assert.
        $this->assertEquals($expectedSQL, $result);
        $this->assertNotNull($this->testObject->getEntity());
        $this->assertEquals(237463, $this->testObject->getKeyword('database.unique_id'));
        $this->assertEquals('select', $this->testObject->getCommandType());

        // Check history.
        $this->assertCount(1, $this->testObject->get('sqlHistory')->getHistory()['insert']);
        $this->assertCount(2, $this->testObject->get('sqlHistory')->getHistory()['select']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['delete']);
        $this->assertCount(0, $this->testObject->get('sqlHistory')->getHistory()['update']);
    }
}
