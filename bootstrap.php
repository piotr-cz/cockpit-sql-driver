<?php
declare(strict_types=1);

namespace SqlDriver;

use MongoHybrid\ClientWrapper as MongoHybridClientWrapper;

use MongoSql\Driver\Driver;

// Set autoloader (may use composer generated if avail)
require_once __DIR__ . '/autoload.php';

$dbConfig = $app->retrieve('config/database');

// Skip when server other than sqldriver
if ($dbConfig['server'] !== Driver::SERVER_NAME) {
    return;
}

/**
 * Register on bootstrap
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
}, $dbConfig['options']['bootstrapPriority'] ?? 999);
