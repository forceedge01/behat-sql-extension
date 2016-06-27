<?php

namespace Genesis\SQLExtension\Context\Interfaces;

interface KeyStoreInterface
{
    /**
     * @param string $key
     * @param mixed $value
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
    public function getKeywordFromConfigForKeyIfExists($key);
}
