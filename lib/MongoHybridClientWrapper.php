<?php
use MongoHybrid\Client as MongoHybridClient;

use MongoSql\Driver\Driver;
use MongoSql\DriverException;

/**
 * Monkey patched MongoHybrid client
 */
class MongoHybridClientWrapper extends MongoHybridClient
{
    /**
     * @inheritdoc
     */
    public function __construct(string $server, array $options = [], array $driverOptions = [])
    {
        // Validate support by server prefix
        if ($server !== Driver::SERVER_NAME) {
            parent::__construct($server, $options, $driverOptions);

            // Could not initialize driver fid given server
            if (!$this->driver) {
                throw new DriverException(sprintf('Could not initialize for %s', $server));
            }
        }

        // Resolve drivers' FQCN
        $fqcn = sprintf('MongoSql\Driver\%sDriver', ucfirst($options['connection']));

        if (!class_exists($fqcn)) {
            throw new DriverException(sprintf('SQL Driver for %s not found', $options['connection']));
        }

        // Create new driver
        $this->driver = new $fqcn($options, $driverOptions);

        // Set same type as MongoLite
        $this->type = 'mongolite';
    }
}
