<?php

/**
 * Component template: snipeit.asset_list
 * Displays a paginated table of assets on public Bitrix pages.
 *
 * Available variables:
 *   $arResult['ITEMS']      — array of asset rows
 *   $arResult['TOTAL']      — total count
 *   $arParams['ITEMS_PER_PAGE'] — page size
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$items = $arResult['ITEMS'] ?? [];
?>
<div class="snipeit-asset-list">
    <div class="snipeit-asset-list__header">
        <h2>Активы</h2>
        <span class="snipeit-asset-list__count">Всего: <?= (int) ($arResult['TOTAL'] ?? count($items)) ?></span>
    </div>

    <?php if (empty($items)): ?>
        <p class="snipeit-asset-list__empty">Активов не найдено.</p>
    <?php else: ?>
    <table class="snipeit-asset-list__table">
        <thead>
        <tr>
            <th>Тег актива</th>
            <th>Название</th>
            <th>Серийный №</th>
            <th>Статус</th>
            <th>Назначен</th>
            <th>Дата покупки</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr class="snipeit-asset-list__row">
                <td><?= htmlspecialcharsbx($item['ASSET_TAG'] ?? '') ?></td>
                <td>
                    <a href="<?= $arResult['DETAIL_URL'] ?? '#' ?>?id=<?= (int) $item['ID'] ?>">
                        <?= htmlspecialcharsbx($item['NAME'] ?? '—') ?>
                    </a>
                </td>
                <td><?= htmlspecialcharsbx($item['SERIAL'] ?? '—') ?></td>
                <td>
                    <?php
                    $statusLabels = [
                        'deployable'   => 'Развёрнут',
                        'pending'      => 'Ожидание',
                        'archived'     => 'Архив',
                        'undeployable' => 'Недоступен',
                    ];
                    echo htmlspecialcharsbx($statusLabels[$item['STATUS'] ?? ''] ?? ($item['STATUS'] ?? '—'));
                    ?>
                </td>
                <td><?= $item['ASSIGNED_TO'] ? 'Пользователь #' . (int) $item['ASSIGNED_TO'] : '—' ?></td>
                <td><?= htmlspecialcharsbx($item['PURCHASE_DATE'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<style>
.snipeit-asset-list { font-family: sans-serif; }
.snipeit-asset-list__header { display: flex; align-items: center; gap: 1rem; margin-bottom: .75rem; }
.snipeit-asset-list__count  { color: #666; font-size: .9rem; }
.snipeit-asset-list__table  { width: 100%; border-collapse: collapse; }
.snipeit-asset-list__table th { background: #2c3e50; color: #fff; padding: .5rem .75rem; text-align: left; font-size: .85rem; }
.snipeit-asset-list__table td { padding: .5rem .75rem; border-bottom: 1px solid #eee; font-size: .9rem; }
.snipeit-asset-list__table tr:hover td { background: #f9f9f9; }
.snipeit-asset-list__empty  { color: #999; padding: 1rem 0; }
</style>
