<?php
namespace Snipeit\Itrix\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Модель назначений активов/лицензий пользователям — таблица snipeit_user_assignments.
 */
class UserAssignmentTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'snipeit_user_assignments';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField('ID', ['primary' => true, 'autocomplete' => true]),

            new IntegerField('USER_ID', ['required' => true]),

            new IntegerField('ASSET_ID'),

            new IntegerField('LICENSE_ID'),

            new DatetimeField('ASSIGNED_AT', [
                'default' => new \Bitrix\Main\Type\DateTime(),
            ]),

            new DatetimeField('RETURNED_AT'),

            new TextField('NOTES'),
        ];
    }
}
