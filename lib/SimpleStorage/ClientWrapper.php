<?php
declare(strict_types=1);

namespace SimpleStorage;

use SimpleStorage\Client as SimpleStorageClient;

use MongoSql\Driver\Driver;
use MongoSql\DriverException;

/**
 * Monkey patched SimpleStorage\Client
 *
 * Note: Table schema different than in collections:
 * id, document != key, keyval
 *
 * @see https://github.com/phpredis/phpredis/#keys
 */
class ClientWrapper extends SimpleStorageClient
{
    /** @var string - Collection/ Table name */
    protected $collectionName = 'memory';

    /**
     * @inheritdoc
     */
    public function __construct(string $server, array $options = [], array $driverOptions = [])
    {
        // Fall back to Cockpit drivers client
        if ($server !== Driver::SERVER_NAME) {
            parent::__construct($server, $options);

            if (!$this->driver) {
                throw new DriverException(sprintf('Could not initialize driver %s', $server));
            }

            return;
        }

        // Resolve drivers' FQCN
        $fqcn = sprintf('MongoSql\Driver\%sDriver', ucfirst($options['connection']));

        if (!class_exists($fqcn)) {
            throw new DriverException(sprintf('SQL driver for %s not found', $options['connection']));
        }


        // Create new driver
        $this->driver = new $fqcn($options, $driverOptions);
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null)
    {
        $val = $this->driver->findOne($this->collectionName, ['key' => $key]);

        if ($val === null) {
            return $default;
        }

        return $val['keyval'] ?? $default;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value): void
    {
        $doc = ['key' => $key, 'keyval' => $value];

        // Does not exist
        if ($this->get($key) === null) {
            $this->driver->insert($this->collectionName, $doc);
            return;
        }

        $this->driver->update($this->collectionName, ['key' => $key], $doc);
    }

    /**
     * @inheritdoc
     */
    public function del($key): void
    {
        $this->driver->remove($this->collectionName, ['key' => $key]);
    }
}
