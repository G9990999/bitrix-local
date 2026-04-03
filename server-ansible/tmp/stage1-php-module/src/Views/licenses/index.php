<?php /** @var \SnipeIT\Module\Models\License[] $licenses */ ?>
<h1>Лицензии</h1>
<div class="toolbar">
    <a href="/licenses/create" class="btn btn-success">+ Добавить лицензию</a>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Серийный номер</th>
            <th>Мест всего</th>
            <th>Доступно</th>
            <th>Срок действия</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($licenses as $license): ?>
        <?php $available = $license->availableSeats(); ?>
        <tr>
            <td><?= $license->id ?></td>
            <td><a href="/licenses/<?= $license->id ?>"><?= htmlspecialchars($license->name ?? '—') ?></a></td>
            <td><?= htmlspecialchars($license->serial ?? '—') ?></td>
            <td><?= $license->seats ?? '—' ?></td>
            <td>
                <?php if ($available > 0): ?>
                    <span class="badge badge-success"><?= $available ?></span>
                <?php else: ?>
                    <span class="badge badge-danger">0</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($license->expiration_date ?? '—') ?></td>
            <td>
                <form class="inline" method="post" action="/licenses/<?= $license->id ?>/delete"
                      onsubmit="return confirm('Удалить лицензию?')">
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($licenses)): ?>
        <tr><td colspan="7" style="text-align:center;color:#999">Нет лицензий</td></tr>
    <?php endif; ?>
    </tbody>
</table>
