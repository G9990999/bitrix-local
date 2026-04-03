<?php /** @var \SnipeIT\Module\Models\Asset $asset */ ?>
<h1>Актив: <?= htmlspecialchars($asset->name ?? 'Без названия') ?></h1>

<div class="card">
    <table>
        <tr><th style="width:220px">Тег актива</th><td><?= htmlspecialchars($asset->asset_tag ?? '—') ?></td></tr>
        <tr><th>Серийный номер</th><td><?= htmlspecialchars($asset->serial ?? '—') ?></td></tr>
        <tr><th>Дата покупки</th><td><?= htmlspecialchars($asset->purchase_date ?? '—') ?></td></tr>
        <tr><th>Стоимость</th><td><?= $asset->purchase_cost !== null ? number_format($asset->purchase_cost, 2) . ' ₽' : '—' ?></td></tr>
        <tr><th>Номер заказа</th><td><?= htmlspecialchars($asset->order_number ?? '—') ?></td></tr>
        <tr><th>Гарантия (мес.)</th><td><?= htmlspecialchars($asset->warranty_months ?? '—') ?></td></tr>
        <tr><th>Примечания</th><td><?= nl2br(htmlspecialchars($asset->notes ?? '')) ?></td></tr>
        <tr><th>Назначен</th>
            <td>
                <?php if ($asset->assigned_to): ?>
                    <span class="badge badge-warning">Пользователь #<?= $asset->assigned_to ?></span>
                <?php else: ?>
                    <span class="badge badge-success">Свободен</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>Создан</th><td><?= htmlspecialchars($asset->created_at ?? '—') ?></td></tr>
        <tr><th>Обновлён</th><td><?= htmlspecialchars($asset->updated_at ?? '—') ?></td></tr>
    </table>
</div>

<div style="display:flex;gap:0.5rem;margin-top:1rem">
    <a href="/assets/<?= $asset->id ?>/edit" class="btn btn-primary">Редактировать</a>
    <?php if ($asset->assigned_to === null): ?>
        <button class="btn btn-success" onclick="document.getElementById('checkout-form').style.display='block'">Выдать</button>
    <?php else: ?>
        <form method="post" action="/assets/<?= $asset->id ?>/checkin">
            <button type="submit" class="btn btn-warning" style="background:#f39c12;color:#fff">Принять обратно</button>
        </form>
    <?php endif; ?>
    <a href="/assets" class="btn" style="background:#95a5a6;color:#fff">← Назад</a>
</div>

<div id="checkout-form" class="card" style="display:none;margin-top:1rem">
    <h2 style="font-size:1.1rem;margin-bottom:1rem">Выдать актив пользователю</h2>
    <form method="post" action="/assets/<?= $asset->id ?>/checkout">
        <div class="form-group">
            <label>ID пользователя</label>
            <input type="number" name="assigned_to" required min="1">
        </div>
        <button type="submit" class="btn btn-success">Выдать</button>
    </form>
</div>
