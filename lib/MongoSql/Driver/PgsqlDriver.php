<?php
declare(strict_types=1);

namespace MongoSql\Driver;

use PDO;

use MongoSql\Driver\Driver;

/**
 * PostgreSQL Driver
 */
class PgsqlDriver extends Driver
{
    /** @inheritdoc */
    protected const DB_DRIVER_NAME = 'pgsql';

    /**
     * Min db server version
     * extended json added in 9.3
     * jsonb added in 9.4
     */
    protected const DB_MIN_SERVER_VERSION = '9.5';

    /**
     * @inheritdoc
     */
    protected function createConnection(array $options, array $driverOptions = []): PDO
    {
        // See https://www.php.net/manual/en/ref.pdo-pgsql.connection.php
        return new PDO(
            vsprintf("pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s;options='--client-encoding=%s'", [
                $options['host'] ?? 'localhost',
                $options['port'] ?? 5432,
                $options['dbname'],
                $options['username'],
                $options['password'],
                $options['charset'] ?? 'UTF8'
            ]),
            null,
            null,
            $driverOptions
        );
    }

    /**
     * @inheritdoc
     */
    protected function assertIsDbSupported(): void
    {
        parent::assertIsDbSupported();

        // Check version
        static::assertIsDbVersionSupported(
            $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION),
            static::DB_MIN_SERVER_VERSION
        );
    }
}
