<?php

namespace Genesis\SQLExtension\Context;

class LocalKeyStore implements Interfaces\KeyStoreInterface
{
    /**
     * Sets a behat keyword.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setKeyword($key, $value)
    {
        $_SESSION['behat']['GenesisSqlExtension']['keywords'][$key] = $value;

        return $this;
    }

    /**
     * Fetches a specific keyword from the behat keywords store.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getKeyword($key)
    {
        if (! isset($_SESSION['behat']['GenesisSqlExtension']['keywords'][$key])) {
            if (! isset($_SESSION['behat']['GenesisSqlExtension']['keywords'])) {
                throw new Exceptions\KeywordNotFoundException();
            }

            throw new Exceptions\KeywordNotFoundException(
                $key,
                $_SESSION['behat']['GenesisSqlExtension']['keywords']
            );
        }

        $value = $_SESSION['behat']['GenesisSqlExtension']['keywords'][$key];

        return $value;
    }

    /**
     * Checks the value for possible keywords set in behat.yml file.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getKeywordIfExists($key)
    {
        if (! isset($_SESSION['behat']['GenesisSqlExtension']['keywords'])) {
            return $key;
        }

        foreach ($_SESSION['behat']['GenesisSqlExtension']['keywords'] as $keyword => $val) {
            $keyValue = sprintf('{%s}', $keyword);

            if ($key == $keyValue) {
                $key = str_replace($keyValue, $val, $key);
            }
        }

        return $key;
    }

    /**
     * Provide a string with keywords to be parsed.
     *
     * @param string $string The string to parse.
     *
     * @return string
     */
    public function parseKeywordsInString($string)
    {
        $matches = [];

        // Extract potential keywords
        preg_match_all('/({.+?})/', $string, $matches);

        if (isset($matches[0])) {
            foreach ($matches[0] as $match) {
                $string = str_replace($match, $this->getKeywordIfExists($match), $string);
            }
        }

        return $string;
    }
}
