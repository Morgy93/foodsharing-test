<?php

namespace Foodsharing\Modules\Bell;

use Foodsharing\Lib\WebSocketConnection;
use Foodsharing\Modules\Bell\DTO\Bell;
use Foodsharing\Modules\Bell\DTO\BellForExpirationUpdates;
use Foodsharing\Modules\Bell\DTO\BellForList;
use Foodsharing\Modules\Core\BaseGateway;
use Foodsharing\Modules\Core\Database;

class BellGateway extends BaseGateway
{
    private WebSocketConnection $webSocketConnection;

    public function __construct(Database $db, WebSocketConnection $webSocketConnection)
    {
        parent::__construct($db);

        $this->webSocketConnection = $webSocketConnection;
    }

    public function addBell($foodsavers, Bell $bellData): void
    {
        if (!is_array($foodsavers)) {
            $foodsavers = [$foodsavers];
        }

        $bellId = $this->db->insert(
            'fs_bell',
            [
                'name' => $bellData->title,
                'body' => $bellData->body,
                'vars' => $bellData->vars ? serialize($bellData->vars) : null,
                'attr' => $bellData->link_attributes ? serialize($bellData->link_attributes) : null,
                'icon' => $bellData->icon,
                'identifier' => $bellData->identifier,
                'time' => $bellData->time ? $bellData->time->format('Y-m-d H:i:s') : (new \DateTime())->format('Y-m-d H:i:s'),
                'closeable' => $bellData->closeable,
                'expiration' => $bellData->expiration ? $bellData->expiration->format('Y-m-d H:i:s') : null
            ]
        );

        // add the bell for all foodsavers (100 per query)
        $parts = array_chunk($foodsavers, 100);
        foreach ($parts as $part) {
            $data = array_map(function ($fs) use ($bellId) {
                return [
                    'foodsaver_id' => is_array($fs) ? $fs['id'] : $fs,
                    'bell_id' => $bellId,
                    'seen' => 0,
                ];
            }, $part);

            $this->db->insertMultiple('fs_foodsaver_has_bell', $data);
        }

        $this->updateMultipleFoodsaverClients(array_column($foodsavers, 'id'));
    }

    /**
     * @param array $data - the data to be updated. $data['var'] and data['attr'] must not be serialized.
     */
    public function updateBell(int $bellId, array $data, bool $setUnseen = false, bool $updateClients = true): void
    {
        if (isset($data['attr'])) {
            $data['attr'] = serialize($data['attr']);
        }

        if (isset($data['vars'])) {
            $data['vars'] = serialize($data['vars']);
        }

        if (isset($data['time']) && is_a($data['time'], \DateTime::class)) {
            $data['time'] = $data['time']->format('Y-m-d H:i:s');
        }

        if (isset($data['expiration']) && is_a($data['expiration'], \DateTime::class)) {
            $data['expiration'] = $data['expiration']->format('Y-m-d H:i:s');
        }

        $this->db->update('fs_bell', $data, ['id' => $bellId]);

        $foodsaverIds = $this->db->fetchAllValuesByCriteria('fs_foodsaver_has_bell', 'foodsaver_id', ['bell_id' => $bellId]);

        if ($setUnseen && !empty($foodsaverIds)) {
            $this->db->update('fs_foodsaver_has_bell', ['seen' => 0], ['foodsaver_id' => $foodsaverIds, 'bell_id' => $bellId]);
        }

        if ($updateClients) {
            $this->updateMultipleFoodsaverClients($foodsaverIds);
        }
    }

    /**
     * Method returns an array of all bells a user sees.
     *
     * @return BellForList[]
     */
    public function listBells(int $fsId, ?int $limit = null, int $offset = 0)
    {
        if ($limit !== null) {
            $limit = ' LIMIT ' . $offset . ', ' . $limit;
        }

        $stm = 'SELECT
				b.`id`,
				b.`name`,
				b.`body`,
				b.`vars`,
				b.`attr`,
				b.`icon`,
				b.`time`,
				hb.seen,
				b.closeable
			FROM
				fs_bell b,
				`fs_foodsaver_has_bell` hb
			WHERE
				hb.bell_id = b.id
			    AND hb.foodsaver_id = :foodsaver_id
			ORDER BY
                hb.seen ASC,
                b.`time` DESC
			' . $limit . '
		';
        $rows = $this->db->fetchAll($stm, [':foodsaver_id' => $fsId]);

        if (!$rows) {
            return [];
        }

        return $this->createBellsForListFromDatabaseRows($rows);
    }

    /**
     * @param string $identifier - can contain SQL wildcards
     *
     * @return int - id of the bell
     */
    public function getOneByIdentifier(string $identifier): int
    {
        return $this->db->fetchValueByCriteria('fs_bell', 'id', ['identifier like' => $identifier]);
    }

    /**
     * @param string $identifier - can contain SQL wildcards
     *
     * @return BellForExpirationUpdates[]
     */
    public function getExpiredByIdentifier(string $identifier): array
    {
        $bells = $this->db->fetchAll('
            SELECT
                `id`,
				`identifier`
            FROM `fs_bell`
            WHERE `identifier` LIKE :identifier
            AND `expiration` < NOW()',
            [':identifier' => $identifier]
        );

        return $this->createBellsForExpirationUpdatesFromDatabaseRows($bells);
    }

    public function bellWithIdentifierExists(string $identifier): bool
    {
        return $this->db->exists('fs_bell', ['identifier' => $identifier]);
    }

    /**
     * Deletes the bell with the given ID for a specific foodsaver. Returns whether the bell was succesfully
     * deleted.
     */
    public function delBellsForFoodsaver(array $bellIds, int $fsId): int
    {
        $deleted = $this->db->execute('DELETE hb
            FROM fs_foodsaver_has_bell hb
            JOIN fs_bell b ON b.id = hb.bell_id
			WHERE hb.foodsaver_id = ?
                AND b.closeable
                AND b.id IN (' . $this->db->generatePlaceholders(count($bellIds)) . ')
            ', [$fsId, ...$bellIds])->rowCount();
        $this->updateFoodsaverClient($fsId);

        return $deleted;
    }

    public function deleteBellForFoodsavers(int $bellId, array $foodsaverIds): void
    {
        // add the bell for all foodsavers (100 per query)
        $parts = array_chunk($foodsaverIds, 100);
        foreach ($parts as $part) {
            $this->db->delete('fs_foodsaver_has_bell', [
                'foodsaver_id' => $part,
                'bell_id' => $bellId,
            ]);
        }

        $this->updateMultipleFoodsaverClients($foodsaverIds);
    }

    public function delBellsByIdentifier(string $identifier): void
    {
        $foodsaverIds = $this->db->fetchAllValues(
            'SELECT DISTINCT `foodsaver_id`
			FROM `fs_foodsaver_has_bell` JOIN `fs_bell`
			ON `fs_foodsaver_has_bell`.bell_id = `fs_bell`.id
			WHERE `identifier` = :identifier',
            [':identifier' => $identifier]
        );

        $this->db->delete('fs_bell', ['identifier' => $identifier]);

        $this->updateMultipleFoodsaverClients($foodsaverIds);
    }

    /**
     * Marks the bells specified by a list of IDs and the owner's ID as read. Returns the number of
     * bells that were successfully changed.
     */
    public function setBellsAsSeen(array $bellIds, int $foodsaverId): int
    {
        return $this->db->update('fs_foodsaver_has_bell',
            ['seen' => 1],
            [
                'bell_id' => array_map('intval', $bellIds),
                'foodsaver_id' => $foodsaverId
            ]
        );
    }

    private function updateFoodsaverClient(int $foodsaverId): void
    {
        $this->webSocketConnection->sendSock($foodsaverId, 'bell', 'update', []);
    }

    /**
     * @param int[] $foodsaverIds
     */
    private function updateMultipleFoodsaverClients(array $foodsaverIds): void
    {
        $this->webSocketConnection->sendSockMulti($foodsaverIds, 'bell', 'update', []);
    }

    /**
     * @param array $databaseRows - 2D-array with bell data, expects indexes []['vars'] and []['attr'] to contain serialized data
     *
     * @return BellForList[] - BellData objects with with unserialized $ball->vars and $bell->attr
     */
    private function createBellsForListFromDatabaseRows(array $databaseRows): array
    {
        $output = [];
        foreach ($databaseRows as $row) {
            $bellDTO = new BellForList();

            // This onclick-to-href conversion is probably not needed anymore
            if (isset($row['attr']['onclick'])) {
                preg_match('/profile\((.*?)\)/', $row['attr']['onclick'], $matches);
                if ($matches) {
                    $row['attr']['href'] = '/profile/' . $matches[1];
                }
            }

            $bellDTO->id = $row['id'];
            $bellDTO->key = $row['body'];
            $bellDTO->title = $row['name'];
            $bellDTO->payload = unserialize($row['vars'], ['allowed_classes' => false]);
            $bellDTO->href = unserialize($row['attr'], ['allowed_classes' => false])['href'];
            $bellDTO->icon = $row['icon'][0] != '/' ? $row['icon'] : null;
            $bellDTO->image = $row['icon'][0] == '/' ? $row['icon'] : null;
            $bellDTO->createdAt = (new \DateTime($row['time']))->format('Y-m-d\TH:i:s');
            $bellDTO->isRead = $row['seen'];
            $bellDTO->isCloseable = $row['closeable'];

            $output[] = $bellDTO;
        }

        return $output;
    }

    /**
     * @param array $databaseRows - 2D-array with bell data, expects indexes []['vars'] and []['attr'] to contain serialized data
     *
     * @return BellForExpirationUpdates[] - BellData objects with with unserialized $ball->vars and $bell->attr
     */
    private function createBellsForExpirationUpdatesFromDatabaseRows(array $databaseRows): array
    {
        $output = [];
        foreach ($databaseRows as $row) {
            $bellDTO = new BellForExpirationUpdates();

            $bellDTO->id = $row['id'];
            $bellDTO->identifier = $row['identifier'];

            $output[] = $bellDTO;
        }

        return $output;
    }
}
