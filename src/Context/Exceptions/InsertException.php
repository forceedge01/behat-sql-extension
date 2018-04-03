<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception as BaseException;
use Genesis\SQLExtension\Context\Representations\Entity;

class InsertException extends BaseException
{
    const CODE = 0;

    /**
     * @param string $dataProperty The property that was not found.
     * @param BaseException $e The original exception.
     */
    public function __construct(Entity $entity, BaseException $e)
    {
        $message = "Unable to insert data into table '{$entity->getEntityName()}', Error " .
            $e->getMessage() .
            ", Entity: " .
            print_r($entity, true);

        parent::__construct($message, self::CODE, $e);
    }
}
