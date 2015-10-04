# genesis-behat-sql-extension
Provides easy data manipulation with any pdo enabled database

Installation
------------
require with composer
```
require "genesis/behat-sql-extension"
```

Setup
-----
After composer has installed the extension you would need to setup the connection details. This can be done in 2 ways:

###1. Behat.yml

In addition to the usual mink-extension parameters, you can pass in a `connection_details` parameter as follows:
```
default:
    extensions:
        Genesis\SQLExtension\Extension:
          goutte: ~
          ...
          connection_details:
            engine: pgsql
            host: 127.0.0.1
            schema: ...
            dbname: ...
            username: ...
            password: ...
```

###2. Environment variable

An environment variable for the database connection details. This will essentially be a semi colon separated string like so:

```
$ export BEHAT_ENV_PARAMS="DBENGINE:mysql;DBHOST:127.0.0.1;DBSCH..."
```

Fields required are
```
DBENGINE
DBHOST
DBSCHEMA
DBNAME
DBUSER
DBPASSWORD
```

The field needs to be present but it may be left empty.

DB Support
----------
Tested with PostgreSQL. Expected to work with MySQL as well.


Calls provided by this extension
--------------------------------

```
// This will run an insert query using the @where/@with data provided
// @with @where are used synonymously in this call
@Given I have an? "@table" where "@where"
@Given I have an? "@table" with "@with"

// This will run a delete query against the database using the @where/@with criteria given
// @with @where are used synonymously in this call
@Given I dont have an? "@table" where "@where"
@Given I dont have an? "@table" with "@with"

// This call will run an update query on the database records matching the @where clause
// @table for this to make sense your table should represent an entity
// @update the field you would like to update e.g email:someone@somewhere.com
// @where this functions exactly the same as the sql where clause
// Format for @update and @where is "email:its.inevitable.com,id:1,isActive:true"
@Given I have an existing "@table" with "@update" where "@where"
```
