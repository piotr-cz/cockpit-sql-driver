# SQL Driver for Cockpit CMS (next/ legacy)

This addon allows to use MySQL/ MariaDB/ PostgreSQL databases instead of default Mongo/ SQLite.


## Requirements
- Cockpit
- MySQL 5.7.9/ MariaDB 10.2.3/ PostgreSQL 9.4
- PHP 7.1
- PHP extensions: *pdo*, *pdo_mysql*/ *pdo_pgsql*


## Installation


### Manual

Download [latest release](https://github.com/piotr-cz/cockpit-sql-driver/releases/latest) and place in under `cockpit/addons/SqlDriver` directory


### Using composer

1. Make sure path to cockpit addons are defined in composer.json

   ```json
   {
       "name": "my-project",
       "extra": {
           "installer-paths": {
               "public/cockpit/addons/{$name}": ["type:cockpit-module"]
           }
       }
   }
   ```

2. Install addon using composer
   ```sh
   composer require piotr-cz/cockpit-sql-driver
   ```


## Configuration

Example configuration for `/config/config.php` file:

```php
<?php
return [
    # Cockpit configuration
    # …

    # Use SQL Driver as main data storage
    'database' => [
        'server' => 'sqldriver',
        # Connection options
        'options' => [
            'connection' => 'mysql'          # One of 'mysql'|'pgsql'
            'host'       => 'localhost',     # Optional, defaults to 'localhost'
            'port'       => 3306,            # Optional, defaults to 3306 (MySQL) or 5432 (PostgreSQL)
            'dbname'     => 'DATABASE_NAME',
            'username'   => 'USER',
            'password'   => 'PASSWORD',
            'charset'    => 'UTF8'           # Optional, defaults to 'UTF8'
        ],
        # Connection specific options
        # General: https://www.php.net/manual/en/pdo.setattribute.php
        # MySQL specific: https://www.php.net/manual/en/ref.pdo-mysql.php#pdo-mysql.constants
        'driverOptions' => [],
    ],
];
```


Rererence: Cockpit docs > [Configuration](https://getcockpit.com/documentation/reference/configuration)


## Database data migration (Cockpit 0.6.0+)

1. Export data to `/migration` dir
   ``` sh
   ./cp export --target migration
   ```
2. Switch database to _sqldriver_ (see [Configuration](#configuration))
3. Import data from `/migration` dir
   ```sh
   ./cp import --src migration
   ```

Reference: Cockpit docs > [CLI](https://getcockpit.com/documentation/reference/CLI)


## Testing

1. Install dependencies [with --no-plugins](https://github.com/composer/installers/issues/430)
   ```sh
   cd cockpit/addons/SqlDriver
   composer install --no-plugins
   ```

2. Configure test database: copy [`/phpunit.xml.dist`](./phpunit.xml.dist) to `/phpunit.xml` and set up variables as in [configuration](#configuration)

3. Run tests with PHPUnit
   ```sh
   ./vendor/bin/phpunit
   ```


## Drawbacks

Cockpit doesn't provide public API to register custom databse drivers so this module monkey-patches cockpit Driver selector client.
This means that there is guarantee that this addon will work in future versions of Cockpit.

### Collection filters

#### Not implemented

- `$func`/ `$fn`/ `$f`
- `$fuzzy`

#### Works differently

- callable

  [unlike SQLite](https://www.php.net/manual/en/pdo.sqlitecreatefunction.php) PDO MySQL driver doesn't have support for User Defined Functions in php language so callable is evaluated on every result fetch

- `$in`, `$nin`

  when databse value is an array, evaluates to false

- `$regexp`
  - MySQL implemented via [REGEXP](https://dev.mysql.com/doc/refman/5.7/en/regexp.html) + case insensitive
  - PostgreSQL impemeted via [POSIX Regular Expressions](https://www.postgresql.org/docs/9.4/functions-matching.html#FUNCTIONS-POSIX-REGEXP) + case insensitive

  wrapping expression in `//` or adding flags like `/foobar/i` doesn't work, as MySQL and PosgreSQL Regexp functions don't support flags

- `$text`
  - MySQL implemeted via [LIKE](https://dev.mysql.com/doc/refman/5.7/en/string-comparison-functions.html#operator_like)
  - PostgreSQL implementad via [LIKE](https://www.postgresql.org/docs/9.4/functions-matching.html#FUNCTIONS-LIKE)

  options are not supported (_$minScore_, _$distance_, _$search_)

## Manual database optimisations

_TODO_


## Copyright and license

Copyright 2019 Piotr Konieczny under the MIT license.