<?php /** @var \SnipeIT\Module\Models\Asset[] $assets */ ?>
<h1>Активы</h1>
<div class="toolbar">
    <form method="get" action="/assets" style="display:flex;gap:0.5rem;flex:1">
        <input type="search" name="search" placeholder="Поиск по названию, тегу, серийному номеру…"
               value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn btn-primary">Найти</button>
    </form>
    <a href="/assets/create" class="btn btn-success">+ Добавить актив</a>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Тег актива</th>
            <th>Название</th>
            <th>Серийный номер</th>
            <th>Назначен</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($assets as $asset): ?>
        <tr>
            <td><?= $asset->id ?></td>
            <td><?= htmlspecialchars($asset->asset_tag ?? '—') ?></td>
            <td><a href="/assets/<?= $asset->id ?>"><?= htmlspecialchars($asset->name ?? '—') ?></a></td>
            <td><?= htmlspecialchars($asset->serial ?? '—') ?></td>
            <td>
                <?php if ($asset->assigned_to): ?>
                    <span class="badge badge-warning">Назначен (#<?= $asset->assigned_to ?>)</span>
                <?php else: ?>
                    <span class="badge badge-success">Свободен</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="/assets/<?= $asset->id ?>/edit" class="btn btn-primary">Ред.</a>
                <form class="inline" method="post" action="/assets/<?= $asset->id ?>/delete"
                      onsubmit="return confirm('Удалить актив?')">
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($assets)): ?>
        <tr><td colspan="6" style="text-align:center;color:#999">Нет активов</td></tr>
    <?php endif; ?>
    </tbody>
</table>
