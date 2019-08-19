<?php
declare(strict_types=1);

namespace MongoSql\Contracts;

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
     * @return int
     */
    public function count($filter = []): int;

    /**
     * Not used in Cockpit
     * Not part of MongoDB
     * @todo mark MongoHybrid\Client::renameCollection as deprecated
     */
    // public function renameCollection(string $newName): void;
}
