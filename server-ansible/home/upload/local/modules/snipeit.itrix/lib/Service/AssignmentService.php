<?php
namespace Snipeit\Itrix\Service;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Snipeit\Itrix\Model\AssetTable;
use Snipeit\Itrix\Model\LicenseTable;
use Snipeit\Itrix\Model\UserAssignmentTable;

/**
 * Сервис назначения активов и лицензий пользователям.
 *
 * Аудит доступов: позволяет видеть, кому что выдано.
 */
class AssignmentService
{
    /**
     * Назначает актив пользователю.
     */
    public static function checkoutAsset(int $userId, int $assetId, ?string $notes = null): Result
    {
        $result = new Result();

        // Проверяем актив
        $asset = AssetTable::getByPrimary($assetId)?->fetch();
        if (!$asset) {
            $result->addError(new Error("Актив ID={$assetId} не найден.", 'ASSET_NOT_FOUND'));
            return $result;
        }

        if ($asset['STATUS'] !== 'deployable') {
            $result->addError(new Error(
                "Актив не доступен для выдачи (статус: {$asset['STATUS']}).",
                'ASSET_NOT_DEPLOYABLE'
            ));
            return $result;
        }

        // Создаём запись о назначении
        $addResult = UserAssignmentTable::add([
            'USER_ID'     => $userId,
            'ASSET_ID'    => $assetId,
            'ASSIGNED_AT' => new \Bitrix\Main\Type\DateTime(),
            'NOTES'       => $notes,
        ]);

        if (!$addResult->isSuccess()) {
            foreach ($addResult->getErrors() as $error) {
                $result->addError($error);
            }
            return $result;
        }

        // Обновляем статус актива
        AssetTable::update($assetId, [
            'STATUS'      => 'deployed',
            'ASSIGNED_TO' => $userId,
            'DATE_MODIFY' => new \Bitrix\Main\Type\DateTime(),
        ]);

        $result->setData(['ASSIGNMENT_ID' => $addResult->getId()]);
        return $result;
    }

    /**
     * Возвращает актив (checkin).
     */
    public static function checkinAsset(int $assetId, ?string $notes = null): Result
    {
        $result = new Result();

        // Находим активное назначение
        $assignment = UserAssignmentTable::getList([
            'filter' => ['ASSET_ID' => $assetId, '=RETURNED_AT' => null],
            'limit'  => 1,
        ])->fetch();

        if (!$assignment) {
            $result->addError(new Error("Активное назначение для актива ID={$assetId} не найдено.", 'NOT_FOUND'));
            return $result;
        }

        // Закрываем назначение
        UserAssignmentTable::update($assignment['ID'], [
            'RETURNED_AT' => new \Bitrix\Main\Type\DateTime(),
            'NOTES'       => $notes,
        ]);

        // Освобождаем актив
        AssetTable::update($assetId, [
            'STATUS'      => 'deployable',
            'ASSIGNED_TO' => null,
            'DATE_MODIFY' => new \Bitrix\Main\Type\DateTime(),
        ]);

        return $result;
    }

    /**
     * Назначает лицензию пользователю.
     */
    public static function checkoutLicense(int $userId, int $licenseId, ?string $notes = null): Result
    {
        $result = new Result();

        if (!LicenseService::hasAvailableSeats($licenseId)) {
            $result->addError(new Error(
                "Нет свободных мест в лицензии ID={$licenseId}.",
                'NO_SEATS_AVAILABLE'
            ));
            return $result;
        }

        $addResult = UserAssignmentTable::add([
            'USER_ID'     => $userId,
            'LICENSE_ID'  => $licenseId,
            'ASSIGNED_AT' => new \Bitrix\Main\Type\DateTime(),
            'NOTES'       => $notes,
        ]);

        if (!$addResult->isSuccess()) {
            foreach ($addResult->getErrors() as $error) {
                $result->addError($error);
            }
            return $result;
        }

        // Увеличиваем счётчик использованных мест
        $license = LicenseService::getLicense($licenseId);
        LicenseTable::update($licenseId, [
            'SEATS_USED'  => (int)$license['SEATS_USED'] + 1,
            'DATE_MODIFY' => new \Bitrix\Main\Type\DateTime(),
        ]);

        $result->setData(['ASSIGNMENT_ID' => $addResult->getId()]);
        return $result;
    }

    /**
     * Возвращает список всех назначений для аудита.
     *
     * @param array $filter  Например: ['USER_ID' => 5] или ['LICENSE_ID' => 3]
     */
    public static function getAssignments(array $filter = []): array
    {
        $result = UserAssignmentTable::getList([
            'filter' => $filter,
            'order'  => ['ASSIGNED_AT' => 'DESC'],
            'limit'  => 200,
        ]);

        $items = [];
        while ($row = $result->fetch()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Аудит: кто имеет доступ к активу или лицензии.
     */
    public static function getAssignedUsers(int $assetId = 0, int $licenseId = 0): array
    {
        $filter = ['=RETURNED_AT' => null];
        if ($assetId > 0)   $filter['ASSET_ID']   = $assetId;
        if ($licenseId > 0) $filter['LICENSE_ID']  = $licenseId;
        return self::getAssignments($filter);
    }
}
