<?php

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/London');

$_SESSION = [];

// Necessary vars for testing.
DEFINE('SQLDBENGINE', 'mysql');
DEFINE('SQLDBHOST', 'localhost');
DEFINE('SQLDBPORT', '3306');
DEFINE('SQLDBSCHEMA', 'myschema');
DEFINE('SQLDBNAME', 'mydb');
DEFINE('SQLDBUSERNAME', 'root');
DEFINE('SQLDBPASSWORD', 'toor');
DEFINE('SQLDBPREFIX', 'dev_');
