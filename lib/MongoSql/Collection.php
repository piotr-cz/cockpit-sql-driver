<?php
declare(strict_types=1);

namespace MongoSql;

use PDO;

use MongoSql\Contracts\ {
    CollectionInterface,
    CursorInterface
};

use MongoSql\Driver\Driver;
use MongoSql\QueryBuilder\QueryBuilder;

/**
 * Minimum set of MongoDB\Collection methods requied for Cockpit
 * @see MongoDB\Collection
 */
class Collection implements CollectionInterface
{
    /** @var string Collection name */
    protected $collectionName;

    /** @var \PDO */
    protected $connection;

    /** @var \MongoSql\QueryBuilder\QueryBuilder */
    protected $queryBuilder;

    /** @var \MongoSql\Driver\Driver - Database driver */
    protected $driver;

    /**
     * Constructor
     */
    public function __construct(string $collectionName, PDO $connection, QueryBuilder $queryBuilder, Driver $driver)
    {
        $this->collectionName = $collectionName;
        $this->connection = $connection;
        $this->queryBuilder = $queryBuilder;
        $this->driver = $driver;

        $this->createIfNotExists();
    }

    /**
     * Return collection namespace
     */
    public function __toString(): string
    {
        return $this->collectionName;
    }

    /**
     * Find document
     *
     * @param array|callable
     * @param array $options {
     *   @var array [$sort]
     *   @var int [$limit]
     *   @var int [$skip]
     *   @var array [$projection]
     * }
     * @return Cursor
     *
     * Note: deprecated usage
     * `$collection->find()->limit(1)`
     * in favor of
     * `$collection->find([], ['limit' => 1])`
     */
    public function find($filter = [], $options = []): CursorInterface
    {
        return new Cursor($this->connection, $this->queryBuilder, $this->collectionName, $filter, $options);
    }

    /**
     * Find one document
     *
     * @param array|callable
     * @return array|null
     */
    public function findOne($filter = [], $options = []): ?array
    {
        $results = $this->find($filter, array_merge($options, [
            'limit' => 1
        ]))->toArray();

        return array_shift($results);
    }

    /**
     * Insert new document into collection
     *
     * @param array &$doc
     * @return bool
     */
    public function insertOne(array &$doc): bool
    {
        $doc['_id'] = createMongoDbLikeId();

        $stmt = $this->connection->prepare(
            <<<SQL

                INSERT INTO
                    "{$this->collectionName}" ("document")

                VALUES (
                    :data
                )
SQL
        );

        $stmt->execute([':data' => QueryBuilder::jsonEncode($doc)]);

        return true;
    }

    /**
     * Update documents
     *
     * @param array $filter
     * @param array $data Update to apply to the matched documents
     */
    public function updateMany(array $filter = [], array $update): bool
    {
        $stmt = $this->connection->prepare(
            <<<SQL

                UPDATE
                    "{$this->collectionName}"

                SET
                    "document" = :data

                {$this->queryBuilder->buildWhere($filter)}
SQL
        );

        $stmt->execute([':data'  => QueryBuilder::jsonEncode($update)]);

        return true;
    }

    /**
     * Delete documents
     *
     * @param array $filter
     */
    public function deleteMany(array $filter = []): bool
    {
        $stmt = $this->connection->prepare(
            <<<SQL

                DELETE FROM
                    "{$this->collectionName}"

                {$this->queryBuilder->buildWhere($filter)}
SQL
        );

        $stmt->execute();

        return true;
    }

    /**
     * Count documents
     * @deprecated in MongoDb 1.4 in favor of countDocuments
     */
    public function count($filter = []): int
    {
        // On user defined function must use find to evaluate each item
        if (is_callable($filter)) {
            return iterator_count($this->find($filter));
        }

        $stmt = $this->connection->prepare(
            <<<SQL

                SELECT
                    COUNT("document")

                FROM
                    "{$this->collectionName}"

                {$this->queryBuilder->buildWhere($filter)}
SQL
        );

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @inheritdoc
     */
    public function drop(): bool
    {
        $stmt = $this->connection->prepare(
            <<<SQL

                DROP TABLE IF EXISTS
                    "{$this->collectionName}"
SQL
        );

        $stmt->execute();

        $this->driver->handleCollectionDrop($this->collectionName);

        return true;
    }

    /**
     * Create table if does not exist
     */
    protected function createIfNotExists(): void
    {
        // Create one
        $sql = $this->queryBuilder->buildCreateTable($this->collectionName);

        $this->connection->exec($sql);

        return;
    }
}

// Copied from MongoLite\Database
function createMongoDbLikeId()
{

    // based on https://gist.github.com/h4cc/9b716dc05869296c1be6

    $timestamp = \microtime(true);
    $hostname  = \php_uname('n');
    $processId = \getmypid();
    $id        = \random_int(10, 1000);
    $result    = '';

    // Building binary data.
    $bin = \sprintf(
        '%s%s%s%s',
        \pack('N', $timestamp),
        \substr(md5($hostname), 0, 3),
        \pack('n', $processId),
        \substr(\pack('N', $id), 1, 3)
    );

    // Convert binary to hex.
    for ($i = 0; $i < 12; $i++) {
        $result .= \sprintf('%02x', ord($bin[$i]));
    }

    return $result;
}
