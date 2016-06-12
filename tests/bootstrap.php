<?php

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Necessary vars for testing.
DEFINE('SQLDBENGINE', 'mysql');
DEFINE('SQLDBHOST', 'localhost');
DEFINE('SQLDBSCHEMA', 'myschema');
DEFINE('SQLDBNAME', 'mydb');
DEFINE('SQLDBUSERNAME', 'root');
DEFINE('SQLDBPASSWORD', 'toor');
DEFINE('SQLDBPREFIX', 'dev_');
