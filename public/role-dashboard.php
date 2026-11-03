<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';
require_once dirname(__DIR__) . '/app/Modules/StudentManagement/Students/Services/student.php';

$user = requireDashboardUser(['Teacher', 'Student']);
$dashboardPath = match ($user['role_name']) {
    'Teacher' => 'teacher-dashboard.php',
    'Student' => studentSignInDestination(getDatabaseConnection(), (int) $user['user_id']),
};

header('Location: ' . $dashboardPath, true, 303);
exit;
