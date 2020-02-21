<?php
declare(strict_types=1);

namespace MongoHybrid\Contracts;

/**
 * Interface for methods used directly on collection by Cockpit
 * Example: `$this->app->storage->getCollection('foobar')->count()`
 */
interface CollectionInterface
{
    /**
     * @deprecated
     */
    public function drop(): bool;

    /**
     * Count items in collection
     * Used in \install\index.php
     *
     * @param array|callable [$count]
     * @return int
     */
    public function count($filter = []): int;

    /**
     * Insert many documents
     *
     * @param array $documents
     * @return count of inserted documents for arrays
     */
    public function insertMany(array &$documents): int;

    /**
     * Not used in Cockpit
     * Not part of MongoDB
     * @todo mark MongoHybrid\Client::renameCollection as deprecated
     *
     * @param string $newName
     */
    // public function renameCollection(string $newName): void;
}
