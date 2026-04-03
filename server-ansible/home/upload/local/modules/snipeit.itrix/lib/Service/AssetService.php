<?php
namespace Snipeit\Itrix\Service;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Snipeit\Itrix\Model\AssetTable;

/**
 * Сервис управления активами SnipeIT.
 *
 * Оборачивает ORM AssetTable в бизнес-логику с валидацией.
 */
class AssetService
{
    /**
     * Возвращает список активов с фильтрацией.
     *
     * @param array $filter  Фильтр Bitrix ORM
     * @param array $select  Поля для выборки
     * @param array $order   Сортировка
     * @param int   $limit   Ограничение числа записей
     */
    public static function getAssets(
        array $filter = ['ACTIVE' => 'Y'],
        array $select = ['*'],
        array $order  = ['DATE_CREATE' => 'DESC'],
        int   $limit  = 50
    ): array {
        $result = AssetTable::getList([
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

    /**
     * Возвращает один актив по ID.
     */
    public static function getAsset(int $id): ?array
    {
        return AssetTable::getByPrimary($id)?->fetch() ?: null;
    }

    /**
     * Создаёт новый актив.
     *
     * @param array $fields  NAME, ASSET_TAG, SERIAL, STATUS, ...
     */
    public static function createAsset(array $fields): Result
    {
        $result = new Result();

        if (empty($fields['NAME'])) {
            $result->addError(new Error('Поле NAME обязательно.', 'EMPTY_NAME'));
            return $result;
        }

        // Проверка уникальности ASSET_TAG
        if (!empty($fields['ASSET_TAG'])) {
            $exists = AssetTable::getList([
                'filter' => ['ASSET_TAG' => $fields['ASSET_TAG']],
                'limit'  => 1,
            ])->fetch();

            if ($exists) {
                $result->addError(new Error(
                    "Asset Tag '{$fields['ASSET_TAG']}' уже используется.",
                    'DUPLICATE_ASSET_TAG'
                ));
                return $result;
            }
        }

        $fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
        $fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
        $fields['ACTIVE']      = $fields['ACTIVE'] ?? 'Y';

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
     * Обновляет актив.
     */
    public static function updateAsset(int $id, array $fields): Result
    {
        $result = new Result();

        $existing = self::getAsset($id);
        if (!$existing) {
            $result->addError(new Error("Актив с ID={$id} не найден.", 'NOT_FOUND'));
            return $result;
        }

        $fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();

        $updateResult = AssetTable::update($id, $fields);
        if (!$updateResult->isSuccess()) {
            foreach ($updateResult->getErrors() as $error) {
                $result->addError($error);
            }
        }

        return $result;
    }

    /**
     * Мягкое удаление (ACTIVE = N).
     */
    public static function deleteAsset(int $id): Result
    {
        return self::updateAsset($id, ['ACTIVE' => 'N']);
    }

    /**
     * Полнотекстовый поиск по NAME и SERIAL.
     */
    public static function searchAssets(string $query): array
    {
        $like = '%' . $query . '%';
        return self::getAssets([
            'LOGIC' => 'OR',
            '%NAME' => $like,
            '%SERIAL' => $like,
        ]);
    }
}
