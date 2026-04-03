<?php

/**
 * Component template: snipeit.asset_detail
 * Displays a single asset card.
 *
 * Available variables:
 *   $arResult['ASSET'] — asset row array (null if not found)
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$asset = $arResult['ASSET'] ?? null;
if (!$asset): ?>
<p>Актив не найден.</p>
<?php return; endif; ?>

<div class="snipeit-asset-detail">
    <h1 class="snipeit-asset-detail__title">
        <?= htmlspecialcharsbx($asset['NAME'] ?? 'Актив #' . $asset['ID']) ?>
    </h1>

    <table class="snipeit-asset-detail__table">
        <tr><th>Тег актива</th><td><?= htmlspecialcharsbx($asset['ASSET_TAG'] ?? '—') ?></td></tr>
        <tr><th>Серийный номер</th><td><?= htmlspecialcharsbx($asset['SERIAL'] ?? '—') ?></td></tr>
        <tr><th>Статус</th>
            <td>
                <?php
                $statusLabels = [
                    'deployable'   => 'Развёрнут',
                    'pending'      => 'Ожидание',
                    'archived'     => 'Архив',
                    'undeployable' => 'Недоступен',
                ];
                echo htmlspecialcharsbx($statusLabels[$asset['STATUS'] ?? ''] ?? ($asset['STATUS'] ?? '—'));
                ?>
            </td>
        </tr>
        <tr><th>Назначен</th>
            <td><?= $asset['ASSIGNED_TO'] ? 'Пользователь #' . (int) $asset['ASSIGNED_TO'] : '—' ?></td>
        </tr>
        <tr><th>Дата покупки</th><td><?= htmlspecialcharsbx($asset['PURCHASE_DATE'] ?? '—') ?></td></tr>
        <tr><th>Стоимость</th>
            <td><?= $asset['PURCHASE_COST'] !== null
                    ? number_format((float)$asset['PURCHASE_COST'], 2) . ' ₽'
                    : '—' ?>
            </td>
        </tr>
        <tr><th>Номер заказа</th><td><?= htmlspecialcharsbx($asset['ORDER_NUMBER'] ?? '—') ?></td></tr>
        <tr><th>Примечания</th><td><?= nl2br(htmlspecialcharsbx($asset['NOTES'] ?? '')) ?></td></tr>
        <tr><th>Дата добавления</th><td><?= htmlspecialcharsbx((string)($asset['DATE_CREATE'] ?? '—')) ?></td></tr>
        <tr><th>Последнее изменение</th><td><?= htmlspecialcharsbx((string)($asset['DATE_MODIFY'] ?? '—')) ?></td></tr>
    </table>

    <div class="snipeit-asset-detail__actions">
        <a href="<?= $arResult['LIST_URL'] ?? '#' ?>" class="snipeit-asset-detail__back">← Назад к списку</a>
    </div>
</div>

<style>
.snipeit-asset-detail { font-family: sans-serif; max-width: 700px; }
.snipeit-asset-detail__title { margin-bottom: 1rem; font-size: 1.4rem; }
.snipeit-asset-detail__table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
.snipeit-asset-detail__table th { width: 200px; text-align: left; padding: .5rem .75rem; background: #f5f5f5; font-weight: 600; font-size: .85rem; color: #555; border-bottom: 1px solid #eee; }
.snipeit-asset-detail__table td { padding: .5rem .75rem; border-bottom: 1px solid #eee; font-size: .9rem; }
.snipeit-asset-detail__back { color: #3498db; text-decoration: none; font-size: .9rem; }
</style>
