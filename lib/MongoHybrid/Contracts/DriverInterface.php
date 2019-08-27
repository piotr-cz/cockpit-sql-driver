<?php
declare(strict_types=1);

namespace MongoHybrid\Contracts;

use MongoHybrid\Contracts\CollectionInterface;

use MongoHybrid\ResultSet;

/**
 * Driver Interface used by MongoHybrid\Client
 *
 * Notes
 * - $collectionId = db name + collection name
 * - methods may be called via MongoHybrid\Client or as a proxy via MmongoHybrid\Client::__call ($app->storage->update
 */
interface DriverInterface
{
    //// Used by MongoHybrid\Client

    /**
     * Get collection
     *
     * @param string name
     * @param string [$db]
     * @return CollectionInterface
     */
    public function getCollection(string $name, string $db = null): CollectionInterface;

    //// Used by MongoHybrid\Client or as proxy

    /**
     * Find one document in collection
     * Used in modules\Cockpit\cli\account\create.php, ...
     *
     * @param string $collectionId
     * @param array|callable [$filter]
     * @return array|null
     */
    public function findOne(string $collectionId, $filter = []): ?array;

    /**
     * Find document in collection by it's id
     * Used in lib\MongoHybrid\ResultSet::hasOne
     *
     * @param string $collectionId
     * @param string $docId
     * @return array|null
     */
    public function findOneById(string $collectionId, string $docId): ?array;

    /**
     * Save (insert or update) new document into collection
     *
     * @param string $collectionId - Full collection id
     * @param array &$doc
     * @param bool $isCreate - true to replace document, false to update
     * @return bool
     */
    public function save(string $collectionId, array &$doc, bool $isCreate = false): bool;

    /**
     * Remove documents from collection
     *
     * @param string $collectionId
     * @param array|callable $filter
     */
    public function remove(string $collectionId, $filter): bool;

    /**
     * Insert new document into collection
     *
     * @param string $collectionId - Full collection id
     * @param array &$doc
     * @return bool
     */
    public function insert(string $collectionId, array &$doc): bool;

    /**
     * Used in modules\Cockpit\cli\import\accounts.php
     */
    public function count(string $collectionId, $filter = []): int;

    /**
     * Drop collection
     * Cockpit MongoLite implementation is broken
     *
     * Used in
     * - modules\Collections\cli\flush\accounts.php
     * - modules\Collections\cli\fllush\assets.php
     * - modules\Collections\cli\flush\collections.php
     * - modules\Collections\bootstrap.php
     * - modules\Forms\cli\flush\forms.php
     * - ...
     *
     * @param string $collectionId
     */
    public function dropCollection(string $collectionId): bool;

    //// Used as proxy

    /**
     * Update documents in collection matching filter
     * Used in modules\Cockpit\module\auth.php
     *
     * @param string $collectionId
     * @param array|callable $filter
     * @param array  $data
     */
    public function update(string $collectionId, $filter, array $data);

    /**
     * Find documents in collection
     *
     * @param string $collectionId
     * @param array [$options] {
     *   @var array [$filter]
     *   @var array [$fields]
     *   @var array [$sort]
     *   @var int [$limit]
     *   @var int [$skip]
     * }
     * @return ResultSet
     */
    public function find(string $collectionId, array $options = []);

    /**
     * Remove field from collection documents
     * @since https://github.com/agentejo/cockpit/commit/504bc559af08b8e22c5dc5c15ef27bf13192ed42
     *
     * @param string $collectionId
     * @param string $field
     */
    public function removeField(string $collectionId, string $field): void;

    /**
     * Rename field in collection documents
     * @since https://github.com/agentejo/cockpit/commit/76f90543074705d941df48e9b1d3ffec3873c30a
     *
     * @param string $collectionId
     * @param string $field
     * @param string $newField
     */
    public function renameField(string $collectionId, string $field, string $newField): void;
}
