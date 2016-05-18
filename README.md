# genesis-behat-sql-extension
Provides easy data manipulation with any pdo enabled database for behat 3.0

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
  suites:
    default:
      ...
      contexts:
        - FeatureContext
        - Genesis\SQLExtension\Context\SQLContext
      ...
  extensions:
    Genesis\SQLExtension\Extension:
      # Database connection details
      connection_details:
        engine: pgsql
        host: 127.0.0.1
        schema: ...
        dbname: ...
        username: ...
        password: ...
        dbprefix: ...
      # Keywords to be expanded with the sql extension steps.
      keywords:
        ...
      # Keywords that do not need quoting when querying the database.
      notQuotableKeywords:
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
Note the use of `{qwerty}` keyword. `{qwerty}` will be replaced with `thisisthehashofthepassword`.

The 'notQuotableKeywords' provide a way to specify mysql functions you do not wish to put in quotes when the SQLContext generates
the SQL query. These are expected to be regular expressions but without the delimiters. The defaults that are already set are:

```php
$keywords = [
  'true',
  'false',
  'null',
  'NOW\(\)',
  'COUNT\(.*\)',
  'MAX\(.*\)',
  '\d+'
];
```

Note: The `schema` is a very important parameter for the SQLContext, if you are working with multiple databases don't set a fixed schema. To reference a table from another database simply prefix that databases' name as per the sql convention and it will be used as your schema on the fly for that table. If you are just using one database in your application set the schema the same as the database.

###2. Environment variable

An environment variable can be set for the database connection details in the following way:

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

The fields needs to be preset but may be left empty.

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

To insert more rows at once the above statement can be re-written as follows:

```gherkin
# file: insert.feature

Given I have "@table" where:
  | column1            | column2            |
  | row1-column1-value | row1-column2-value |
  | row2-column1-value | row2-column2-value |
```

The above will insert two rows.

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
### Using the not operator.

You can use the not operator to say a column should not be equal to value as follows:
```
Then I should have a "user" with "email:its.inevitable@hotmail.com, active: !null"
```

This will generate `active is not null`. For a value other than null it would generate`column != value`.

The same can be written as:
```
Then I should have a "user" with:
    | column | value                      |
    | email  | its.inevitable@hotmail.com |
    | active | !null                      |
```
Note the top row is just explanatory, it will not be used as part of the query.

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

### Verifying data in the database

Verify the database records as follows:
```gherkin
Then I should have a "user" with "email:its.inevitable@hotmail.com,status:1"
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

```php
# file: FeatureContext.php
<?php

use Genesis\SQLExtension\Context\SQLContext;

class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * @var SQLContext
     */
    private $sqlContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->sqlContext = $environment->getContext(SQLContext::class);
    }
}
```
