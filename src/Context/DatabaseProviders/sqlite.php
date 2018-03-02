<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

/**
 * sqlite class.
 */
class sqlite extends mysql
{
    /**
     * @param string $dbpath The absolute path or :memory for in memory db.
     *
     * {@inheritDoc}
     */
    public function getPdoDnsString($dbpath, $host = null, $port = null)
    {
        return "sqlite:{$dbpath}";
    }
}
