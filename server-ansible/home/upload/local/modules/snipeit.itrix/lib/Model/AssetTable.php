<?php
namespace Snipeit\Itrix\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Модель активов SnipeIT — PostgreSQL-таблица snipeit_assets.
 *
 * Поля:
 *   ID, NAME, ASSET_TAG, SERIAL, MODEL_ID, PURCHASE_DATE, PURCHASE_COST,
 *   ORDER_NUMBER, STATUS, ASSIGNED_TO, COMPANY_ID, LOCATION_ID, NOTES,
 *   DATE_CREATE, DATE_MODIFY, ACTIVE
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
            new IntegerField('ID', ['primary' => true, 'autocomplete' => true]),

            new StringField('NAME', [
                'required'   => true,
                'validators' => [new LengthValidator(1, 255)],
            ]),

            new StringField('ASSET_TAG', [
                'validators' => [new LengthValidator(null, 100)],
            ]),

            new StringField('SERIAL', [
                'validators' => [new LengthValidator(null, 100)],
            ]),

            new IntegerField('MODEL_ID'),

            new DateField('PURCHASE_DATE'),

            new FloatField('PURCHASE_COST'),

            new StringField('ORDER_NUMBER', [
                'validators' => [new LengthValidator(null, 100)],
            ]),

            new EnumField('STATUS', [
                'required' => true,
                'values'   => ['deployable', 'pending', 'archived', 'deployed'],
                'default'  => 'deployable',
            ]),

            new IntegerField('ASSIGNED_TO'),
            new IntegerField('COMPANY_ID'),
            new IntegerField('LOCATION_ID'),

            new TextField('NOTES'),

            new DatetimeField('DATE_CREATE', [
                'default' => new \Bitrix\Main\Type\DateTime(),
            ]),

            new DatetimeField('DATE_MODIFY', [
                'default' => new \Bitrix\Main\Type\DateTime(),
            ]),

            new StringField('ACTIVE', [
                'default'    => 'Y',
                'validators' => [new LengthValidator(1, 1)],
            ]),
        ];
    }
}
