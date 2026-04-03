<?php
namespace Snipeit\Itrix\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Модель лицензий SnipeIT — таблица snipeit_licenses.
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
            new IntegerField('ID', ['primary' => true, 'autocomplete' => true]),

            new StringField('NAME', [
                'required'   => true,
                'validators' => [new LengthValidator(1, 255)],
            ]),

            new StringField('PRODUCT_KEY', [
                'validators' => [new LengthValidator(null, 255)],
            ]),

            new StringField('SERIAL', [
                'validators' => [new LengthValidator(null, 100)],
            ]),

            new IntegerField('MANUFACTURER_ID'),

            new DateField('PURCHASE_DATE'),

            new FloatField('PURCHASE_COST'),

            new IntegerField('SEATS', ['default' => 1]),

            new IntegerField('SEATS_USED', ['default' => 0]),

            new StringField('LICENSE_EMAIL', [
                'validators' => [new LengthValidator(null, 255)],
            ]),

            new StringField('LICENSE_NAME', [
                'validators' => [new LengthValidator(null, 255)],
            ]),

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
