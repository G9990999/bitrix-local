<?php

namespace Local\SnipeIT\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

/**
 * ORM data manager for snipeit_assets table.
 *
 * Usage:
 *   $result = AssetTable::getList(['filter' => ['ACTIVE' => 'Y']]);
 *   while ($row = $result->fetch()) { ... }
 *
 *   $result = AssetTable::add(['NAME' => 'ThinkPad X1', 'ASSET_TAG' => 'A-001', ...]);
 */
class AssetTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'snipeit_assets';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new StringField('NAME'))
                ->configureTitle('Название')
                ->configureNullable(),

            (new StringField('ASSET_TAG'))
                ->configureTitle('Тег актива')
                ->configureNullable()
                ->addValidator(new LengthValidator(null, 255)),

            (new StringField('SERIAL'))
                ->configureTitle('Серийный номер')
                ->configureNullable(),

            (new IntegerField('MODEL_ID'))
                ->configureTitle('Модель')
                ->configureNullable(),

            (new StringField('PURCHASE_DATE'))
                ->configureTitle('Дата покупки')
                ->configureNullable(),

            (new FloatField('PURCHASE_COST'))
                ->configureTitle('Стоимость')
                ->configureNullable(),

            (new StringField('ORDER_NUMBER'))
                ->configureTitle('Номер заказа')
                ->configureNullable(),

            (new EnumField('STATUS'))
                ->configureTitle('Статус')
                ->configureValues(['deployable', 'pending', 'archived', 'undeployable'])
                ->configureDefaultValue('pending'),

            (new IntegerField('ASSIGNED_TO'))
                ->configureTitle('Назначен пользователю (ID)')
                ->configureNullable(),

            (new IntegerField('COMPANY_ID'))
                ->configureTitle('Компания')
                ->configureNullable(),

            (new IntegerField('LOCATION_ID'))
                ->configureTitle('Расположение')
                ->configureNullable(),

            (new StringField('NOTES'))
                ->configureTitle('Примечания')
                ->configureNullable(),

            (new DatetimeField('DATE_CREATE'))
                ->configureTitle('Дата создания')
                ->configureNullable(),

            (new DatetimeField('DATE_MODIFY'))
                ->configureTitle('Дата изменения')
                ->configureNullable(),

            (new StringField('ACTIVE'))
                ->configureTitle('Активен')
                ->configureDefaultValue('Y'),
        ];
    }

    public static function onBeforeAdd(\Bitrix\Main\ORM\Event $event): \Bitrix\Main\ORM\EventResult
    {
        $result = new \Bitrix\Main\ORM\EventResult();
        $result->modifyFields(['DATE_CREATE' => new DateTime(), 'DATE_MODIFY' => new DateTime()]);
        return $result;
    }

    public static function onBeforeUpdate(\Bitrix\Main\ORM\Event $event): \Bitrix\Main\ORM\EventResult
    {
        $result = new \Bitrix\Main\ORM\EventResult();
        $result->modifyFields(['DATE_MODIFY' => new DateTime()]);
        return $result;
    }
}
