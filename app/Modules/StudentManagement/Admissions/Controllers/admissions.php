<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/app/Core/dashboard.php';
require_once dirname(__DIR__, 2) . '/Students/Services/student.php';
require_once dirname(__DIR__) . '/Services/access.php';

$connection = getDatabaseConnection();
admissionsRedirectSignedInUser($connection);
$programs = studentAvailablePrograms($connection);
$programGroups = [];

foreach ($programs as $program) {
    $departmentName = is_string($program['department_name']) && $program['department_name'] !== ''
        ? $program['department_name']
        : 'Programs';
    $programGroups[$departmentName][] = $program;
}

$admissionsViewPath = dirname(__DIR__) . '/Views/admissions.php';

require $admissionsViewPath;
