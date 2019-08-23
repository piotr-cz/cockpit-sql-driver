<?php
declare(strict_types=1);

namespace MongoSql\Driver;

use PDO;
use PDOException;

use MongoHybrid\ResultSet;

use MongoSql\ {
    DriverException,
    Collection,
    ResultIterator
};

use MongoSql\Contracts\ {
    CollectionInterface,
    DriverInterface
};

/**
 * Driver factory
 */
abstract class Driver implements DriverInterface
{
    /** @var string - MongoHybrid server id */
    public const SERVER_NAME = 'sqldriver';

    /** @var string - Driver name */
    protected const DB_DRIVER_NAME = null;

    /** @var string - Query builder FQCN */
    protected const QUERYBUILDER_CLASS = null;

    /** @type \PDO - Database connection */
    protected $connection;

    /** @var array - Collections cache */
    protected $collections = [];

    /** @var \MongoSql\QueryBuilder\QueryBuilder */
    protected $queryBuilder;

    /**
     * Constructor
     *
     * @param array $options {
     *   @var string [$host]
     *   @var int [$port]
     *   @var string $dbname
     *   @var string [$charset]
     *   @var string $username
     *   @var string $password
     * }
     * @param array $driverOptions
     * @throws \MongoSql\DriverException
     */
    public function __construct(array $options, array $driverOptions = [])
    {
        // Using + to keep keys
        // See https://www.php.net/manual/en/pdo.setattribute.php
        $driverOptions = $driverOptions + [
            // Throw exceptions on errors
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Set default fetch mode
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_COLUMN,
            // Use prepares to avoid parsing query selectors as placeholders
            PDO::ATTR_EMULATE_PREPARES => true,
        ];

        try {
            $this->connection = $this->createConnection($options, $driverOptions);
        } catch (PDOException $pdoException) {
            throw new DriverException(sprintf('PDO connection failed: %s', $pdoException->getMessage()), 0, $pdoException);
        }

        $queryBuilderFqcn = static::QUERYBUILDER_CLASS;

        $this->queryBuilder = new $queryBuilderFqcn([$this->connection, 'quote']);

        $this->assertIsDbSupported();
    }

    /**
     * Assert features are supported by database
     *
     * @throws \MongoSql\DriverException
     */
    protected function assertIsDbSupported(): void
    {
        $pdoDriverName = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        // Note: may also use static::DB_DRIVER_NAME;

        // Check for PDO Driver
        if (!in_array($pdoDriverName, PDO::getAvailableDrivers())) {
            throw new DriverException(sprintf('PDO extension for %s driver not loaded', $pdoDriverName));
        }

        return;
    }

    /**
     * Assert min database server version requirement
     *
     * @param string $currentVersion
     * @param string $minVersion
     * @throws \MongoSql\DriverException
     */
    protected static function assertIsDbVersionSupported(string $currentVersion, string $minVersion): void
    {
        if (!version_compare($currentVersion, $minVersion, '>=')) {
            throw new DriverException(vsprintf('Driver requires database server version >= %s, got %s', [
                $minVersion,
                $currentVersion
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function getCollection(string $name, ?string $db = null): CollectionInterface
    {
        $collectionId = $db
            ? $db . '/' . $name
            : $name;

        if (!isset($this->collections[$collectionId])) {
            $this->collections[$collectionId] = new Collection($collectionId, $this->connection, $this->queryBuilder, $this);
        }

        return $this->collections[$collectionId];
    }

    /**
     * @inheritdoc
     */
    public function dropCollection(string $collectionId): bool
    {
        return $this->getCollection($collectionId)->drop();
    }

    /**
     * Handle collection drop
     *
     * @param string $collectionId
     */
    public function handleCollectionDrop(string $collectionId): void
    {
        unset($this->collections[$collectionId]);
    }

    /**
     * @inheritdoc
     */
    public function find(string $collectionId, array $criteria = [], bool $returnIterator = false)
    {
        $filter = $criteria['filter'] ?? null;

        $options = [
            'sort'       => $criteria['sort'] ?? null,
            'limit'      => $criteria['limit'] ?? null,
            'skip'       => $criteria['skip'] ?? null,
            'projection' => $criteria['fields'] ?? null,
        ];

        $cursor = $this->getCollection($collectionId)->find($filter, $options);

        if ($returnIterator) {
            return new ResultIterator($this, $cursor);
        }

        $docs = array_values($cursor->toArray());

        return new ResultSet($this, $docs);
    }

    /**
     * @inheritdoc
     */
    public function findOne(string $collectionId, $filter = []): ?array
    {
        return $this->getCollection($collectionId)->findOne($filter);
    }

    /**
     * @inheritdoc
     */
    public function findOneById(string $collectionId, string $docId): ?array
    {
        return $this->findOne($collectionId, ['_id' => $docId]);
    }

    /**
     * @inheritdoc
     */
    public function save(string $collectionId, array &$doc, bool $isCreate = false): bool
    {
        if (empty($doc['_id'])) {
            return $this->insert($collectionId, $doc);
        }

        $filter = ['_id' => $doc['_id']];

        if ($isCreate) {
            return $this->getCollection($collectionId)->replaceMany($filter, $doc);
        }

        return $this->getCollection($collectionId)->updateOne($filter, $doc);
    }

    /**
     * @inheritdoc
     */
    public function insert(string $collectionId, array &$doc): bool
    {
        return $this->getCollection($collectionId)->insertOne($doc);
    }

    /**
     * @inheritdoc
     */
    public function update(string $collectionId, $filter = [], array $data): bool
    {
        return $this->getCollection($collectionId)->updateMany($filter, $data);
    }

    /**
     * @inheritdoc
     */
    public function remove(string $collectionId, $filter = []): bool
    {
        return $this->getCollection($collectionId)->deleteMany($filter);
    }

    /**
     * @inheritdoc
     */
    public function count(string $collectionId, $filter = []): int
    {
        return $this->getCollection($collectionId)->count($filter);
    }

    /**
     * @inheritdoc
     */
    public function removeField(string $collectionId, string $field, $filter = []): void
    {
        $docs = $this->find($collectionId, ['filter' => $filter]);

        foreach ($docs as $doc) {
            if (!isset($doc[$field])) {
                continue;
            }

            unset($doc[$field]);

            $this->save($collectionId, $doc, true);
        }

        return;
    }

    /**
     * @inheritdoc
     */
    public function renameField(string $collectionId, string $field, string $newField, $filter = []): void
    {
        $docs = $this->find($collectionId, ['filter' => $filter]);

        foreach ($docs as $doc) {
            if (!isset($doc[$field])) {
                continue;
            }

            $doc[$newField] = $doc[$field];

            unset($doc[$field]);

            $this->save($collectionId, $doc, true);
        }

        return;
    }
}
