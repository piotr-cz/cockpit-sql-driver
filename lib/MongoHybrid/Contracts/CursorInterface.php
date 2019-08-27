<?php
declare(strict_types=1);

namespace MongoHybrid\Contracts;

use Traversable;

/**
 * @see https://www.php.net/manual/en/class.mongodb-driver-cursor.php
 */
interface CursorInterface extends Traversable
{
    /**
     * Get documents as an array
     *
     * @return array
     */
    public function toArray(): array;
}
