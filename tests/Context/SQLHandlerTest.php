<?php

namespace Genesis\SQLExtension\Context;

// Mock pdo class for testing.
class PDO
{
    private $dns;
    private $username;
    private $password;

    public function __construct($dns, $username, $password)
    {
        $this->dns = $dns;
        $this->username = $username;
        $this->password = $password;
    }

    public function getDns()
    {
        return $this->dns;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }
}

// Mock rand method for testing.
function rand($min = null, $max = null)
{
    if ($min == null && $max == null) {
        return \Genesis\SQLExtension\Tests\Context\SQLHandlerTest::INT_NUMBER;
    }

    return \Genesis\SQLExtension\Tests\Context\SQLHandlerTest::TINY_INT_NUMBER;
}

// Mock time method for testing.
function time()
{
    return \Genesis\SQLExtension\Tests\Context\SQLHandlerTest::TYPE_STRING_TIME;
}

namespace Genesis\SQLExtension\Tests\Context;

use Behat\Gherkin\Node\TableNode;
use Genesis\SQLExtension\Context\SQLHandler;
use PHPUnit_Framework_TestCase;

class SQLHandlerTest extends PHPUnit_Framework_TestCase
{
    const TYPE_STRING_TIME = 234234234;
    const INT_NUMBER = 23423;
    const TINY_INT_NUMBER = 5;

    private $testObject;

    public function __construct()
    {
        ini_set('error_reporting', E_ALL | E_STRICT);
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 'On');
        $this->testObject = new SQLHandler();
    }

    public function testSetDBParamsFromEnvironmentVariable()
    {
        putenv('BEHAT_ENV_PARAMS=HOST: localhost;username: root;password: abc123');

        $result = $this->testObject->setDBParams();

        $this->assertInstanceOf(SQLHandler::class, $result);
        $this->assertEquals($this->testObject->getParams()['DBENGINE'], 'mysql');
        $this->assertEquals($this->testObject->getParams()['DBHOST'], 'localhost');
        $this->assertEquals($this->testObject->getParams()['DBUSER'], 'root');
        $this->assertEquals($this->testObject->getParams()['DBPASSWORD'], 'toor');
        $this->assertEquals($this->testObject->getParams()['DBPREFIX'], 'dev_');
        $this->assertEquals($this->testObject->getParams()['DBNAME'], 'mydb');
        $this->assertEquals($this->testObject->getParams()['DBSCHEMA'], 'myschema');
    }

    public function testSetDBParamsWithConstantsDefined()
    {
        $result = $this->testObject->setDBParams();

        $this->assertInstanceOf(SQLHandler::class, $result);
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
        $result = $this->testObject->constructSQLClause($glue, $columns);

        $expected = "firstname = 'Abdul' - lastname = 'Qureshi'";

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
        $result = $this->testObject->constructSQLClause($glue, $columns);

        $expected = "firstname != 'Abdul' - lastname = 'Qureshi'";

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
        $this->testObject->setClauseType('select');
        $result = $this->testObject->constructSQLClause(' AND ', $columns);
        $expected = "firstname is null AND lastname is not null AND postcode is not NULL AND address is NULL";
        // Assert Result
        $this->assertEquals($expected, $result);

        // Execute
        $this->testObject->setClauseType('update');
        $result = $this->testObject->constructSQLClause(' AND ', $columns);
        $expected = "firstname is null AND lastname is not null AND postcode is not NULL AND address is NULL";
        // Assert Result
        $this->assertEquals($expected, $result);

        // Execute
        $this->testObject->setClauseType('delete');
        $result = $this->testObject->constructSQLClause(' AND ', $columns);
        $expected = "firstname is null AND lastname is not null AND postcode is not NULL AND address is NULL";
        // Assert Result
        $this->assertEquals($expected, $result);
    }

    /**
     * testFilterAndConvertToArray Test that filterAndConvertToArray executes as expected.
     */
    public function testFilterAndConvertToArray()
    {
        // Prepare / Mock
        $columns = 'one:1, two:2, three:ransom, four:{keyword}';

        // Set the keyword value.
        $this->testObject->setKeyword('keyword', '123123');

        // Execute
        $this->testObject->filterAndConvertToArray($columns);

        // Get the columns set by previous call.
        $result = $this->testObject->getColumns();

        // Assert Result
        $this->assertEquals($result['one'], 1);
        $this->assertEquals($result['two'], 2);
        $this->assertEquals($result['three'], 'ransom');
        $this->assertEquals($result['four'], '123123');
    }

    /**
     * testSetKeyword Test that setKeyword executes as expected.
     */
    public function testSetAndGetKeyword()
    {
        $keyword = 'key';
        $value = 'this should be saved and retrieved';

        // Execute.
        $this->testObject->setKeyword($keyword, $value);

        // Get the keyword out.
        $result = $this->testObject->getKeyword($keyword);

        // Assert Result.
        $this->assertEquals($value, $result);
        $this->assertEquals($value, $_SESSION['behat']['GenesisSqlExtension']['keywords'][$keyword]);
    }

    /**
     * testDebugLog Test that debugLog executes as expected.
     */
    public function testDebugLog()
    {
        define('DEBUG_MODE', 1);

        // Start capturing the output to the screen.
        ob_start();

        // Message that is expected to be outputted.
        $msg = 'This is a message';

        // Execute.
        $this->testObject->debugLog($msg);

        // Output debug information.
        $log = ob_get_clean();

        // Assert Result
        $this->assertContains($msg, $log);
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
     * testGetKeyFromDuplicateError Test that getKeyFromDuplicateError executes as expected.
     */
    public function testGetKeyFromDuplicateErrorNoError()
    {
        // Prepare / Mock
        $error = ['', '', null];

        // Execute
        $result = $this->testObject->getKeyFromDuplicateError($error);

        // Assert Result
        $this->assertFalse($result);
    }

    /**
     * testGetKeyFromDuplicateError Test that getKeyFromDuplicateError executes as expected.
     */
    public function testGetKeyFromDuplicateErrorDuplicateError()
    {
        // Prepare / Mock
        $error = ['', '', 'error DETAIL: Key asdf=123 already exists.'];

        // Execute
        $result = $this->testObject->getKeyFromDuplicateError($error);

        // Assert Result
        $this->assertEquals('asdf', $result);
    }

    /**
     * testGetKeyFromDuplicateError Test that getKeyFromDuplicateError executes as expected.
     */
    public function testGetKeyFromDuplicateErrorOtherError()
    {
        // Prepare / Mock
        $error = ['', '', 'This is an unknown error.'];

        // Execute
        $result = $this->testObject->getKeyFromDuplicateError($error);

        // Assert Result
        $this->assertFalse($result);
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
     * @expectedException \Exception
     */
    public function testThrowErrorsIfNoRowsAffectedNoRowsAffected()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlStatementMock->expects($this->any())
            ->method('rowCount')
            ->willReturn(0);

        $this->testObject->throwErrorIfNoRowsAffected($sqlStatementMock);
    }

    /**
     * Check if the error is returned when one is found and is a duplicate error.
     */
    public function testThrowErrorsIfNoRowsAffectedDuplicateError()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlStatementMock->expects($this->any())
            ->method('rowCount')
            ->willReturn(0);

        $sqlStatementMock->expects($this->exactly(2))
            ->method('errorInfo')
            ->willReturn([0 => 'Duplicate error key=asdf']);

        $result = $this->testObject->throwErrorIfNoRowsAffected(
            $sqlStatementMock,
            true
        );

        $this->assertContains('Duplicate', $result[0]);
    }

    /**
     * Check if false is returned when rows are affected.
     */
    public function testThrowErrorsIfNoRowsAffectedNoException()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlStatementMock->expects($this->any())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->testObject->throwErrorIfNoRowsAffected($sqlStatementMock);

        $this->assertFalse($result);
    }

    /**
     * @expectedException \Exception
     */
    public function testThrowExceptionIfErrorsWithErrors()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlStatementMock->expects($this->once())
            ->method('errorCode')
            ->willReturn(234);

        $this->testObject->throwExceptionIfErrors($sqlStatementMock);
    }

    /**
     * Check if the method returns false if no errors are found.
     */
    public function testThrowExceptionIfErrorsNoErrors()
    {
        $sqlStatementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        // This is the error code returned by mysql if no errors have occurred.
        $sqlStatementMock->expects($this->once())
            ->method('errorCode')
            ->willReturn('00000');

        $result = $this->testObject->throwExceptionIfErrors($sqlStatementMock);

        $this->assertFalse($result);
    }

    /**
     * Test that this method works as expected.
     */
    public function testMakeSQLSafe()
    {
        $string = 'databaseName.tableName.more';

        $result = $this->testObject->makeSQLSafe($string);

        $this->assertEquals('databaseName.tableName.more', $result);
    }

    /**
     * Test that this method works as expected.
     */
    public function testMakeSQLUnsafe()
    {
        $string = '`databaseName`.`tableName`.`more`';

        $result = $this->testObject->makeSQLUnsafe($string);

        $this->assertEquals('databaseName.tableName.more', $result);
    }

    /**
     * Test that the entity can be set using the setter.
     */
    public function testSetEntity()
    {
        $this->testObject->setEntity('abc');

        $this->assertEquals('dev_abc', $this->testObject->getEntity());
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
     * Test that setClauseType works as expected.
     *
     * @expectedException Exception
     */
    public function testSetClauseType()
    {
        $this->testObject->setClauseType('random');
    }

    /**
     * Test that setClauseType works as expected.
     */
    public function testSetClauseTypeWithValidValues()
    {
        $clauseTypes = ['update', 'insert', 'select', 'delete'];

        foreach ($clauseTypes as $clauseType) {
            $this->testObject->setClauseType($clauseType);

            $this->assertEquals($clauseType, $this->testObject->getClauseType());
        }
    }
}
