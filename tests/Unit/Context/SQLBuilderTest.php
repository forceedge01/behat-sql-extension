<?php

namespace Genesis\SQLExtension\Context;

// Mock rand method for testing.
function rand($min = null, $max = null)
{
    if ($min == null && $max == null) {
        return \Genesis\SQLExtension\Tests\Unit\Context\SQLBuilderTest::INT_NUMBER;
    }

    return \Genesis\SQLExtension\Tests\Unit\Context\SQLBuilderTest::TINY_INT_NUMBER;
}

// Mock time method for testing.
function time()
{
    return \Genesis\SQLExtension\Tests\Unit\Context\SQLBuilderTest::TYPE_STRING_TIME;
}

namespace Genesis\SQLExtension\Tests\Unit\Context;

use PHPUnit_Framework_TestCase;
use Genesis\SQLExtension\Context\SQLBuilder;
use Behat\Gherkin\Node\TableNode;

/**
 * @group sqlBuilder
 */
class SQLBuilderTest extends PHPUnit_Framework_TestCase
{
    const TYPE_STRING_TIME = 234234234;
    const INT_NUMBER = 23423;
    const TINY_INT_NUMBER = 5;

    /**
     * @var object $testObject The object to be tested.
     */
    private $testObject;

    /**
     * Set up the testing object.
     */
    public function setUp()
    {
        $this->testObject = new SQLBuilder();
    }

    /**
     * Tests that constructSQLClause executes as expected with LIKE values.
     */
    public function testConstructSQLClauseLikeValues()
    {
        $_SESSION['behat']['GenesisSqlExtension']['keywords'] = [];
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

        // Prepare / Mock
        $commandType = 'select';
        $glue = ' AND ';
        $columns = [
            'firstname' => 'Bob',
            'user_agent' => '%Firefox%'
        ];

        // Execute
        $result = $this->testObject->constructSQLClause($commandType, $glue, $columns);

        $expected = "firstname = 'Bob' AND user_agent LIKE '%Firefox%'";

        // Assert Result
        $this->assertEquals($expected, $result);
    }

    /**
     * testconstructSQLClause Test that constructSQLClause executes as expected with a null.
     */
    public function testconstructSQLClauseNullValues()
    {
        $_SESSION['behat']['GenesisSqlExtension']['keywords'] = [];
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

        // Prepare / Mock
        $columns = [
            'firstname' => 'null',
            'lastname' => '!null',
            'postcode' => '!NULL',
            'address' => 'NULL'
        ];

        // Execute

        $result = $this->testObject->constructSQLClause('select', ' AND ', $columns);
        $expected = "firstname is null AND lastname is not null AND postcode is not NULL AND address is NULL";
        // Assert Result
        $this->assertEquals($expected, $result);

        // Execute
        $result = $this->testObject->constructSQLClause('update', ' AND ', $columns);
        $expected = "firstname is null AND lastname is not null AND postcode is not NULL AND address is NULL";
        // Assert Result
        $this->assertEquals($expected, $result);

        // Execute
        $result = $this->testObject->constructSQLClause('delete', ' AND ', $columns);
        $expected = "firstname is null AND lastname is not null AND postcode is not NULL AND address is NULL";
        // Assert Result
        $this->assertEquals($expected, $result);
    }

    /**
     * testconstructSQLClause Test that constructSQLClause executes as expected with a not.
     */
    public function testconstructSQLClauseNot()
    {
        $_SESSION['behat']['GenesisSqlExtension']['keywords'] = [];
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

        // Prepare / Mock
        $glue = ' - ';
        $columns = [
            'firstname' => '!Abdul',
            'lastname' => 'Qureshi'
        ];

        // Execute
        $result = $this->testObject->constructSQLClause('select', $glue, $columns);

        $expected = "firstname != 'Abdul' - lastname = 'Qureshi'";

        // Assert Result
        $this->assertEquals($expected, $result);
    }

    /**
     * testconstructSQLClause Test that constructSQLClause executes as expected.
     */
    public function testconstructSQLClause()
    {
        $_SESSION['behat']['GenesisSqlExtension']['keywords'] = [];
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

        // Prepare / Mock
        $glue = ' - ';
        $columns = [
            'firstname' => 'Abdul',
            'lastname' => 'Qureshi'
        ];

        // Execute
        $result = $this->testObject->constructSQLClause('select', $glue, $columns);

        $expected = "firstname = 'Abdul' - lastname = 'Qureshi'";

        // Assert Result
        $this->assertEquals($expected, $result);
    }

    /**
     * testConvertToArray Test that convertToArray executes as expected.
     */
    public function testConvertToArray()
    {
        // Prepare / Mock
        $columns = 'one:1,
            two:2,
            three:ransom,
            four:randomness,
            address: 1\, Burlington road,
            created: DATE(NOW()\, "U")';

        // Execute
        $result = $this->testObject->convertToArray($columns);

        // Assert Result
        $this->assertEquals($result['one'], 1);
        $this->assertEquals($result['two'], 2);
        $this->assertEquals($result['three'], 'ransom');
        $this->assertEquals($result['four'], 'randomness');
        $this->assertEquals($result['address'], '1, Burlington road');
        $this->assertEquals($result['created'], 'DATE(NOW(), "U")');
    }

    /**
     * testFilterAndConvertToArray Test that filterAndConvertToArray executes as expected.
     *
     * @expectedException Exception
     */
    public function testFilterAndConvertToArrayNoSeparator()
    {
        // Prepare / Mock
        $columns = 'one string without separator';

        // Execute
        $this->testObject->convertToArray($columns);
    }

    /**
     * testFilterAndConvertToArray Test that filterAndConvertToArray executes as expected.
     *
     * @expectedException Exception
     */
    public function testFilterAndConvertToArraySeparatorTheFirstChar()
    {
        // Prepare / Mock
        $columns = ':one string without separator';

        // Execute
        $this->testObject->convertToArray($columns);
    }

    /**
     * testQuoteOrNot Test that quoteOrNot executes as expected.
     */
    public function testQuoteOrNot()
    {
        // Prepare / Mock
        // The key is the expected output and the value is the input.
        $values = [
            "'quoted_string'" => 'quoted_string',
            '123' => 123,
            'COUNT(id)' => 'COUNT(id)',
            'null' => 'null',
            'MAX(random_id)' => 'MAX(random_id)',
            'true' => 'true',
            'false' => 'false',
            'THIS_IS_NOT_QUOTED' => 'THIS_IS_NOT_QUOTED',
            "'THIS_IS_QUOTED'" => 'THIS_IS_QUOTED',
        ];

        // Add more to this array that would not get quoted.
        // The above array with this value will not get quoted.
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = ['THIS_IS_NOT_QUOTED'];

        // Execute
        foreach ($values as $key => $value) {
            $result = $this->testObject->quoteOrNot($value);

            $this->assertEquals($key, $result);
        }
    }

    /**
     * Test that convertTableNodeToQueries works as expected.
     */
    public function testConvertTableNodeToQueries()
    {
        // Mock.
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

        // Run.
        $queries = $this->testObject->convertTableNodeToQueries($node);

        // Assert.
        $this->assertCount(2, $queries);
        $this->assertEquals('email:its.inevitable@hotmail.com,name:Abdul', $queries[0]);
        $this->assertEquals('email:forceedge01@gmail.com,name:Qureshi', $queries[1]);
    }

    /**
     * Test that convertTableNodeToQueries works as expected.
     *
     * @expectedException Exception
     */
    public function testConvertTableNodeToQueriesNoRows()
    {
        // Mock.
        $node = new TableNode();
        // Add title row.
        $node->addRow([
            'email',
            'name'
        ]);

        // Run.
        $this->testObject->convertTableNodeToQueries($node);
    }

    /**
     * Test that convertTableNodeToSingleContextClause works as expected.
     */
    public function testConvertTableNodeToSingleContextClauseTableNode()
    {
        $node = new TableNode();
        $node->addRow([
            'title',
            'value'
        ]);
        $node->addRow([
            'email',
            'its.inevitable@hotmail'
        ]);
        $node->addRow([
            'name',
            'Abdul'
        ]);
        $node->addRow([
            'age',
            26
        ]);

        $result = $this->testObject->convertTableNodeToSingleContextClause($node);

        $this->assertEquals('email:its.inevitable@hotmail,name:Abdul,age:26', $result);
    }

    /**
     * Test that convertTableNodeToSingleContextClause works as expected.
     * 
     * @expectedException Exception
     */
    public function testConvertTableNodeToSingleContextClauseTableNodeNoData()
    {
        $node = new TableNode();
        $node->addRow([
            'title',
            'value'
        ]);

        $this->testObject->convertTableNodeToSingleContextClause($node);
    }

    /**
     * testSampleData Test that sampleData executes as expected.
     */
    public function testSampleData()
    {
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

        $types = [
            'boolean' => 'false',
            'integer' => self::INT_NUMBER,
            'double' => self::INT_NUMBER,
            'int' => self::INT_NUMBER,
            'tinyint' => self::TINY_INT_NUMBER,
            'string' => "'behat-test-string-" . self::TYPE_STRING_TIME . "'",
            'text' => "'behat-test-string-" . self::TYPE_STRING_TIME . "'",
            'varchar' => "'behat-test-string-" . self::TYPE_STRING_TIME . "'",
            'character varying' => "'behat-test-string-" . self::TYPE_STRING_TIME . "'",
            'tinytext' => "'behat-test-string-" . self::TYPE_STRING_TIME . "'",
            'char' => "'f'",
            'timestamp' => 'NOW()',
            'timestamp with time zone' => 'NOW()',
            'null' => null,
            'longtext' => "'behat-test-string-" . self::TYPE_STRING_TIME . "'",
            'randomness' => "'behat-test-string-" . self::TYPE_STRING_TIME . "'"
        ];

        // Assert
        foreach ($types as $type => $val) {
            // Execute
            $result = $this->testObject->sampleData($type);

            $this->assertEquals($val, $result);
        }
    }

    /**
     * testGetRefFromPlaceholder Test that getRefFromPlaceholder executes as expected.
     */
    public function testGetRefFromPlaceholder()
    {
        // Prepare / Mock
        $placeholder = '';

        // Execute
        $result = $this->testObject->getRefFromPlaceholder($placeholder);

        // Assert Result
        //assert
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * testGetSQLQueryForExternalReference Test that getSQLQueryForExternalReference executes as expected.
     *
     * @expectedException Exception
     */
    public function testGetSQLQueryForExternalReferenceInvalidFormatNoPipe()
    {
        // Prepare / Mock
        $externalReference = '[kjhasjkdf]';

        // Execute
        $this->testObject->getSQLQueryForExternalReference($externalReference);
    }

    /**
     * testGetSQLQueryForExternalReference Test that getSQLQueryForExternalReference executes as expected.
     *
     * @expectedException Exception
     */
    public function testGetSQLQueryForExternalReferenceInvalidFormatNoClosing()
    {
        // Prepare / Mock
        $externalReference = '[asdf|asdf:asdf';

        // Execute
        $this->testObject->getSQLQueryForExternalReference($externalReference);
    }

    /**
     * testGetSQLQueryForExternalReference Test that getSQLQueryForExternalReference executes as expected.
     *
     * @expectedException Exception
     */
    public function testGetSQLQueryForExternalReferenceInvalidFormatNoOpening()
    {
        // Prepare / Mock
        $externalReference = '[asdfasdf]';

        // Execute
        $this->testObject->getSQLQueryForExternalReference($externalReference);
    }

    /**
     * testGetSQLQueryForExternalReference Test that getSQLQueryForExternalReference executes as expected.
     *
     * @expectedException Exception
     */
    public function testGetSQLQueryForExternalReferenceInvalidFormatSimple()
    {
        // Prepare / Mock
        $externalReference = 'alhaskjdf|kljahsdf';

        // Execute
        $this->testObject->getSQLQueryForExternalReference($externalReference);
    }

    /**
     * testGetPlaceholderForRef Test that getPlaceholderForRef executes as expected.
     */
    public function testGetPlaceholderForRef()
    {
        // Prepare / Mock
        $reference = '';

        // Execute
        $result = $this->testObject->getPlaceholderForRef($reference);

        // Assert Result
        //assert
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * testReplaceExternalQueryReferences Test that replaceExternalQueryReferences executes as expected.
     */
    public function testReplaceExternalQueryReferences()
    {
        // Prepare / Mock
        $query = '';

        // Execute
        $result = $this->testObject->replaceExternalQueryReferences($query);

        // Assert Result
        //assert
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
