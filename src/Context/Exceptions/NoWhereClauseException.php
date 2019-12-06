<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception as BaseException;
use Genesis\SQLExtension\Context\Representations\Entity;

class NoWhereClauseException extends BaseException
{
    const CODE = 0;

    /**
     * @param BaseException $e The original exception.
     */
    public function __construct(Entity $entity, BaseException $e)
    {
        $message = "Expected to have a where clause for '{$entity->getEntityName()}', Error: " . $e->getMessage();
        parent::__construct($message, self::CODE, $e);
    }
}
