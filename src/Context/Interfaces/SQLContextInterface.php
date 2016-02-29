<?php

namespace Genesis\SQLExtension\Context\Interfaces;

interface SQLContextInterface
{
    public function iHaveAWhere($entity, $columns);

    public function iDontHaveAWhere($entity, $columns);

    public function iHaveAnExistingWithWhere($entity, $with, $columns);

    public function iShouldHaveAWith($entity, $with);

    public function iShouldNotHaveAWith($entity, $with);

    public function iSaveTheIdAs($key);

    public function iAmInDebugMode();
}
