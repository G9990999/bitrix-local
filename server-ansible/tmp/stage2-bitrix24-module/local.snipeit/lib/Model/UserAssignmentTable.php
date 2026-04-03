<?php

namespace Local\SnipeIT\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * ORM data manager for snipeit_assignments table.
 * Tracks which assets are checked out to which Bitrix users.
 */
class AssignmentTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'snipeit_assignments';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new IntegerField('ASSET_ID'))
                ->configureTitle('Актив')
                ->configureRequired(),

            (new IntegerField('USER_ID'))
                ->configureTitle('Пользователь Bitrix')
                ->configureRequired(),

            (new DatetimeField('DATE_FROM'))
                ->configureTitle('Дата выдачи')
                ->configureNullable(),

            (new DatetimeField('DATE_TO'))
                ->configureTitle('Дата возврата')
                ->configureNullable(),

            (new StringField('NOTE'))
                ->configureTitle('Примечание')
                ->configureNullable(),

            (new StringField('ACTIVE'))
                ->configureTitle('Активно')
                ->configureDefaultValue('Y'),
        ];
    }
}
