<?php
declare(strict_types=1);

namespace MongoSql\QueryBuilder;

use PDO;

/**
 * See \MongoLite\Database\UtilArrayQuery
 */
abstract class QueryBuilder
{
    // Ordering directions
    protected const ORDER_BY_ASC = 1;
    protected const ORDER_BY_DESC = -1;

    // Logical operators
    protected const GLUE_OPERATOR_AND = ' AND ';
    protected const GLUE_OPERATOR_OR  = ' OR ';

    protected const GLUE_OPERATOR = [
        '$and' => self::GLUE_OPERATOR_AND,
        '$or'  => self::GLUE_OPERATOR_OR,
    ];

    /** @var callable */
    protected $connectionQuote;

    /**
     * Constructor
     *
     * @param callable $connectionQuote - Connection quite callable
     */
    public function __construct(callable $connectionQuote)
    {
        $this->connectionQuote = $connectionQuote;
    }

    /**
     * Create query builder from connection
     *
     * @param \PDO
     * @return self
     */
    public static function createFromPdo(PDO $connection): self
    {
        // Get driver name
        $pdoDriverName = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Resolve FQCN
        $fqcn = sprintf('%s\\%sQueryBuilder', __NAMESPACE__, ucfirst($pdoDriverName));

        if (!class_exists($fqcn)) {
            throw new \RuntimeException('Cannot initialize query builder for %s driver', $pdoDriverName);
        }

        return new $fqcn([$connection, 'quote']);
    }

    /**
     * Create path selector for field
     * Nested fields are separater by comma
     *
     * @param string $fieldName
     * @return string
     */
    abstract public function createPathSelector(string $fieldName): string;

    /**
     * Build ORDER BY subquery
     *
     * @param array|null $sorts
     * @return string|null
     */
    public function buildOrderby(array $sorts = null): ?string
    {
        if (!$sorts) {
            return null;
        }

        $sqlOrderBySegments = [];

        foreach ($sorts as $fieldName => $direction) {
            $sqlOrderBySegments[] = vsprintf('%s %s', [
                $this->createPathSelector($fieldName),
                $direction === static::ORDER_BY_DESC ? 'DESC' : 'ASC'
            ]);
        }

        return !empty($sqlOrderBySegments)
            ? 'ORDER BY ' . implode(', ', $sqlOrderBySegments)
            : null;
    }

    /**
     * Build LIMIT subquery
     *
     * @param int|null $limit
     * @param int|null $offset
     * @return string|null
     */
    public function buildLimit(int $limit = null, int $offset = null): ?string
    {
        if (!$limit) {
            return null;
        }

        // Offset (limit must be provided)
        // See https://stackoverflow.com/questions/255517/mysql-offset-infinite-rows
        if ($offset) {
            return sprintf('LIMIT %d OFFSET %d', $limit, $offset);
        }

        return sprintf('LIMIT %d', $limit);
    }

    /**
     * Build WHERE subquery
     *
     * @param array|null $criteria
     * @return string|null
     */
    public function buildWhere(array $criteria = null): ?string
    {
        if (!$criteria) {
            return null;
        }

        $segments = $this->buildWhereSegments($criteria);

        return !empty($segments)
            ? sprintf('WHERE %s', $segments)
            : null;
    }

    /**
     * Build WHERE segments
     *
     * @see \MongoLite\Database\UtilArrayQuery::buildCondition
     *
     * @param array $criteria
     * @param string $concat
     * @return string|null
     */
    protected function buildWhereSegments(array $criteria, string $concat = self::GLUE_OPERATOR_AND): ?string
    {
        $whereSegments = [];

        // Key may be field name or operator
        foreach ($criteria as $key => $value) {
            switch ($key) {
                // Operators: value is array of conditions
                case '$and':
                case '$or':
                    $whereSubSegments = [];

                    foreach ($value as $subCriteria) {
                        $whereSubSegments[] = $this->buildWhereSegments($subCriteria, static::GLUE_OPERATOR[$key]);
                    }

                    $whereSegments[] = '(' . implode(static::GLUE_OPERATOR[$key], $whereSubSegments) . ')';
                    break;

                // No operator:
                default:
                    // $not operator in values' key, condition in it's value
                    if (is_array($value) && array_keys($value) === ['$not']) {
                        $whereSegments[] = 'NOT ' . is_array($value['$not'])
                            ? $this->buildWhereSegments([$key => $value['$not']])
                            : $this->buildWhereSegmentsGroup($key, ['$regex' => $value['$not']]);
                        break;
                    }

                    // Value
                    $whereSegments[] = $this->buildWhereSegmentsGroup(
                        $key,
                        is_array($value) ? $value : ['$eq' => $value]
                    );
                    break;
            }
        }

        if (empty($whereSegments)) {
            return null;
        }

        return implode($concat, $whereSegments);
    }

    /**
     * Build where segments group
     *
     * @see \MongoLite\Database\UtilArrayQuery::check
     *
     * @param string $fieldName
     * @param array $conditions
     * @return string
     */
    protected function buildWhereSegmentsGroup(string $fieldName, array $conditions): string
    {
        $subSegments = [];

        foreach ($conditions as $func => $value) {
            $subSegments[] = $this->buildWhereSegment($func, $fieldName, $value);
            // TODO: pass path to builWhereSegment
            // $subSegments[] = $this->buildWhereSegment($func, $this->createPathSelector($fieldName), $value);
        }

        // Remove nulls
        $subSegments = array_filter($subSegments);

        return implode(static::GLUE_OPERATOR_AND, $subSegments);
    }

    /**
     * Build single where segment
     * Should implement:
     * - $eq (equals), $ne (not eqals), $gte (greater or equals), $gt (greater), $lte (lower or equals), $lt (lower),
     * - $in (target is one of array elements), $nin (target is not one of array elements), $has (target contains array elements), $all (target contains all array elements),
     * - $regex (Regex)
     * - $size (Array size),
     * - $mod (Mod),
     * - $func (Callback),
     * - $fuzzy (Fuzzy search), $text (Search in text)
     * - $options
     * or throw \InvalidArgumentException when func is not implemented
     *
     * @see \MongoLite\Database\UtilArrayQuery::evaluate
     *
     * @param string $func
     * @param string $fieldName
     * @param mixed $value
     * @return string|null
     * @throws \InvalidArgumentException
     * @throws \ErrorException
     */
    abstract protected function buildWhereSegment(string $func, string $fieldName, $value): ?string;

    /**
     * Build query checking if table exists
     * NOT USED
     *
     * @param string $tableName
     * @return string
     */
    abstract public function buildTableExists(string $tableName): string;

    /**
     * Build query to create table
     *
     * @param string $tableName
     * @return string
     */
    abstract public function buildCreateTable(string $tableName): string;

    /**
     * Quote value
     * @param mixed $value
     * @return string
     * @throws \TypeError - When passing non-string to quote: PDO::quote() expects parameter 1 to be string, xxx given
     */
    public function qv($value): string
    {
        return ($this->connectionQuote)((string) $value);
    }

    /**
     * Quote multiple values
     * @param array $values
     * @return string
     */
    public function qvs(array $values): string
    {
        return implode(', ', array_map([$this, 'qv'], $values));
    }

    /**
     * Quote identifier (table or column name)
     *
     * @param string $identifier
     * @return string
     */
    abstract public function qi(string $identifier): string;

    /**
     * Quote and escape LIKE value
     *
     * @param string $value
     * @return string
     */
    public static function wrapLikeValue(string $value): string
    {
        return sprintf('%%%s%%', strtr($value, [
            '_' => '\\_',
            '%' => '\\%',
        ]));
    }

    /**
     * Encode value helper
     *
     * @param mixed $value
     * @return string
     */
    public static function jsonEncode($value): string
    {
        // Slashes are nomalized by MySQL anyway
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Decode value helper
     *
     * @param string $string
     * @return mixed
     */
    public static function jsonDecode(string $string)
    {
        return json_decode($string, true);
    }
}
