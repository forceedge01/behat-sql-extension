<?php

namespace Genesis\SQLExtension\Context\Interfaces;

use Behat\Gherkin\Node\TableNode;

interface SQLContextInterface
{
    public function iHaveAWhere($entity, $columns);

    public function iDontHaveAWhere($entity, $columns);

    public function iHaveAnExistingWithWhere($entity, $with, $columns);

    public function iHaveAnExistingWhere($entity, $where);

    public function iShouldHaveAWith($entity, $with);

    public function iShouldNotHaveAWith($entity, $with);

    public function convertTableNodeToQueries(TableNode $node);

    public function iSaveTheIdAs($key);

    public function iAmInDebugMode();
}
