<?php
declare(strict_types=1);

namespace MongoHybrid\Contracts;

use Traversable;

/**
 * Results from Collection::find
 */
interface ResultInterface extends Traversable
{
    /**
     * Populate each document with related one from given collections
     *
     * @param iterable $collections - Format [foreign key => collection name]
     */
    public function hasOne(iterable $collections);

    /**
     * Populate each document with with related ones from given collections
     *
     * @param iterable $collections - Format [collection name => foreign key]
     */
    public function hasMany(iterable $collections);

    /**
     * Convert to array
     * Note: should be able to typecast to array
     *
     * @return array
     */
    public function toArray(): array;
}
