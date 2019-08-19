# SQL Driver for Cockpit CMS (next)

This addon allows to use MySQL or PostgreSQL databases instead of default Mongo/ SQLite.


## Requirements

- MySQL 5.7.9/ MariaDB 10.2.3/ PostgreSQL 9.4
- PHP 7.1
- PHP extensions: *pdo*, *pdo_mysql*/ *pdo_pgsql*


## Installation


### Manual

Download [latest release](https://github.com/piotr-cz/cockpit-sql-driver/releases/latest) and place in under `cockpit/addons/SqlDriver` folder


### Using composer

1. Make sure path to cockpit addons are defined in composer.json

  ```json
  "extra": {
      "installer-paths": {
          "public/cockpit/addons/{$name}": ["type:cockpit-module"]
      }
  }
  ```

2. Install addon using composer
  ```sh
  composer require piotr-cz/cockpit-sql-driver
  ```


## Configuration

Using `config/config.php` file:

```php
return [
    'database' => [
        'server' => 'sqldriver',
        'options' => [
            'connection' => 'mysql'      // One of 'mysql'|'pgsql'
            'host'       => 'localhost', // Optional, defaults to 'localhost'
            'port'       => 3306,        // Optional, defaults to 3306 for MySQL and 5432 for PostgreSQL
            'dbname'     => 'DATABASE_NAME',
            'username'   => 'USER',
            'password'   => 'PASSWORD'
        ],
        // PDO Attributes
        // General https://www.php.net/manual/en/pdo.setattribute.php
        // MySQL https://www.php.net/manual/en/ref.pdo-mysql.php#pdo-mysql.constants
        'driverOptions' => [],
    ]
]
```


## Database data migration (Cockpit 0.6.0+)

1. Export data
   ``` sh
   php cp export --target migration
   ```
2. Switch database to _sqldriver_ (See [#configuration])
3. Import data
   ```sh
   php cp import --src migration
   ```

Reference: [CLI]](https://getcockpit.com/documentation/reference/CLI)


## Testing

1. Install dependencies
   ```sh
   cd cockpit/addons/SqlDriver
   composer install --no-plugins
   ```

2. Configure test database: Copy `/tests/conifg.php.dist` to `/tests/config.php` and configure as in [#configuration]

3. Run phpunit
   ```sh
   ./vendor/bin/phpunit
   ```


## Drawbacks

_TODO_

## Manual database optimisations

_TODO_


## Copyright and license

Copyright 2019 Piotr Konieczny under the MIT license.