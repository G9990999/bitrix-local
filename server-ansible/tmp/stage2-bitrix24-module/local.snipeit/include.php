<?php

use Bitrix\Main\Loader;

/**
 * Module bootstrap — auto-registered by Bitrix loader.
 * Called once when the module is included via Loader::includeModule('local.snipeit').
 */
Loader::registerAutoLoadClasses('local.snipeit', [
    'Local\\SnipeIT\\Model\\AssetTable'          => 'lib/Model/AssetTable.php',
    'Local\\SnipeIT\\Model\\LicenseTable'        => 'lib/Model/LicenseTable.php',
    'Local\\SnipeIT\\Model\\AssignmentTable'     => 'lib/Model/UserAssignmentTable.php',
    'Local\\SnipeIT\\Service\\AssetService'      => 'lib/Service/AssetService.php',
    'Local\\SnipeIT\\Service\\CheckoutService'   => 'lib/Service/CheckoutService.php',
]);
