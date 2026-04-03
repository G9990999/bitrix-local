<?php

namespace Local\SnipeIT\Service;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use Local\SnipeIT\Model\AssetTable;
use Local\SnipeIT\Model\AssignmentTable;

/**
 * Service for checkout (выдача) and checkin (возврат) operations.
 *
 * Integrates with Bitrix users (b_user table via USER_ID).
 */
class CheckoutService
{
    /**
     * Check out an asset to a Bitrix user.
     *
     * @param int    $assetId  ID of the asset (snipeit_assets.ID)
     * @param int    $userId   Bitrix user ID (b_user.ID)
     * @param string $note     Optional note
     * @return Result
     */
    public static function checkout(int $assetId, int $userId, string $note = ''): Result
    {
        $result = new Result();

        $asset = AssetTable::getByPrimary($assetId)->fetch();
        if (!$asset || $asset['ACTIVE'] !== 'Y') {
            $result->addError(new Error("Актив #$assetId не найден или неактивен"));
            return $result;
        }

        if ($asset['ASSIGNED_TO'] !== null && $asset['ASSIGNED_TO'] > 0) {
            $result->addError(new Error("Актив уже назначен пользователю #{$asset['ASSIGNED_TO']}"));
            return $result;
        }

        // Create assignment record
        $assignResult = AssignmentTable::add([
            'ASSET_ID'  => $assetId,
            'USER_ID'   => $userId,
            'DATE_FROM' => new DateTime(),
            'NOTE'      => $note,
            'ACTIVE'    => 'Y',
        ]);

        if (!$assignResult->isSuccess()) {
            foreach ($assignResult->getErrors() as $error) {
                $result->addError($error);
            }
            return $result;
        }

        // Update asset's ASSIGNED_TO field
        $updateResult = AssetTable::update($assetId, [
            'ASSIGNED_TO' => $userId,
            'STATUS'      => 'deployable',
        ]);

        if (!$updateResult->isSuccess()) {
            foreach ($updateResult->getErrors() as $error) {
                $result->addError($error);
            }
        } else {
            $result->setData(['ASSIGNMENT_ID' => $assignResult->getId()]);
        }

        return $result;
    }

    /**
     * Check in (return) an asset.
     *
     * @param int    $assetId
     * @param string $note
     * @return Result
     */
    public static function checkin(int $assetId, string $note = ''): Result
    {
        $result = new Result();

        $asset = AssetTable::getByPrimary($assetId)->fetch();
        if (!$asset || $asset['ACTIVE'] !== 'Y') {
            $result->addError(new Error("Актив #$assetId не найден"));
            return $result;
        }

        // Close open assignment
        $assignRes = AssignmentTable::getList([
            'filter' => ['ASSET_ID' => $assetId, 'ACTIVE' => 'Y'],
            'limit'  => 1,
        ])->fetch();

        if ($assignRes) {
            AssignmentTable::update($assignRes['ID'], [
                'DATE_TO' => new DateTime(),
                'ACTIVE'  => 'N',
                'NOTE'    => $note ?: $assignRes['NOTE'],
            ]);
        }

        // Clear asset assignment
        $updateResult = AssetTable::update($assetId, [
            'ASSIGNED_TO' => null,
            'STATUS'      => 'pending',
        ]);

        if (!$updateResult->isSuccess()) {
            foreach ($updateResult->getErrors() as $error) {
                $result->addError($error);
            }
        }

        return $result;
    }

    /**
     * Get current assignments for a user.
     *
     * @param int $userId Bitrix user ID
     * @return array
     */
    public static function getUserAssets(int $userId): array
    {
        return AssetTable::getList([
            'filter' => ['ASSIGNED_TO' => $userId, 'ACTIVE' => 'Y'],
            'order'  => ['NAME' => 'ASC'],
        ])->fetchAll();
    }
}
