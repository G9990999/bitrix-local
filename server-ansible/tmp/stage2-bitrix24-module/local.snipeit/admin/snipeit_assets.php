<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Local\SnipeIT\Service\AssetService;
use Local\SnipeIT\Service\CheckoutService;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule('local.snipeit');

// Handle POST actions
$action = $_REQUEST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    $assetId = (int) ($_POST['ASSET_ID'] ?? 0);

    if ($action === 'checkout' && $assetId > 0) {
        $userId = (int) ($_POST['USER_ID'] ?? 0);
        $result = CheckoutService::checkout($assetId, $userId, $_POST['NOTE'] ?? '');
        if (!$result->isSuccess()) {
            $errorMessage = implode(', ', array_map(fn($e) => $e->getMessage(), $result->getErrors()));
        }
    } elseif ($action === 'checkin' && $assetId > 0) {
        CheckoutService::checkin($assetId);
    } elseif ($action === 'delete' && $assetId > 0) {
        AssetService::deleteAsset($assetId);
    } elseif ($action === 'save') {
        $fields = [
            'NAME'          => $_POST['NAME'] ?? '',
            'ASSET_TAG'     => $_POST['ASSET_TAG'] ?? '',
            'SERIAL'        => $_POST['SERIAL'] ?? '',
            'PURCHASE_DATE' => $_POST['PURCHASE_DATE'] ?? null,
            'PURCHASE_COST' => $_POST['PURCHASE_COST'] !== '' ? (float) $_POST['PURCHASE_COST'] : null,
            'ORDER_NUMBER'  => $_POST['ORDER_NUMBER'] ?? '',
            'NOTES'         => $_POST['NOTES'] ?? '',
        ];

        if ($assetId > 0) {
            AssetService::updateAsset($assetId, $fields);
        } else {
            $result = AssetService::createAsset($fields);
            if (!$result->isSuccess()) {
                $errorMessage = implode(', ', array_map(fn($e) => $e->getMessage(), $result->getErrors()));
            }
        }
    }
}

$APPLICATION->SetTitle(Loc::getMessage('SNIPEIT_ASSETS_TITLE') ?: 'Активы Snipe-IT');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$assets = AssetService::getAssets([], 200);

?>
<?php if (!empty($errorMessage)): ?>
<div class="adm-note-red"><?= htmlspecialcharsbx($errorMessage) ?></div>
<?php endif; ?>

<form method="get" action="">
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="hidden" name="module" value="local.snipeit">
</form>

<table class="adm-list-table" cellspacing="0" cellpadding="0">
    <caption>
        <b><?= count($assets) ?></b> активов &nbsp;
        <a href="?action=edit&ASSET_ID=0" class="adm-btn">+ Добавить</a>
    </caption>
    <thead>
    <tr>
        <th>ID</th>
        <th>Тег</th>
        <th>Название</th>
        <th>Серийный №</th>
        <th>Статус</th>
        <th>Назначен (User ID)</th>
        <th>Действия</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($assets as $asset): ?>
    <tr class="adm-list-table-row">
        <td><?= $asset['ID'] ?></td>
        <td><?= htmlspecialcharsbx($asset['ASSET_TAG'] ?? '') ?></td>
        <td><?= htmlspecialcharsbx($asset['NAME'] ?? '') ?></td>
        <td><?= htmlspecialcharsbx($asset['SERIAL'] ?? '') ?></td>
        <td>
            <?php
            $statusMap = [
                'deployable'   => '<span style="color:green">Развёрнут</span>',
                'pending'      => '<span style="color:orange">Ожидание</span>',
                'archived'     => '<span style="color:gray">Архив</span>',
                'undeployable' => '<span style="color:red">Недоступен</span>',
            ];
            echo $statusMap[$asset['STATUS']] ?? htmlspecialcharsbx($asset['STATUS'] ?? '');
            ?>
        </td>
        <td>
            <?= $asset['ASSIGNED_TO'] ? '#' . $asset['ASSIGNED_TO'] : '<em>Свободен</em>' ?>
        </td>
        <td>
            <?php if (!$asset['ASSIGNED_TO']): ?>
            <form method="post" style="display:inline">
                <?= bitrix_sessid_post() ?>
                <input type="hidden" name="action" value="checkout">
                <input type="hidden" name="ASSET_ID" value="<?= $asset['ID'] ?>">
                <input type="number" name="USER_ID" placeholder="User ID" style="width:80px">
                <button type="submit" class="adm-btn">Выдать</button>
            </form>
            <?php else: ?>
            <form method="post" style="display:inline">
                <?= bitrix_sessid_post() ?>
                <input type="hidden" name="action" value="checkin">
                <input type="hidden" name="ASSET_ID" value="<?= $asset['ID'] ?>">
                <button type="submit" class="adm-btn adm-btn-red">Принять</button>
            </form>
            <?php endif; ?>

            <form method="post" style="display:inline"
                  onsubmit="return confirm('Удалить актив?')">
                <?= bitrix_sessid_post() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="ASSET_ID" value="<?= $asset['ID'] ?>">
                <button type="submit" class="adm-btn adm-btn-red">Удалить</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($assets)): ?>
    <tr><td colspan="7" style="text-align:center;padding:1rem;color:#999">Активов нет</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
