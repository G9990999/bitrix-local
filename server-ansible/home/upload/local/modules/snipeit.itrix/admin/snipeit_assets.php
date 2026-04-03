<?php
/**
 * Страница администрирования активов SnipeIT в Bitrix24.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Snipeit\Itrix\Service\AssetService;

Loader::includeModule('snipeit.itrix');

$APPLICATION->SetTitle('Активы (SnipeIT)');

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$action = $_REQUEST['action'] ?? 'list';
$id     = (int)($_REQUEST['id'] ?? 0);

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $result = AssetService::createAsset([
            'NAME'      => $_POST['name'] ?? '',
            'ASSET_TAG' => $_POST['asset_tag'] ?? '',
            'SERIAL'    => $_POST['serial'] ?? '',
            'STATUS'    => $_POST['status'] ?? 'deployable',
            'NOTES'     => $_POST['notes'] ?? '',
        ]);

        if ($result->isSuccess()) {
            LocalRedirect('/bitrix/admin/snipeit_assets.php?action=list&created=1');
        }
    }

    if ($action === 'delete' && $id > 0) {
        AssetService::deleteAsset($id);
        LocalRedirect('/bitrix/admin/snipeit_assets.php?action=list&deleted=1');
    }
}

$assets = AssetService::getAssets();
$statusLabels = [
    'deployable' => 'Доступен',
    'deployed'   => 'Выдан',
    'pending'    => 'Ожидание',
    'archived'   => 'Архив',
];
?>

<?php if (isset($_GET['created'])): ?>
<div class="bx-messenger-notification-item">Актив успешно создан.</div>
<?php endif; ?>

<?php if ($action === 'create'): ?>
<form method="post" action="/bitrix/admin/snipeit_assets.php?action=create">
    <table class="edit-table">
        <tr><th>Название</th><td><input type="text" name="name" required></td></tr>
        <tr><th>Asset Tag</th><td><input type="text" name="asset_tag"></td></tr>
        <tr><th>Серийный номер</th><td><input type="text" name="serial"></td></tr>
        <tr><th>Статус</th>
            <td>
                <select name="status">
                    <option value="deployable">Доступен</option>
                    <option value="pending">Ожидание</option>
                    <option value="archived">Архив</option>
                </select>
            </td>
        </tr>
        <tr><th>Заметки</th><td><textarea name="notes" rows="3" cols="40"></textarea></td></tr>
        <tr><td colspan="2"><input type="submit" value="Сохранить"></td></tr>
    </table>
</form>
<?php else: ?>

<p><a href="/bitrix/admin/snipeit_assets.php?action=create" class="adm-btn-save">+ Добавить актив</a></p>

<table class="data-table" border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Asset Tag</th>
            <th>Серийный номер</th>
            <th>Статус</th>
            <th>Создан</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($assets as $asset): ?>
        <tr>
            <td><?= htmlspecialchars($asset['ID']) ?></td>
            <td><?= htmlspecialchars($asset['NAME']) ?></td>
            <td><?= htmlspecialchars($asset['ASSET_TAG'] ?? '—') ?></td>
            <td><?= htmlspecialchars($asset['SERIAL'] ?? '—') ?></td>
            <td><?= htmlspecialchars($statusLabels[$asset['STATUS']] ?? $asset['STATUS']) ?></td>
            <td><?= htmlspecialchars((string)$asset['DATE_CREATE']) ?></td>
            <td>
                <a href="?action=delete&id=<?= (int)$asset['ID'] ?>"
                   onclick="return confirm('Удалить актив?')">Удалить</a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($assets)): ?>
        <tr><td colspan="7">Активы не найдены.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php endif; ?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
