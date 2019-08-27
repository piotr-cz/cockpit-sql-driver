<?php

namespace MongoHybrid\Contracts;

interface ClientInterface
{
    public function __construct(string $server, array $options = [], array $driverOptions = []): ClientInterface;

    public function dropCollection(string $name, string $db = null);

    /**
     * NOT USED
     */
    public function renameCollection(string $name, string $newName, string $db = null);

    public function save(string $collection, array &$data);

    public function insert(strign $collection, array &$doc);

    public function __call(string $method, array $args = []);

    //// Single key-value storage (WIP)
}
