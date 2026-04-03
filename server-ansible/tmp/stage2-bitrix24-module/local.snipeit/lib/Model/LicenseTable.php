<?php

namespace Local\SnipeIT\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * ORM data manager for snipeit_licenses table.
 */
class LicenseTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'snipeit_licenses';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new StringField('NAME'))
                ->configureTitle('Название')
                ->configureRequired(),

            (new StringField('SERIAL'))
                ->configureTitle('Серийный номер / ключ активации')
                ->configureNullable(),

            (new IntegerField('SEATS'))
                ->configureTitle('Количество мест')
                ->configureDefaultValue(1),

            (new StringField('PURCHASE_DATE'))
                ->configureTitle('Дата покупки')
                ->configureNullable(),

            (new FloatField('PURCHASE_COST'))
                ->configureTitle('Стоимость покупки')
                ->configureNullable(),

            (new StringField('EXPIRATION_DATE'))
                ->configureTitle('Срок действия')
                ->configureNullable(),

            (new IntegerField('COMPANY_ID'))
                ->configureTitle('Компания')
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
                ->configureTitle('Активна')
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

    /** Count seats currently assigned (via snipeit_assignments) */
    public static function getUsedSeats(int $licenseId): int
    {
        $res = \Bitrix\Main\Application::getConnection()->query(
            "SELECT COUNT(*) AS CNT FROM snipeit_assignments WHERE ASSET_ID IN
             (SELECT ID FROM snipeit_assets WHERE ACTIVE='Y') AND ACTIVE='Y'"
        );
        // Simplified: in real impl link via license_seat table
        return 0;
    }
}
