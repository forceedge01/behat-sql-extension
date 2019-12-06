<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception;

class KeywordNotFoundException extends Exception
{
    /**
     * @param string $keyword The keyword not found.
     * @param array $allKeywords All keywords available in store.
     */
    public function __construct($keyword = null, array $allKeywords = null)
    {
        if (! $keyword) {
            parent::__construct('No keywords found.');
        } else {
            parent::__construct(sprintf(
                'Key "%s" not found in behat store, all keys available: %s',
                $keyword,
                print_r($allKeywords, true)
            ));
        }
    }
}
