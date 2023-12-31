<?php

namespace Foodsharing\RestApi;

use Carbon\Carbon;
use Foodsharing\Modules\Core\DBConstants\Foodsaver\SleepStatus;

/**
 * Utility class that can be user by all controllers to format objects for
 * uniform Rest responses.
 */
class RestNormalization
{
    /**
     * Formats a timestamp to the DATE_ATOM format.
     *
     * @param int $timestamp a timestamp
     */
    public static function normalizeDate(int $timestamp): string
    {
        return date(DATE_ATOM, $timestamp);
    }

    /**
     * Returns the response data for a foodsaver including id, name, photo url,
     * and sleep-status.
     *
     * @param array $data the foodsaver data from the database
     * @param string $prefix a prefix for the entries in the data array
     */
    public static function normalizeUser(array $data, string $prefix = '', string $idPrefix = ''): array
    {
        //sleep_status is used with and without prefix
        $sleepStatus = self::isSleeping($data, $prefix);

        return [
            'id' => (int)$data[$prefix . $idPrefix . 'id'],
            'name' => $data[$prefix . 'name'],
            'avatar' => $data[$prefix . 'photo'] ?? null,
            'sleepStatus' => $sleepStatus,
        ];
    }

    /**
     * Returns the response data for a foodsaver in store context: the above, plus
     * phone numbers, verification state, passed quiz level and if they're manager.
     *
     * @param array $data the user data from the database
     */
    public static function normalizeStoreUser(array $data): array
    {
        if (!isset($data['id'])) {
            // the user can no longer be found
            $data['id'] = -1;
            $data['name'] = '?';
        }

        return [
            /* user-related data: */
            'id' => (int)$data['id'],
            'name' => $data['name'],
            'avatar' => $data['photo'] ?? null,
            'sleepStatus' => self::isSleeping($data),
            'mobile' => $data['handy'] ?? '',
            'landline' => $data['telefon'] ?? '',
            // 'isVerified' => boolval($data['verified']),
            // 'roleLevel' => $data['quiz_rolle'], // should be added to FS:getFoodsaverDetails
            /* team-related data: */
            'isManager' => boolval($data['verantwortlich'] ?? false),
            // 'team_active' (membership status) should be included as well
        ];
    }

    private static function isSleeping(array $data, string $prefix = ''): bool
    {
        $sleepFrom = null;
        $sleepUntil = null;
        if (isset($data[$prefix . 'sleep_status'])) {
            $sleepState = $data[$prefix . 'sleep_status'];
            $sleepFrom = $data[$prefix . 'sleep_from'] ?? null;
            $sleepUntil = $data[$prefix . 'sleep_until'] ?? null;
        } elseif (isset($data['sleep_status'])) {
            $sleepState = $data['sleep_status'];
        } else {
            $sleepState = SleepStatus::NONE;
        }

        return match ($sleepState) {
            SleepStatus::TEMP => $sleepFrom && Carbon::now()->isSameDay(Carbon::parse($sleepFrom))
                || $sleepFrom && Carbon::now()->isAfter(Carbon::parse($sleepFrom)->startOfDay())
                || ($sleepFrom && $sleepUntil && Carbon::now()->isBefore(Carbon::parse($sleepUntil)->addDay()) && Carbon::now()->isAfter(Carbon::parse($sleepFrom)->endOfDay())),
            SleepStatus::FULL => true,
            default => false,
        };
    }

    /**
     * Returns the response data for a store.
     *
     * @param array $data the store data from the database
     */
    public static function normalizeStore(array $data, bool $includeDetails): array
    {
        $store = [
            'id' => (int)$data['id'],
            'name' => $data['name'],
            'group' => [
                'id' => $data['bezirk_id'],
                'name' => $data['bezirk'],
            ],
            'lat' => (float)$data['lat'],
            'lon' => (float)$data['lon'],
            'storeCategoryId' => (int)$data['betrieb_kategorie_id'],
            'cooperationStatus' => (int)$data['betrieb_status_id'],
            'teamStatus' => (int)$data['team_status'],
            'chain' => [],
            'responsibleUserIds' => [],
        ];

        if (isset($data['kette'])) {
            $store['chain'] = $data['kette'];
        }
        if (isset($data['verantwortlicher']) && is_array($data['verantwortlicher'])) {
            $store['responsibleUserIds'] = array_map(function ($u) {
                return (int)$u['id'];
            }, $data['verantwortlicher']);
        }

        if ($includeDetails) {
            $store = array_merge($store, [
                'address' => self::normalizeAddress($data),
                'phone' => $data['telefon'],
                'fax' => $data['fax'],
                'email' => $data['email'],
                'contactPerson' => $data['ansprechpartner'],
                'updatedAt' => self::normalizeDate(strtotime($data['status_date'])),
                'notes' => [],
            ]);

            if (isset($data['notizen']) && is_array($data['notizen'])) {
                $store['notes'] = array_map(function ($n) {
                    return self::normalizeStoreNote($n);
                }, $data['notizen']);
            }
        }

        return $store;
    }

    /**
     * Returns the response data for an address.
     *
     * @param array $data the address data from the database
     */
    public static function normalizeAddress(array $data): array
    {
        return [
            'street' => $data['str'],
            'city' => $data['stadt'],
            'postalCode' => $data['plz']
        ];
    }

    /**
     * Returns the response data for a note on a store's wall (milestone).
     *
     * @param array $data the note data from the database
     */
    public static function normalizeStoreNote(array $data): array
    {
        return [
            'id' => (int)$data['id'],
            'foodsaverId' => (int)$data['foodsaver_id'],
            'text' => $data['text'],
            'author' => self::normalizeUser($data, '', 'foodsaver_'),
            'createdAt' => self::normalizeDate(strtotime($data['zeit'])),
        ];
    }
}
