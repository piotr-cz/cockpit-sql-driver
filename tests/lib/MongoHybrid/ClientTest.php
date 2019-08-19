<?php
declare(strict_types=1);

namespace Test\MongoHybrid;

use InvalidArgumentException;

use PHPUnit\Framework\TestCase;

use MongoHybridClientWrapper;

class ClientTest extends TestCase
{
    /** @var \MongoHybrid\Client - Storage client */
    protected static $storage;

    /**
     * @inheritdoc
     */
    public function setupBeforeClas(): void
    {
        $globalTestConfig = require __DIR__ . '/../config.php';

        $databaseConfig = $globalTestConfig['database'];

        // Create new storage
        static::$storage = new MongoHybridClientWrapper(
            $databaseConfig['server'],
            $databaseConfig['options'],
            $databaseConfig['driverOptions'] ?? []
        );
    }

    public function testDropCollection(): void
    {
        // Noop
        $this->assertTrue(true);
    }
}
