<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';

requireDashboardUser(['Super Admin', 'Admin', 'Teacher', 'Student']);
$connection = getDatabaseConnection();
$version = dashboardConfigurationVersion($connection);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: application/json; charset=UTF-8');

echo json_encode(['version' => (string) $version], JSON_THROW_ON_ERROR);
