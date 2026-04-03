<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

$moduleId = 'local.snipeit';

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    if (isset($_POST['save'])) {
        Option::set($moduleId, 'ITEMS_PER_PAGE',    (int) ($_POST['ITEMS_PER_PAGE'] ?? 25));
        Option::set($moduleId, 'DEFAULT_COMPANY_ID',(int) ($_POST['DEFAULT_COMPANY_ID'] ?? 0));
    }
    if (isset($_POST['restore'])) {
        Option::delete($moduleId);
    }
}

$APPLICATION->SetTitle('Настройки: Snipe-IT Asset Management');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$itemsPerPage   = (int) Option::get($moduleId, 'ITEMS_PER_PAGE', 25);
$defaultCompany = (int) Option::get($moduleId, 'DEFAULT_COMPANY_ID', 0);

?>
<form method="post">
    <?= bitrix_sessid_post() ?>
    <table class="adm-detail-content-table">
        <tr>
            <td>Активов на странице</td>
            <td><input type="number" name="ITEMS_PER_PAGE" value="<?= $itemsPerPage ?>" min="5" max="200" style="width:80px"></td>
        </tr>
        <tr>
            <td>ID компании по умолчанию (0 = все)</td>
            <td><input type="number" name="DEFAULT_COMPANY_ID" value="<?= $defaultCompany ?>" min="0" style="width:80px"></td>
        </tr>
    </table>
    <button type="submit" name="save" class="adm-btn adm-btn-green">Сохранить</button>
    <button type="submit" name="restore" class="adm-btn"
            onclick="return confirm('Сбросить все настройки?')">Сбросить</button>
</form>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
