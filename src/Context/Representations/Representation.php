<?php

namespace Genesis\SQLExtension\Context\Representations;

/**
 * Base representation class.
 */
abstract class Representation
{
    /**
     * new object of the calling class.
     *
     * @return new Static
     */
    public static function instance()
    {
        return new static();
    }
}
