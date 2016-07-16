<?php

namespace Genesis\SQLExtension\Tests\Unit\Context;

use Genesis\SQLExtension\Context\LocalKeyStore;
use PHPUnit_Framework_TestCase;

/**
 * @group localKeyStore
 */
class LocalKeyStoreTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var object $testObject The object to be tested.
     */
    private $testObject;

    /**
     * Setup unit testing.
     */
    public function setup()
    {
        $this->testObject = new LocalKeyStore();
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
     * Throw exception if no keys are set at all.
     *
     * @expectedException \Genesis\SQLExtension\Context\Exceptions\KeywordNotFoundException
     */
    public function testGetKeywordNoKeywords()
    {
        $this->testObject->getKeyword('not_exists');
    }

    /**
     * Throw exception if keys are found but not the one that we are looking for.
     *
     * @expectedException \Genesis\SQLExtension\Context\Exceptions\KeywordNotFoundException
     */
    public function testGetKeywordNotFound()
    {
        $_SESSION['behat']['GenesisSqlExtension']['keywords']['one'] = ['the word one'];

        $this->testObject->getKeyword('not_exists');
    }

    /**
     * Test that the value is returned unchanged if key is not set.
     */
    public function testgetKeywordIfExistsNotFound()
    {
        $key = $this->testObject->getKeywordIfExists('not_exists');

        $this->assertEquals('not_exists', $key);
    }

    /**
     * Test that the value is returned as is if not a keyword.
     */
    public function testgetKeywordIfExistsFoundString()
    {
        $_SESSION['behat']['GenesisSqlExtension']['keywords']['one'] = 'the word one';

        $key = $this->testObject->getKeywordIfExists('{one}');

        $this->assertEquals('the word one', $key);
    }

    /**
     * testParseKeywordsInString Test that parseKeywordsInString executes as expected.
     */
    public function testParseKeywordsInString()
    {
        // Prepare / Mock
        $string = 'no keywords in this string';

        // Execute
        $result = $this->testObject->parseKeywordsInString($string);

        // Assert Result
        $this->assertEquals($string, $result);
    }

    /**
     * testParseKeywordsInString Test that parseKeywordsInString executes as expected.
     */
    public function testParseKeywordsInStringKeywordsPlaced()
    {
        // Prepare / Mock
        $string = 'keywords {yes} in this string {another}';
        $another = 'another keyword';
        $yes = 'yes keyword';
        $_SESSION['behat']['GenesisSqlExtension']['keywords']['another'] = $another;
        $_SESSION['behat']['GenesisSqlExtension']['keywords']['yes'] = $yes;

        // Execute
        $result = $this->testObject->parseKeywordsInString($string);

        // Assert Result
        $this->assertEquals('keywords yes keyword in this string another keyword', $result);
    }
}
