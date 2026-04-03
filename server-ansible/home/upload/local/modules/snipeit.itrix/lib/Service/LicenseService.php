<?php
namespace Snipeit\Itrix\Service;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Snipeit\Itrix\Model\LicenseTable;

/**
 * Сервис управления лицензиями SnipeIT.
 */
class LicenseService
{
    public static function getLicenses(
        array $filter = ['ACTIVE' => 'Y'],
        array $select = ['*'],
        array $order  = ['DATE_CREATE' => 'DESC'],
        int   $limit  = 50
    ): array {
        $result = LicenseTable::getList([
            'filter' => $filter,
            'select' => $select,
            'order'  => $order,
            'limit'  => $limit,
        ]);

        $items = [];
        while ($row = $result->fetch()) {
            $items[] = $row;
        }
        return $items;
    }

    public static function getLicense(int $id): ?array
    {
        return LicenseTable::getByPrimary($id)?->fetch() ?: null;
    }

    public static function createLicense(array $fields): Result
    {
        $result = new Result();

        if (empty($fields['NAME'])) {
            $result->addError(new Error('Поле NAME обязательно.', 'EMPTY_NAME'));
            return $result;
        }

        if (empty($fields['SEATS']) || $fields['SEATS'] < 1) {
            $fields['SEATS'] = 1;
        }

        $fields['SEATS_USED']  = $fields['SEATS_USED'] ?? 0;
        $fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
        $fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
        $fields['ACTIVE']      = $fields['ACTIVE'] ?? 'Y';

        $addResult = LicenseTable::add($fields);
        if (!$addResult->isSuccess()) {
            foreach ($addResult->getErrors() as $error) {
                $result->addError($error);
            }
        } else {
            $result->setData(['ID' => $addResult->getId()]);
        }

        return $result;
    }

    public static function updateLicense(int $id, array $fields): Result
    {
        $result = new Result();

        if (!self::getLicense($id)) {
            $result->addError(new Error("Лицензия с ID={$id} не найдена.", 'NOT_FOUND'));
            return $result;
        }

        $fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();

        $updateResult = LicenseTable::update($id, $fields);
        if (!$updateResult->isSuccess()) {
            foreach ($updateResult->getErrors() as $error) {
                $result->addError($error);
            }
        }

        return $result;
    }

    public static function deleteLicense(int $id): Result
    {
        return self::updateLicense($id, ['ACTIVE' => 'N']);
    }

    /**
     * Проверяет, есть ли свободные места в лицензии.
     */
    public static function hasAvailableSeats(int $id): bool
    {
        $license = self::getLicense($id);
        if (!$license) return false;
        return (int)$license['SEATS'] > (int)$license['SEATS_USED'];
    }
}
