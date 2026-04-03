<?php /** @var \SnipeIT\Module\Models\License $license */ ?>
<h1>Новая лицензия</h1>

<div class="card">
    <form method="post" action="/licenses">
        <div class="form-group">
            <label>Название *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($license->name ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Серийный номер / ключ</label>
            <input type="text" name="serial" value="<?= htmlspecialchars($license->serial ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Количество мест *</label>
            <input type="number" name="seats" required min="1" value="<?= htmlspecialchars((string) ($license->seats ?? '1')) ?>">
        </div>
        <div class="form-group">
            <label>Стоимость покупки</label>
            <input type="number" name="purchase_cost" step="0.01" min="0"
                   value="<?= htmlspecialchars((string) ($license->purchase_cost ?? '')) ?>">
        </div>
        <div class="form-group">
            <label>Дата покупки</label>
            <input type="date" name="purchase_date" value="<?= htmlspecialchars($license->purchase_date ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Срок действия</label>
            <input type="date" name="expiration_date" value="<?= htmlspecialchars($license->expiration_date ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Примечания</label>
            <textarea name="notes" rows="3"><?= htmlspecialchars($license->notes ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:0.5rem">
            <button type="submit" class="btn btn-success">Сохранить</button>
            <a href="/licenses" class="btn" style="background:#95a5a6;color:#fff">Отмена</a>
        </div>
    </form>
</div>
