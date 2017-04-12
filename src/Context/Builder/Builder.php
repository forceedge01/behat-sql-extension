<?php

namespace Genesis\SQLExtension\Context\Builder;

use Exception;

/**
 * Builder class.
 */
abstract class Builder
{
    /**
     * @param string $value The value to check.
     * @param string $name The name of the value being passed in.
     *
     * @return $this
     */
    public function throwExceptionIfNotSet($value, $name)
    {
        if (empty($value)) {
            throw new Exception("$name must be set");
        }

        return $this;
    }
}
