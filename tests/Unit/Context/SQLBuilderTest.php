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

use Genesis\SQLExtension\Context\SQLBuilder;
use Behat\Gherkin\Node\TableNode;
use Genesis\SQLExtension\Tests\TestHelper;

/**
 * @group sqlBuilder
 * @group unit
 */
class SQLBuilderTest extends TestHelper
{
    const TYPE_STRING_TIME = 234234234;
    const INT_NUMBER = 23423;
    const TINY_INT_NUMBER = 5;

    /**
     * @var object $testObject The object to be tested.
     */
    protected $testObject;

    /**
     * Set up the testing object.
     */
    public function setUp()
    {
        $_SESSION['behat']['GenesisSqlExtension']['keywords'] = [];
        $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

        $this->testObject = new SQLBuilder();
    }

    /**
     * Tests that constructSQLClause executes as expected with LIKE values.
     */
    public function testConstructSQLClauseLikeValues()
    {
        // Prepare / Mock
        $commandType = 'select';
        $glue = ' AND ';
        $columns = [
            'firstname' => 'Bob',
            'user_agent' => '%Firefox%'
        ];

        // Execute
        $result = $this->testObject->constructSQLClause($commandType, $glue, $columns);

        $expected = "`firstname` = 'Bob' AND `user_agent` LIKE '%Firefox%'";

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
        $expected = "`firstname` is null AND `lastname` is not null AND `postcode` is not NULL AND `address` is NULL";
        // Assert Result
        $this->assertEquals($expected, $result);

        // Execute
        $result = $this->testObject->constructSQLClause('update', ' AND ', $columns);
        $expected = "`firstname` is null AND `lastname` is not null AND `postcode` is not NULL AND `address` is NULL";
        // Assert Result
        $this->assertEquals($expected, $result);

        // Execute
        $result = $this->testObject->constructSQLClause('delete', ' AND ', $columns);
        $expected = "`firstname` is null AND `lastname` is not null AND `postcode` is not NULL AND `address` is NULL";
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

        $expected = "`firstname` != 'Abdul' - `lastname` = 'Qureshi'";

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

        $expected = "`firstname` = 'Abdul' - `lastname` = 'Qureshi'";

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
    public function testGetRefFromPlaceholderInvalidPlaceholder()
    {
        // Prepare / Mock
        $placeholder = '';

        // Execute
        $result = $this->testObject->getRefFromPlaceholder($placeholder);

        // Assert Result
        $this->assertFalse($result);
    }

    /**
     * testGetRefFromPlaceholder Test that getRefFromPlaceholder executes as expected.
     */
    public function testGetRefFromPlaceholderNotFound()
    {
        // Prepare / Mock
        $placeholder = 'placeholder_user.id';

        // Execute
        $result = $this->testObject->getRefFromPlaceholder($placeholder);

        // Assert Result
        $this->assertFalse($result);
    }

    /**
     * testGetRefFromPlaceholder Test that getRefFromPlaceholder executes as expected.
     */
    public function testGetRefFromPlaceholderFound()
    {
        $this->accessProperty('refs')->setValue(
            $this->testObject,
            ['user.id' => 123]
        );

        // Prepare / Mock
        $placeholder = 'ext-ref-placeholder_user.id';

        // Execute
        $result = $this->testObject->getRefFromPlaceholder($placeholder);

        // Assert Result
        $this->assertEquals(123, $result);
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
     * testGetSQLQueryForExternalReference Test that getSQLQueryForExternalReference executes as expected.
     *
     * @expectedException Exception
     */
    public function testGetSQLQueryForExternalReferenceValidFormatNoColon()
    {
        // Prepare / Mock
        $externalReference = '[alhaskjdf|kljahsdf]';

        // Execute
        $this->testObject->getSQLQueryForExternalReference($externalReference);
    }

    /**
     * testGetSQLQueryForExternalReference Test that getSQLQueryForExternalReference executes as expected.
     *
     * @expectedException Exception
     */
    public function testGetSQLQueryForExternalReferenceInvalidFormatColon()
    {
        // Prepare / Mock
        $externalReference = '[alhaskjdf|kljahsdf:]';

        // Execute
        $this->testObject->getSQLQueryForExternalReference($externalReference);
    }

    /**
     * testGetSQLQueryForExternalReference Test that getSQLQueryForExternalReference executes as expected.
     */
    public function testGetSQLQueryForExternalReferenceValid()
    {
        // Prepare / Mock
        $reference = '[user.id|email: abdul@easyfundraising.org.uk, status: 1]';

        // Execute
        $result = $this->testObject->getSQLQueryForExternalReference($reference);

        // Assert Result
        $expectedSQL = "SELECT id FROM user WHERE `email` = 'abdul@easyfundraising.org.uk' AND `status` = 1";

        $this->assertEquals($expectedSQL, $result);
    }

    /**
     * testGetSQLQueryForExternalReference Test that getSQLQueryForExternalReference executes as expected.
     */
    public function testGetSQLQueryForExternalReferenceValidWithDBName()
    {
        // Prepare / Mock
        $reference = '[mydb.user.id|email: abdul@easyfundraising.org.uk, status: 1]';

        // Execute
        $result = $this->testObject->getSQLQueryForExternalReference($reference);

        // Assert Result
        $expectedSQL = "SELECT id FROM mydb.user WHERE `email` = 'abdul@easyfundraising.org.uk' AND `status` = 1";

        $this->assertEquals($expectedSQL, $result);
    }

    /**
     * testGetPlaceholderForRef Test that getPlaceholderForRef executes as expected.
     */
    public function testGetPlaceholderForRef()
    {
        // Prepare / Mock
        $reference = '[user.id|email:!abdul@easyfundraising.org.uk]';
        $reference2 = '[user.id|email:!abdul@easyfundraising.org.uk, status:1]';

        // Execute
        $result = $this->accessMethod('getPlaceholderForRef')->invokeArgs($this->testObject, [$reference]);
        $result1 = $this->accessMethod('getPlaceholderForRef')->invokeArgs($this->testObject, [$reference]);
        $result2 = $this->accessMethod('getPlaceholderForRef')->invokeArgs($this->testObject, [$reference2]);

        // Assert Result
        $this->assertEquals('ext-ref-placeholder_0', $result);
        $this->assertEquals('ext-ref-placeholder_0', $result1);
        $this->assertEquals('ext-ref-placeholder_2', $result2);
    }

    /**
     * testParseExternalQueryReferences Test that parseExternalQueryReferences executes as expected.
     */
    public function testParseExternalQueryReferences()
    {
        // Prepare / Mock
        $query = 'company_id: [company.id|name: best], username: forceedge, status_id: [status.id|state: active]';

        // Execute
        $result = $this->testObject->parseExternalQueryReferences($query);

        // Assert Result
        $expectedQuery = 'company_id: ext-ref-placeholder_0, username: forceedge, status_id: ext-ref-placeholder_1';

        //assert
        $this->assertEquals($expectedQuery, $result);
    }

    /**
     * testparseExternalQueryReferences Test that parseExternalQueryReferences executes as expected.
     */
    public function testParseExternalQueryReferencesNoRefs()
    {
        // Prepare / Mock
        $query = 'company_id: 123, username: forceedge, status_id: 3';

        // Execute
        $result = $this->testObject->parseExternalQueryReferences($query);

        // Assert Result
        $expectedQuery = 'company_id: 123, username: forceedge, status_id: 3';

        //assert
        $this->assertEquals($expectedQuery, $result);
    }
}
