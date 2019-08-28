<?php
declare(strict_types=1);

namespace MongoSql;

use PDO;
use PDOException;

use Traversable;
use IteratorAggregate;
use Generator;

use CallbackFilterIterator;
use LimitIterator;

use MongoHybrid\Contracts\CursorInterface;

use MongoSql\DriverException;
use MongoSql\QueryBuilder\QueryBuilder;

/**
 * Cursor implementation
 *
 * @see {@link MongoDB\Driver\Cursor https://www.php.net/manual/en/class.mongodb-driver-cursor.php}
 * @see \MongoDB\Operation\Find
 *
 * @note this is different than https://www.php.net/manual/en/class.mongocursor.php
 */
class Cursor implements IteratorAggregate, CursorInterface
{
    /** @var \PDO */
    protected $connection;

    /** @var \MongoSql\QueryBuilder\QueryBuilder */
    protected $queryBuilder;

    /** @var string */
    protected $collectionName;

    /** @var array|callable|null */
    protected $filter;

    /** @var array */
    protected $options = [
        'sort'       => null,
        'limit'      => null,
        'skip'       => null,
        'projection' => null,
    ];

    /**
     * Constructor
     *
     * @param \PDO $connection
     * @param string $collectionName
     * @param array|callable $filter
     * @param array $options {
     *   @var array [$sort]
     *   @var int [$limit]
     *   @var int [$skip]
     *   @var array [$projection]
     * }
     */
    public function __construct(
        PDO $connection,
        QueryBuilder $queryBuilder,
        string $collectionName,
        $filter = [],
        array $options = []
    ) {
        $this->connection = $connection;
        $this->queryBuilder = $queryBuilder;

        $this->collectionName = $collectionName;
        $this->filter = $filter;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * Get Traversable
     * IteratorAggregate implementation
     *
     * @see {@link https://www.php.net/manual/en/class.generator.php}
     *
     * @return \Traversable
     * @throws \PDOException
     */
    public function getIterator(): Traversable
    {
        $sqlWhere = !is_callable($this->filter) ? $this->queryBuilder->buildWhere($this->filter) : null;
        $sqlOrderBy = $this->queryBuilder->buildOrderBy($this->options['sort']);
        $sqlLimit = !is_callable($this->filter) ? $this->queryBuilder->buildLimit($this->options['limit'], $this->options['skip']) : null;

        // Build query
        $sql = <<<SQL

            SELECT
                "document"

            FROM
                {$this->queryBuilder->qi($this->collectionName)}

            {$sqlWhere}
            {$sqlOrderBy}
            {$sqlLimit}
SQL;

        try {
            /* Query without parameters (via PDO::prepare) to avoid problems with reserved characters (? and :)
             * driver option PDO::ATTR_EMULATE_PREPARES must be set to true - see {@link https://bugs.php.net/bug.php?id=74220}
             * This is fixed in php 7.4-beta.1 {@link https://wiki.php.net/rfc/pdo_escape_placeholders}
             */

            // $stmt = $this->connection->prepare($sql, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_COLUMN]);
            // @throws \PDOException: SQLSTATE[HY093]: Invalid parameter number: no parameters were bound  on ATTR_EMULATE_PREPARES true
            // @throws \PDOException: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "$1"  on ATTR_EMULATE_PREPARES false
            // $stmt->execute();

            // @throws \PDOException: SQLSTATE[42601]: Syntax error: 7 ERROR:  syntax error at or near "$1"  on ATTR_EMULATE_PREPARES false
            $stmt = $this->connection->query($sql, PDO::FETCH_COLUMN, 0);
        } catch (PDOException $pdoException) {
            // Rethrow exception with query
            throw new DriverException(
                sprintf('PDOException while running query %s', $sql),
                // Some PostgresSQL codes are strings (22P02)
                (int) $pdoException->getCode(),
                $pdoException
            );
        }

        $it = mapIterator($stmt, [QueryBuilder::class, 'jsonDecode']);

        if (is_callable($this->filter)) {
            $it = new CallbackFilterIterator($it, $this->filter);
            // Note: Rewinding LimitIterator empties it
            $it = new LimitIterator($it, $this->options['skip'] ?? 0, $this->options['limit'] ?? -1);
        }

        $projection = static::compileProjection($this->options['projection']);

        return mapIterator($it, [static::class, 'applyDocumentProjection'], $projection);
    }

    /**
     * Compile projection
     *
     * @param array|null $projection
     * @return array
     */
    protected static function compileProjection(array $projection = null): ?array
    {
        if (empty($projection)) {
            return null;
        }

        $include = array_filter($projection);
        $exclude = array_diff($projection, $include);

        return [
            'include' => $include,
            'exclude' => $exclude,
        ];
    }

    /**
     * Apply projection to document
     *
     * @param array|null $document
     * @param array [$projection] {
     *   @var array $exclude
     *   @var array $include
     * }
     * @return array|null
     */
    public static function applyDocumentProjection(?array $document, array $projection = null): ?array
    {
        if (empty($document) || empty($projection)) {
            return $document;
        }

        $id = $document['_id'];
        $include = $projection['include'];
        $exclude = $projection['exclude'];

        // Remove keys
        if (!empty($exclude)) {
            $document = array_diff_key($document, $exclude);
        }

        // Keep keys (not sure why MongoLite::cursor uses custom function array_key_intersect)
        if (!empty($include)) {
            $document = array_intersect_key($document, $include);
        }

        // Don't remove `_id` via include unless it's explicitly excluded
        if (!isset($exclude['_id'])) {
            $document['_id'] = $id;
        }

        return $document;
    }
}

/**
 * Apply callback to every element
 *
 * @param iterable $iterable
 * @param callable $function
 * @param mixed ...$args - Custom arguments
 * @return \Generator
 */
function mapIterator(iterable $iterable, callable $function, ...$args): Generator
{
    foreach ($iterable as $key => $value) {
        yield $key => $function($value, ...$args);
    }
}
