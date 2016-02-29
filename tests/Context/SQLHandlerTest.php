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

    /**
     * testSetDBParams Test that setDBParams executes as expected.
     *
     * @expectedException Exception
     */
    public function testSetDBParams()
    {
        // Execute
        $this->testObject->setDBParams();
    }

    public function testSetDBParamsFromEnvironmentVariable()
    {
        putenv('BEHAT_ENV_PARAMS=HOST: localhost;username: root;password: abc123');

        $result = $this->testObject->setDBParams();

        $this->assertInstanceOf(SQLHandler::class, $result);
        $this->assertEquals($this->testObject->getParams()['HOST'], 'localhost');
        $this->assertEquals($this->testObject->getParams()['username'], 'root');
        $this->assertEquals($this->testObject->getParams()['password'], 'abc123');
    }

    public function testSetDBParamsWithConstantsDefined()
    {
        // Mock, values are not real.
        define('SQLDBENGINE', 'mysql');
        define('SQLDBHOST', 'mysql');
        define('SQLDBSCHEMA', 'mysql');
        define('SQLDBNAME', 'mysql');
        define('SQLDBUSERNAME', 'mysql');
        define('SQLDBPASSWORD', 'mysql');

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
     * testConstructClause Test that constructClause executes as expected.
     */
    public function testConstructClause()
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
        $result = $this->testObject->constructClause($glue, $columns);

        $expected = "firstname = 'Abdul' - lastname = 'Qureshi'";

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
}
