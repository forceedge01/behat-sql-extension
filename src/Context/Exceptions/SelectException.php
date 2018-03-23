<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception as BaseException;
use Genesis\SQLExtension\Context\Representations\Entity;

class SelectException extends BaseException
{
    const CODE = 0;

    /**
     * @param string $dataProperty The property that was not found.
     * @param BaseException $e The original exception.
     */
    public function __construct(Entity $entity, BaseException $e)
    {
        $message = "Unable to select data from table '{$entity->getEntityName()}', Error " . $e->getMessage();
        parent::__construct($message, self::CODE, $e);
    }
}
