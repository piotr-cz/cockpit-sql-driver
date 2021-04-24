<?php
declare(strict_types=1);

namespace MongoSql\Driver;

use PDO;

use MongoSql\Driver\Driver;

/**
 * Cockpit CMS MySQL Driver
 * Requires MySQL 5.7.9+ (JSON support and shorthand operators)
 *          or MariaDB 10.2.3+ (JSON support: 10.2.3, Generated columns: 10.2.6)
 */
class MysqlDriver extends Driver
{
    /** @inheritdoc */
    protected const DB_DRIVER_NAME = 'mysql';

    /** @var string - Min db server version */
    protected const DB_MIN_SERVER_VERSION = '5.7.9';
    protected const DB_MIN_SERVER_VERSION_MARIADB = '10.2.6';

    /**
     * @inheritdoc
     */
    protected static function createConnection(array $options, array $driverOptions = []): PDO
    {
        // See https://www.php.net/manual/en/ref.pdo-mysql.connection.php
        $connection = new PDO(
            vsprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', [
                $options['host'] ?? 'localhost',
                $options['port'] ?? 3306,
                $options['dbname'],
                $options['charset'] ?? 'UTF8'
            ]),
            $options['username'],
            $options['password'],
            $driverOptions + [
                // Set UTF-8 as character set and collation (Note: Setting sql_mode doesn't work in init command, at least in 5.7.26)
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;',
                // Use unbuffered query to get results one by one
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            ]
        );

        /* Set sql_mode after connection has started to ISO/IEC 9075
         * https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html
         * https://mariadb.com/kb/en/library/sql-mode/
         */
        $connection->exec("SET sql_mode = 'ANSI';");

        return $connection;
    }

    /**
     * @inheritdoc
     *
     * Version string examples:
     * - MySQL: `5.7.27-0ubuntu0.18.04.1`
     * - MariaDB: `5.5.5-10.2.26-MariaDB-1:10.2.26+maria~bionic`
     */
    protected function assertIsDbSupported(): void
    {
        parent::assertIsDbSupported();

        $currentVersion = $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
        $minVersion = static::DB_MIN_SERVER_VERSION;

        // Remove MariaDBs' MySQL compat prefix
        // See https://mariadb.atlassian.net/browse/MDEV-4088
        // Note that query `SELECT VERSION()` won't return MySQL copat prefix
        if (strpos($currentVersion, '-MariaDB') !== false) {
            [$mariaDbVersion, $mySqlCompatVersion] = explode('-', $currentVersion, 2);

            $currentVersion = $mariaDbVersion;
            $minVersion = static::DB_MIN_SERVER_VERSION_MARIADB;
        }

        static::assertIsDbVersionSupported(
            $currentVersion,
            $minVersion
        );
    }
}
