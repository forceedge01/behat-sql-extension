# genesis-behat-sql-extension
Generic library: Provides easy data manipulation with any PDO enabled database for Behat. Core features:

- Out of the box step definitions for simple db interactions.
- Advanced query internal resolutions for quick setup.
- Provides easy access to the entire last record manipulated from the keystore.
- Provides keyword replacement in strings by default, provides clean navigation files.
- Auto-fills required fields in a table, freeing you from the schackles of required data.
- Maintain SQL history for all queries executed for clean up later on.
- An API for advanced integration.

There are two versions maintained at the moment dependent on the version of Behat, for a more tailored README around usage of this extension please choose the appropriate branch first.

 - [behat 2.5.x](https://github.com/forceedge01/genesis-behat-sql-extension/tree/behat/2.5.x)
 - [behat 3.x](https://github.com/forceedge01/genesis-behat-sql-extension/tree/behat/3.x)

Installation
------------
require with composer
```bash
require "genesis/behat-sql-extension"
```

DB Support
----------
Using PDO library, Tested with MySQL/PostgreSQL.

Contributing to this extension
==============================

We are supporting two different versions of the sql extension at the moment. For contributing to the branch that supports behat 2.5 please branch of `behat/2.5.x`, for behat 3.0 branch off `behat/3.x`.
