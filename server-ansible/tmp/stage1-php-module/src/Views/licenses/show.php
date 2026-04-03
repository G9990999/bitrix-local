<?php /** @var \SnipeIT\Module\Models\License $license */ ?>
<h1>Лицензия: <?= htmlspecialchars($license->name ?? 'Без названия') ?></h1>

<div class="card">
    <table>
        <tr><th style="width:220px">Серийный номер</th><td><?= htmlspecialchars($license->serial ?? '—') ?></td></tr>
        <tr><th>Мест (всего)</th><td><?= $license->seats ?? '—' ?></td></tr>
        <tr><th>Мест (доступно)</th>
            <td>
                <?php if ($availableSeats > 0): ?>
                    <span class="badge badge-success"><?= $availableSeats ?></span>
                <?php else: ?>
                    <span class="badge badge-danger">0</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>Дата покупки</th><td><?= htmlspecialchars($license->purchase_date ?? '—') ?></td></tr>
        <tr><th>Стоимость</th><td><?= $license->purchase_cost !== null ? number_format($license->purchase_cost, 2) . ' ₽' : '—' ?></td></tr>
        <tr><th>Срок действия</th><td><?= htmlspecialchars($license->expiration_date ?? '—') ?></td></tr>
        <tr><th>Примечания</th><td><?= nl2br(htmlspecialchars($license->notes ?? '')) ?></td></tr>
        <tr><th>Создан</th><td><?= htmlspecialchars($license->created_at ?? '—') ?></td></tr>
    </table>
</div>

<div style="margin-top:1rem">
    <a href="/licenses" class="btn" style="background:#95a5a6;color:#fff">← Назад к списку</a>
</div>
