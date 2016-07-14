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
    public function getKeywordFromConfigForKeyIfExists($key)
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
}
