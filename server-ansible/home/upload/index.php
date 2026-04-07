<?php
// Если это запрос к API — пусть им занимается router.php
// Если зашли в корень — просто инициализируем ядро
define('BITRIX_INSTALL_DONE', true);
define('BITRIX_SKIP_INSTALL_CHECK', true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

echo "RoleModel Headless Engine: READY";
