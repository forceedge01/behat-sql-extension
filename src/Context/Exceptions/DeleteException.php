<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception as BaseException;
use Genesis\SQLExtension\Context\Representations\Entity;

class DeleteException extends BaseException
{
    const CODE = 0;

    /**
     * @param string $table The property that was not found.
     * @param BaseException $e The original exception.
     * @param mixed $table
     */
    public function __construct(Entity $entity, BaseException $e)
    {
        $message = "Unable to delete data from table '{$entity->getEntityName()}', Error: " . $e->getMessage();
        parent::__construct($message, self::CODE, $e);
    }
}
