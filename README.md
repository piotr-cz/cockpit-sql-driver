# SQL Driver for Cockpit CMS

[![Latest Version](https://img.shields.io/packagist/v/piotr-cz/cockpit-sql-driver?style=flat-square&sort=semver)](https://packagist.org/packages/piotr-cz/cockpit-sql-driver)
[![Build status](https://img.shields.io/travis/piotr-cz/cockpit-sql-driver?style=flat-square)](https://travis-ci.org/piotr-cz/cockpit-sql-driver)

This addon allows to use MySQL/ MariaDB/ PostgreSQL databases instead of default Mongo/ SQLite.


## Requirements

- Cockpit CMS (next or legacy)
- MySQL 5.7.9/ MariaDB 10.2.6/ PostgreSQL 9.4
- PHP 7.1
- Enabled PHP extensions: *pdo*, *pdo_mysql*/ *pdo_pgsql*


### Compatibility table

 Cockpit version    | Addon version
------------------- | -------------
 `>=0.9.3 <=0.10.0` | [1.0.0-beta.2](https://github.com/piotr-cz/cockpit-sql-driver/releases/tag/v1.0.0-beta.2)
 `<0.9.3`           | [1.0.0-beta.1](https://github.com/piotr-cz/cockpit-sql-driver/releases/tag/v1.0.0-beta.1)


## Installation

_Note:_

If you installed addon before ever starting Cockpit, [some errors](#error-call-to-a-member-function-toarray-on-null) may come up once you start it.

To solve it, start Cockpit with database configuration it supports [out of the box](https://getcockpit.com/documentation/reference/configuration) to trigger Cockpit warmup and then set [configuration](#configuration) specific for this addon.


### Manual

Download [latest release](https://github.com/piotr-cz/cockpit-sql-driver/releases/latest) and extract to `COCKPIT_PATH/addons/SqlDriver` directory


### Using Cockpit CLI _(development version)_

```sh
./cp install/addon --name SqlDriver --url https://github.com/piotr-cz/cockpit-sql-driver/archive/master.zip
```


### Using Composer

1. Make sure path to cockpit addons are defined in your projects' _composer.json_ file

   ```json
   {
       "name": "MY_PROJECT",
       "extra": {
           "installer-paths": {
               "public/cockpit/addons/{$name}": ["type:cockpit-module"]
           }
       }
   }
   ```

2. In your project root run command

   ```sh
   composer require piotr-cz/cockpit-sql-driver
   ```


## Configuration

Example configuration for `COCKPIT_PATH/config/config.php` file:

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

_Rererence: Cockpit docs > [Configuration](https://getcockpit.com/documentation/reference/configuration)_


## Database content migration (Cockpit v0.6.0+)

1. Export data to `COCKPIT_PATH/migration` subdirectory

   ```sh
   mkdir migration
   ./cp export --target migration
   ```

2. Switch database to _sqldriver_ (see [Configuration](#configuration))

3. Import data from `COCKPIT_PATH/migration` subdirectory

   ```sh
   ./cp import --src migration
   rm -rf migration
   ```

_Reference: Cockpit docs > [CLI](https://getcockpit.com/documentation/reference/CLI)_


## Testing

There are integration tests included in the package.
These require Cockpit CMS as a dev dependency and use it's _MongoHybrid\Client_ API to run actions on database.

To run tests

1. Install dependencies

   ```sh
   cd COCKPIT_PATH/addons/SqlDriver
   composer install
   ```

2. Configure test database

   copy [`/phpunit.xml.dist`](./phpunit.xml.dist) to `/phpunit.xml` and set up variables as in [configuration](#configuration)

3. Run tests with PHPUnit

   ```sh
   ./vendor/bin/phpunit
   ```


## Drawbacks

Cockpit doesn't provide public API to register custom database drivers so this addon monkey-patches Cockpit Driver selector client (_MongoHybrid Client_).
This means that there is no guarantee that this addon will work in future versions of Cockpit.


### Collection filters

#### Not implemented

- `$func`/ `$fn`/ `$f`

- `$fuzzy`


#### Work differently

- callable

  [Unlike SQLite](https://www.php.net/manual/en/pdo.sqlitecreatefunction.php), PDO MySQL and PostgreSQL drivers don't have support for User Defined Functions in PHP language - so callable is evaluated on every result fetch.
  If you have lots of documents in collection and care about performance use other filters.

- `$in`, `$nin`

  When database value is an array, evaluates to false.

- `$regexp`
  - MySQL implemented via [REGEXP](https://dev.mysql.com/doc/refman/5.7/en/regexp.html) + case insensitive
  - PostgreSQL impemeted via [POSIX Regular Expressions](https://www.postgresql.org/docs/9.4/functions-matching.html#FUNCTIONS-POSIX-REGEXP) + case insensitive

  Wrapping expression in `//` or adding flags like `/foobar/i` won't work, as MySQL and PosgreSQL Regexp functions don't support flags.

- `$text`
  - MySQL implemeted via [LIKE](https://dev.mysql.com/doc/refman/5.7/en/string-comparison-functions.html#operator_like)
  - PostgreSQL implementad via [LIKE](https://www.postgresql.org/docs/9.4/functions-matching.html#FUNCTIONS-LIKE)

  Filter options are not supported (_$minScore_, _$distance_, _$search_).


## Manual database optimisations

By default package creates virtual column `_id` with unique index on every created collection.

If you would like to speed up filters on other collection fields - add virtual column with suitable index and type.

- MySQL:

  ```sql
  ALTER TABLE
      `{$tableName}` ADD COLUMN `{$fieldName}_virtual` INT AS (`document` ->> '$.{$fieldName}') NOT NULL,
      ADD UNIQUE | KEY `idx_{$tableName}_{$fieldName}` (`{$fieldName}_virtual`);
  ```

  _Reference: MySQL 5.7 > [CREATE INDEX](https://dev.mysql.com/doc/refman/5.7/en/create-index.html)_

- PosgreSQL:

  ```sql
  CREATE [UNIQUE] INDEX "idx_{$tableName}_{$fieldName}" ON "{$tableName}" ((("document" ->> '{$fieldName}')::int));
  ```

  _Reference: PostgreSQL 9.4 > [CREATE INDEX](https://www.postgresql.org/docs/9.4/sql-createindex.html)_


## Known issues

### Error: `Call to a member function toArray() on null`

This happens when starting cockpit for the first time and this addon is installed.
The reason is in that native Cockpit modules try to accesss storage which is initialized later (during custom modules bootstrap).

Cockpit must be started for the first time without being configured to use SQL driver.

**Solution 1**
Start Cockpit with database configuration it supports out of the box and than switch to `sqldriver` as described [here](#configuration)

**Solution 2**
Manually create file `COCKPIT_STORAGE_FOLDER/tmp/webhooks.cache.php` with content

```php
<?php return [];
```


### Composer installation fails _(Plugin installation failed, rolling back)_

Related to [VirtualBox issue](https://github.com/laravel/homestead/issues/1240)

Use composer `--no-plugins` option in install/ require


## Copyright and license

Copyright since 2019 Piotr Konieczny under the MIT license.
