<?php

namespace Genesis\SQLExtension\Context;

class LocalKeyStore implements Interfaces\KeyStoreInterface
{
    /**
     * Sets a behat keyword.
     * 
     * @param string $key
     * @param mixed $value
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
     */
    public function getKeyword($key)
    {
        if (! isset($_SESSION['behat']['GenesisSqlExtension']['keywords'][$key])) {
            if (! isset($_SESSION['behat']['GenesisSqlExtension']['keywords'])) {
                throw new Exception('No keywords found.');
            }

            throw new Exception(sprintf(
                'Key "%s" not found in behat store, all keys available: %s',
                $key,
                print_r($_SESSION['behat']['GenesisSqlExtension']['keywords'], true)
            ));
        }

        $value = $_SESSION['behat']['GenesisSqlExtension']['keywords'][$key];

        return $value;
    }

    /**
     * Checks the value for possible keywords set in behat.yml file.
     * 
     * @param string $key
     */
    public function getKeywordFromConfigForKeyIfExists($key)
    {
        if (! isset($_SESSION['behat']['GenesisSqlExtension']['keywords'])) {
            return $value;
        }

        foreach ($_SESSION['behat']['GenesisSqlExtension']['keywords'] as $keyword => $val) {
            $key = sprintf('{%s}', $keyword);

            if ($value == $key) {
                $value = str_replace($key, $val, $value);
            }
        }

        return $value;
    }
}
