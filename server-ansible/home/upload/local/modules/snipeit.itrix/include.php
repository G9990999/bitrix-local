<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('snipeit.itrix', [
    'Snipeit\\Itrix\\Model\\AssetTable'          => 'lib/Model/AssetTable.php',
    'Snipeit\\Itrix\\Model\\LicenseTable'         => 'lib/Model/LicenseTable.php',
    'Snipeit\\Itrix\\Model\\UserAssignmentTable'  => 'lib/Model/UserAssignmentTable.php',
    'Snipeit\\Itrix\\Service\\AssetService'       => 'lib/Service/AssetService.php',
    'Snipeit\\Itrix\\Service\\LicenseService'     => 'lib/Service/LicenseService.php',
    'Snipeit\\Itrix\\Service\\AssignmentService'  => 'lib/Service/AssignmentService.php',
]);
