<?php

namespace Local\SnipeIT\Service;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Local\SnipeIT\Model\AssetTable;

/**
 * Business logic service for Asset operations.
 * Wraps ORM calls and enforces business rules.
 */
class AssetService
{
    /**
     * Get list of active assets, optionally filtered.
     *
     * @param array $filter  Additional ORM filter array
     * @param int   $limit
     * @param int   $offset
     * @return array
     */
    public static function getAssets(array $filter = [], int $limit = 50, int $offset = 0): array
    {
        $filter['ACTIVE'] = 'Y';

        $result = AssetTable::getList([
            'filter'  => $filter,
            'order'   => ['DATE_MODIFY' => 'DESC'],
            'limit'   => $limit,
            'offset'  => $offset,
        ]);

        return $result->fetchAll();
    }

    /**
     * Get a single asset by ID.
     */
    public static function getAsset(int $id): ?array
    {
        $result = AssetTable::getByPrimary($id);
        $row    = $result->fetch();
        return $row ?: null;
    }

    /**
     * Create a new asset.
     *
     * @param array $fields Associative array of field => value
     * @return Result
     */
    public static function createAsset(array $fields): Result
    {
        $result = new Result();

        if (empty($fields['ASSET_TAG'])) {
            $result->addError(new Error('Тег актива обязателен'));
            return $result;
        }

        // Check uniqueness of ASSET_TAG
        $existing = AssetTable::getList([
            'filter' => ['ASSET_TAG' => $fields['ASSET_TAG'], 'ACTIVE' => 'Y'],
            'limit'  => 1,
        ])->fetch();

        if ($existing) {
            $result->addError(new Error("Тег актива '{$fields['ASSET_TAG']}' уже используется"));
            return $result;
        }

        $addResult = AssetTable::add($fields);
        if (!$addResult->isSuccess()) {
            foreach ($addResult->getErrors() as $error) {
                $result->addError($error);
            }
        } else {
            $result->setData(['ID' => $addResult->getId()]);
        }

        return $result;
    }

    /**
     * Update an existing asset.
     */
    public static function updateAsset(int $id, array $fields): Result
    {
        return AssetTable::update($id, $fields);
    }

    /**
     * Soft-delete an asset (sets ACTIVE='N').
     */
    public static function deleteAsset(int $id): Result
    {
        return AssetTable::update($id, ['ACTIVE' => 'N']);
    }

    /**
     * Search assets by name, tag, or serial.
     */
    public static function searchAssets(string $query): array
    {
        $result = AssetTable::getList([
            'filter' => [
                'ACTIVE' => 'Y',
                [
                    'LOGIC' => 'OR',
                    '%NAME'      => $query,
                    '%ASSET_TAG' => $query,
                    '%SERIAL'    => $query,
                ],
            ],
            'order' => ['NAME' => 'ASC'],
        ]);
        return $result->fetchAll();
    }
}
