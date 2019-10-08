<?php

namespace Genesis\SQLExtension\Context\Interfaces;

interface KeyStoreInterface
{
    /**
     * Reset the local store to initial state.
     *
     * @return void
     */
    public function reset();

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setKeyword($key, $value);

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getKeyword($key);

    /**
     * @param string $key
     *
     * @return boolean
     */
    public function getKeywordIfExists($key);

    /**
     * Provide a string with keywords to be parsed.
     *
     * @param string $string The string to parse.
     *
     * @return string
     */
    public function parseKeywordsInString($string);
}
