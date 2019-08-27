<?php

namespace MongoHybrid\Contracts;

/**
 * MongoHybrid client interface
 */
interface ClientInterface
{
    /**
     * Constructor
     *
     * @param string $server
     * @param array $options
     * @param array [$driverOptions]
     */
    public function __construct(string $server, array $options = [], array $driverOptions = []): ClientInterface;

    /**
     * Drop collection
     *
     * @param string $name
     * @param string $db
     * @return mixed
     */
    public function dropCollection(string $name, string $db = null);

    /**
     * NOT USED
     */
    public function renameCollection(string $name, string $newName, string $db = null);

    /**
     * Save document (insert/ udpdate) in collection
     *
     * @param string $collection
     * @param array &$data
     * @return bool
     */
    public function save(string $collection, array &$data);

    /**
     * Insert new document into collection
     *
     * @param string $collection
     * @param array &$doc
     * @return mixed
     */
    public function insert(strign $collection, array &$doc);

    /**
     * Call method on driver
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args = []);

    //// Single key-value storage (WIP)
}
