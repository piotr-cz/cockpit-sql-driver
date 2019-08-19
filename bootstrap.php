<?php
namespace SqlDriver;

use MongoHybridClientWrapper as Client;

use MongoSql\Driver\Driver;

// Set autoloader (may use composer generated if avail)
spl_autoload_register(function (string $fqcn): void {

    // Resolve path to class name in fs
    $class_path = sprintf('%s/lib/%s.php', __DIR__, str_replace('\\', '/', $fqcn));

    if (file_exists($class_path)) {
        include_once $class_path;
    }
});


/**
 * Register on bootstrap
 * @var \LimeExtra\App $this
 * @var \LimeExtra\App $app
 * @var \Lime\Module $module
 *
 * Note: classes may be autoloaded after app has booted which happens after module is booted
 */
$app->on('cockpit.bootstrap', function(): void {
    $dbConfig = $this['config']['database'];

    // Skip when server other than sqldriver
    if ($dbConfig['server'] !== Driver::SERVER_NAME) {
        // return;
    }

    // Overwrite storage in registry
    $this->set('storage', function () use ($dbConfig): Client {
        static $client = null;

        if ($client === null) {
            $client = new Client(
                $dbConfig['server'],
                $dbConfig['options'],
                $dbConfig['driverOptions']
            );
        }

        return $client;
    });

    return;
}, 1);
