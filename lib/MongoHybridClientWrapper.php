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
        // Fall back to Cockpit drivers client
        if ($server !== Driver::SERVER_NAME) {
            parent::__construct($server, $options, $driverOptions);

            // Could not initialize driver fid given server
            if (!$this->driver) {
                throw new DriverException(sprintf('Could not initialize driver %s', $server));
            }

            return;
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

    /**
     * Check if driver is subclass of given class
     * useful for feature checking, should use interfaces
     *
     * @param string $className
     * @return bool
     */
    public function driverImplements(string $className): bool
    {
        return $this->driver instanceof $className;
    }

    /**
     * Check if driver has method
     * useful for feature checking
     *
     * @param string $methodName
     * @return bool
     */
    public function driverHasMethod(string $methodName): bool
    {
        return method_exists($this->driver, $methodName);
    }
}
