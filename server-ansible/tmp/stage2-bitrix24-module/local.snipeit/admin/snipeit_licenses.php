<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Local\SnipeIT\Model\LicenseTable;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule('local.snipeit');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    $licId  = (int) ($_POST['LIC_ID'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && $licId > 0) {
        LicenseTable::update($licId, ['ACTIVE' => 'N']);
    } elseif ($action === 'save') {
        $fields = [
            'NAME'            => $_POST['NAME'] ?? '',
            'SERIAL'          => $_POST['SERIAL'] ?? '',
            'SEATS'           => (int) ($_POST['SEATS'] ?? 1),
            'PURCHASE_COST'   => $_POST['PURCHASE_COST'] !== '' ? (float) $_POST['PURCHASE_COST'] : null,
            'PURCHASE_DATE'   => $_POST['PURCHASE_DATE'] ?: null,
            'EXPIRATION_DATE' => $_POST['EXPIRATION_DATE'] ?: null,
            'NOTES'           => $_POST['NOTES'] ?? '',
        ];

        if ($licId > 0) {
            LicenseTable::update($licId, $fields);
        } else {
            $result = LicenseTable::add($fields);
            if (!$result->isSuccess()) {
                $errorMessage = implode('; ', array_map(fn($e) => $e->getMessage(), $result->getErrors()));
            }
        }
    }
}

$APPLICATION->SetTitle('Лицензии Snipe-IT');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$licenses = LicenseTable::getList([
    'filter' => ['ACTIVE' => 'Y'],
    'order'  => ['NAME'   => 'ASC'],
])->fetchAll();

?>
<?php if (!empty($errorMessage)): ?>
<div class="adm-note-red"><?= htmlspecialcharsbx($errorMessage) ?></div>
<?php endif; ?>

<h2>Добавить лицензию</h2>
<form method="post">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="LIC_ID" value="0">
    <table class="adm-detail-content-table">
        <tr><td>Название *</td><td><input type="text" name="NAME" required style="width:300px"></td></tr>
        <tr><td>Серийный / ключ</td><td><input type="text" name="SERIAL" style="width:300px"></td></tr>
        <tr><td>Мест</td><td><input type="number" name="SEATS" value="1" min="1" style="width:80px"></td></tr>
        <tr><td>Стоимость</td><td><input type="number" name="PURCHASE_COST" step="0.01" style="width:120px"></td></tr>
        <tr><td>Дата покупки</td><td><input type="date" name="PURCHASE_DATE"></td></tr>
        <tr><td>Срок действия</td><td><input type="date" name="EXPIRATION_DATE"></td></tr>
        <tr><td>Примечания</td><td><textarea name="NOTES" rows="3" style="width:300px"></textarea></td></tr>
    </table>
    <button type="submit" class="adm-btn adm-btn-green">Сохранить</button>
</form>

<hr>
<h2>Список лицензий</h2>
<table class="adm-list-table" cellspacing="0" cellpadding="0">
    <thead>
    <tr>
        <th>ID</th>
        <th>Название</th>
        <th>Серийный №</th>
        <th>Мест</th>
        <th>Срок действия</th>
        <th>Стоимость</th>
        <th>Действия</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($licenses as $lic): ?>
    <tr class="adm-list-table-row">
        <td><?= $lic['ID'] ?></td>
        <td><?= htmlspecialcharsbx($lic['NAME']) ?></td>
        <td><?= htmlspecialcharsbx($lic['SERIAL'] ?? '') ?></td>
        <td><?= (int) $lic['SEATS'] ?></td>
        <td>
            <?php if ($lic['EXPIRATION_DATE']): ?>
                <?php
                $exp = new \DateTime($lic['EXPIRATION_DATE']);
                $now = new \DateTime();
                $color = $exp < $now ? 'red' : ($exp->diff($now)->days < 30 ? 'orange' : 'green');
                ?>
                <span style="color:<?= $color ?>"><?= htmlspecialcharsbx($lic['EXPIRATION_DATE']) ?></span>
            <?php else: ?>
                —
            <?php endif; ?>
        </td>
        <td><?= $lic['PURCHASE_COST'] !== null ? number_format((float)$lic['PURCHASE_COST'], 2) . ' ₽' : '—' ?></td>
        <td>
            <form method="post" style="display:inline"
                  onsubmit="return confirm('Удалить лицензию?')">
                <?= bitrix_sessid_post() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="LIC_ID" value="<?= $lic['ID'] ?>">
                <button type="submit" class="adm-btn adm-btn-red">Удалить</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($licenses)): ?>
    <tr><td colspan="7" style="text-align:center;padding:1rem;color:#999">Лицензий нет</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
