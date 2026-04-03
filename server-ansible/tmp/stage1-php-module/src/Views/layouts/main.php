<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snipe-IT Asset Management</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }
        nav { background: #2c3e50; color: #fff; padding: 0 1rem; display: flex; align-items: center; gap: 1.5rem; height: 50px; }
        nav a { color: #ecf0f1; text-decoration: none; font-size: 0.9rem; padding: 0.25rem 0.5rem; border-radius: 4px; }
        nav a:hover { background: rgba(255,255,255,0.1); }
        nav .brand { font-weight: bold; font-size: 1.1rem; }
        main { max-width: 1200px; margin: 1.5rem auto; padding: 0 1rem; }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th { background: #34495e; color: #fff; padding: 0.75rem 1rem; text-align: left; font-size: 0.85rem; }
        td { padding: 0.6rem 1rem; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f9f9f9; }
        .btn { display: inline-block; padding: 0.4rem 0.9rem; border-radius: 4px; font-size: 0.85rem; cursor: pointer; text-decoration: none; border: none; }
        .btn-primary { background: #3498db; color: #fff; }
        .btn-danger  { background: #e74c3c; color: #fff; }
        .btn-success { background: #27ae60; color: #fff; }
        .btn:hover { opacity: 0.85; }
        form.inline { display: inline; }
        .card { background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; color: #555; }
        input[type=text], input[type=number], input[type=date], textarea, select {
            width: 100%; padding: 0.5rem 0.7rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;
        }
        .badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger  { background: #f8d7da; color: #721c24; }
        .toolbar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .toolbar input[type=search] { flex: 1; padding: 0.5rem 0.7rem; border: 1px solid #ddd; border-radius: 4px; }
        .alert { padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
<nav>
    <span class="brand">Snipe-IT</span>
    <a href="/assets">Активы</a>
    <a href="/licenses">Лицензии</a>
    <a href="/users">Пользователи</a>
</nav>
<main>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php
// Include the actual template content
$templateFile = dirname(__DIR__) . '/' . $template . '.php';
if (file_exists($templateFile)) {
    require $templateFile;
}
?>
</main>
</body>
</html>
