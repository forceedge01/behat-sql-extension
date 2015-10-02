# genesis-behat-sql-extension
Provides easy data manipulation with any pdo enabled database

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
