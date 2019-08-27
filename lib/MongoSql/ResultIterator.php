<?php
declare(strict_types=1);

namespace MongoSql;

use Traversable;
use IteratorIterator;

use MongoHybrid\Contracts\ {
    DriverInterface,
    ResultInterface
};

/**
 * @inheritdoc
 * Note: IteratorIterator is not internally rewound (due to performance)
 */
class ResultIterator extends IteratorIterator implements ResultInterface
{
    /** @var \MongoSql\DriverInterface */
    protected $driver;

    /** @var array - hasOne relationships */
    protected $hasOne = [];

    /** @var array - hasMany relationships */
    protected $hasMany = [];

    /** @var array - hasOne cache */
    protected $hasOneCache = [];

    /** @var array - hasMany cache */
    protected $hasManyCache = [];

    /**
     * Constructor
     */
    public function __construct(DriverInterface $driver, Traversable $iterator)
    {
        parent::__construct($iterator);

        $this->driver = $driver;
    }

    /**
     * @inheritdoc
     * [NOT TESTED]
     */
    public function hasOne(iterable $collections): ResultInterface
    {
        $this->hasOne[] = $collections;

        foreach ($collections as $fkey => $collection) {
            $this->hasOneCache[$collection] = [];
        }

        return $this;
    }

    /**
     * Apply has one relationshipt to document
     */
    protected function applyHasOne(array &$doc): self
    {
        // Apply hasOne
        foreach ($this->hasOne as $collections) {
            foreach ($collections as $fkey => $collection) {
                if (!empty($doc[$fkey])) {
                    $docFkey = $doc[$fkey];

                    if (!isset($this->hasOneCache[$collection][$docFkey])) {
                        $this->hasOneCache[$collection][$docFkey] = $this->driver->findOneById($collection, $docFkey);
                    }

                    $doc[$fkey] = $this->hasOneCache[$collection][$docFkey];
                }
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     * [NOT TESTED]
     */
    public function hasMany(iterable $collections): ResultInterface
    {
        $this->hasMany[] = $collections;

        foreach ($collections as $collection => $fkey) {
            $this->hasManyCache[$collection] = [];
        }

        return $this;
    }

    /**
     * Apply hasMany relationship to document
     */
    public function applyHasMany(array &$doc): self
    {
        // Apply hasMany
        if (!empty($doc['_id'])) {
            foreach ($this->hasMany as $collections) {
                foreach ($collections as $collection => $fkey) {
                    if (!isset($this->hasManyCache[$collection][$fkey])) {
                        $this->hasManyCache[$collection] = $this->driver->find($collection, [
                            'filter' => [$fkey => $doc['_id']]
                        ]);
                    }

                    $doc[$collection] = $this->hasManyCache[$collection][$fkey];
                }
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        // Use parent with IteratorAggregate
        $doc = parent::current();

        if ($doc !== null) {
            $this
                ->applyHasOne($doc)
                ->applyHasMany($doc)
            ;
        }

        return $doc;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getInnerIterator());
    }
}
