<?php
declare(strict_types=1);

namespace MongoSql\Contracts;

/**
 * Results from Collection::find
 */
interface ResultInterface extends \Traversable
{
    /**
     * Populate each document with related one from given collections
     * @param iterable $collections - Format [foreign key => collection name]
     * @return void
     */
    public function hasOne(iterable $collections): ResultInterface;

    /**
     * Populate each document with with related ones from given collections
     * @param iterable $collections - Format [collection name => foreign key]
     * @return void
     */
    public function hasMany(iterable $collections): ResultInterface;

    /**
     * Convert to array
     * Note: should be able to typecast to array
     */
    public function toArray(): array;
}
