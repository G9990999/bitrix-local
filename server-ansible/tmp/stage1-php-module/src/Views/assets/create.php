<?php /** @var \SnipeIT\Module\Models\Asset $asset */ ?>
<h1>Новый актив</h1>

<div class="card">
    <form method="post" action="/assets">
        <div class="form-group">
            <label>Название</label>
            <input type="text" name="name" value="<?= htmlspecialchars($asset->name ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Тег актива *</label>
            <input type="text" name="asset_tag" required value="<?= htmlspecialchars($asset->asset_tag ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Серийный номер</label>
            <input type="text" name="serial" value="<?= htmlspecialchars($asset->serial ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Дата покупки</label>
            <input type="date" name="purchase_date" value="<?= htmlspecialchars($asset->purchase_date ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Стоимость покупки</label>
            <input type="number" name="purchase_cost" step="0.01" min="0"
                   value="<?= htmlspecialchars((string) ($asset->purchase_cost ?? '')) ?>">
        </div>
        <div class="form-group">
            <label>Номер заказа</label>
            <input type="text" name="order_number" value="<?= htmlspecialchars($asset->order_number ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Гарантия (месяцев)</label>
            <input type="number" name="warranty_months" min="0"
                   value="<?= htmlspecialchars($asset->warranty_months ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Примечания</label>
            <textarea name="notes" rows="3"><?= htmlspecialchars($asset->notes ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:0.5rem">
            <button type="submit" class="btn btn-success">Сохранить</button>
            <a href="/assets" class="btn" style="background:#95a5a6;color:#fff">Отмена</a>
        </div>
    </form>
</div>
