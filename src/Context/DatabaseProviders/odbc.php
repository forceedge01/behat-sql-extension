<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

/**
 * odbc class, uses FreeTDS to connect to the database.
 */
class odbc extends dblib
{
    /**
     * To connect via the odbc driver, you will need to setup:
     * odbc.ini: Define a connection to a Microsoft SQL server.
     * odbcinst.ini: Define where to find the driver for the Free TDS connections.
     * freetds.conf: Define a connection to the Microsoft SQL Server.
     *
     * The host and port param are not used, all config defined in the above file will be used.
     */
    public function getPdoDnsString($dsnRererence, $host, $port = null)
    {
        return "odbc:DRIVER=FreeTDS;SERVERNAME=$dsnRererence;";
    }
}
