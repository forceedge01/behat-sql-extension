# genesis-behat-sql-extension
Provides easy data manipulation with any pdo enabled database

Installation
------------
require with composer
```bash
require "genesis/behat-sql-extension"
```

Setup
-----
After composer has installed the extension you would need to setup the connection details. This can be done in 2 ways:

###1. Behat.yml

In addition to the usual mink-extension parameters, you can pass in a `connection_details` parameter as follows:
```yaml
default:
    extensions:
        Genesis\SQLExtension\Extension:
          goutte: ~
          ...
          # Database connection details
          connection_details:
            engine: pgsql
            host: 127.0.0.1
            schema: ...
            dbname: ...
            username: ...
            password: ...
          # Keywords to be used with the sql extension steps
          keywords:
            ...
```

In the above example, the `keywords` section provides injection of keywords. For example you can have:
```yaml
default:
    extensions:
        ...:
          ...
          keywords:
            qwerty: thisisthehashofthepassword
```

This will make the `qwerty` keyword usable as follows:

```gherkin
Given I have a "user" where "email:its.inevitable@hotmail.com,password_hash:{qwerty}"
```
In the above example note the use of `{qwerty}` keyword. `{qwerty}` will be replaced with `thisisthehashofthepassword`.

###2. Environment variable

An environment variable for the database connection details. This will essentially be a semi colon separated string like so:

```bash
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

### Inserting data in a table

This will run an insert query using the @where/@with data provided
```gherkin
# file: insert.feature

# replace @table with your table name, include schema if table is stored in a schema
# @with/@where are used synonymously in this call
Given I have a "@table" where "@where"
```

### Deleting data in a table

This will run a delete query against the database using the @where/@with criteria given
```gherkin
# file: delete.feature

# @with/@where are used synonymously in this call
Given I dont have a "@table" where "@where"
```

### Updating data in a table

This call will run an update query on the database records matching the @where clause
```gherkin
# file: update.feature

# @table for this to make sense your table should represent an entity
# @update the field you would like to update e.g email:someone@somewhere.com
# @where this functions exactly the same as the sql where clause
# Format for @update and @where is "email:its.inevitable.com,id:1,isActive:true"
Given I have an existing "@table" with "@update" where "@where"
```

### Re-using the id from another record

After creating or updating data you can assign the record's id to a keyword with the following clause
```gherkin
# file: reuse.feature

Given I have a "user" where "email:its.inevitable@hotmail.com"
And I save the id as "user_id"

# With the above command you can use "some_id" as follows
# Note the use of "some_id" keyword in the following statement
Given I have an "account" where "title:my account, user_id:{user_id}"
```

### Debug mode

Debug mode can be used to print sql queries and results to the screen for quick debugging.
```gherkin
# file: debug.feature

# Enable debug mode to check for errors
Given I am in debug mode
And I have a "user" where "email:its.inevitable@hotmail.com"
```

The above "I have" command will output something like this to the screen:
```bash
Executing SQL: INSERT INTO user (email) VALUES ('its.inevitable@hotmail.com')

Last ID fetched: 57
```

### Using SQL context with other contexts

Registering SQL context additionally to an existing context can be done as follows:

```
# file: FeatureContext.php
<?php

use Genesis\SQLExtension\Context\SQLContext;

public function __construct(array $parameters) {
    $this->parameters = $parameters;

    // Load Context Class
    $this->useContext('my_label_first_context', new SQLContext());
}
```