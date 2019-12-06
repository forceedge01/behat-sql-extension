<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception as BaseException;
use Genesis\SQLExtension\Context\Representations\Entity;

class UpdateException extends BaseException
{
    const CODE = 0;

    /**
     * @param string $dataProperty The property that was not found.
     * @param BaseException $e The original exception.
     */
    public function __construct(Entity $entity, BaseException $e)
    {
        $message = "Unable to update data on table '{$entity->getEntityName()}', Error " . $e->getMessage();
        parent::__construct($message, self::CODE, $e);
    }
}
