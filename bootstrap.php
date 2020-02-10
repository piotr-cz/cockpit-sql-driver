<?php
declare(strict_types=1);

namespace SqlDriver;

use MongoHybrid\ClientWrapper as MongoHybridClientWrapper;
use SimpleStorage\ClientWrapper as SimpleStorageClientWrapper;

use MongoSql\Driver\Driver;

// Set autoloader (may use composer generated if avail)
require_once __DIR__ . '/autoload.php';

$dbConfig = $app->retrieve('config/database');
$memoryConfig = $app->retrieve('config/memory');

// Skip when database server other than sqldriver
if ($dbConfig['server'] === Driver::SERVER_NAME) {
    /**
     * Register on bootstrap
     * There may be a problem when native cockpit modules try to access storage before it's been overloaded
     * For example modules/Cockpit/bootstrap.php -> webhooks.php
     *
     * @var \LimeExtra\App $this
     * @var \LimeExtra\App $app
     * @var \Lime\Module $module
     *
     * Note: classes may be autoloaded after app has booted which happens after module is booted
     */
    $app->on('cockpit.bootstrap', function () use ($dbConfig): ?bool {
        // Overwrite storage in registry
        $this->set('storage', function () use ($dbConfig): MongoHybridClientWrapper {
            static $client = null;

            if ($client === null) {
                $client = new MongoHybridClientWrapper(
                    $dbConfig['server'],
                    $dbConfig['options'],
                    $dbConfig['driverOptions']
                );
            }

            return $client;
        });

        return true;
    }, $dbConfig['options']['bootstrapPriority'] ?? 1);
}

// Skip when memory server oher than sqldriver
if ($memoryConfig['server'] === Driver::SERVER_NAME) {
    /**
     * Register on bootsttap
     */
    $app->on('cockpit.bootstrap', function () use ($memoryConfig): ?bool {
        $this->set('memory', function () use ($memoryConfig): SimpleStorageClientWrapper {
            static $client = null;

            if ($client === null) {
                $client = new SimpleStorageClientWrapper(
                    $memoryConfig['server'],
                    $memoryConfig['options'],
                    $memoryConfig['driverOptions'] ?? []
                );
            }

            return $client;
        });

        return true;
    }, $memoryConfig['options']['bootstrapPriority'] ?? 1);
}
