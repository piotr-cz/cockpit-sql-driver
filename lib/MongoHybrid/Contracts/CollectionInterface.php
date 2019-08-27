<?php
declare(strict_types=1);

namespace MongoHybrid\Contracts;

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
     * Not used in Cockpit
     * Not part of MongoDB
     * @todo mark MongoHybrid\Client::renameCollection as deprecated
     *
     * @param string $newName
     */
    // public function renameCollection(string $newName): void;
}
