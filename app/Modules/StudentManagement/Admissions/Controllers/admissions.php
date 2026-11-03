<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/app/Core/dashboard.php';
require_once dirname(__DIR__, 2) . '/Students/Services/student.php';
require_once dirname(__DIR__) . '/Services/access.php';

$connection = getDatabaseConnection();
admissionsRedirectSignedInUser($connection);
$configuredSchoolName = trim((string) $connection->query(
    'SELECT school_name
     FROM school_settings
     ORDER BY setting_id
     LIMIT 1'
)->fetchColumn());
$schoolName = $configuredSchoolName !== '' ? $configuredSchoolName : 'Fullbright College';
$programs = studentAvailablePrograms($connection);

$admissionsViewPath = dirname(__DIR__) . '/Views/admissions.php';

require $admissionsViewPath;
