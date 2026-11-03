<?php

declare(strict_types=1);

function adminNavigationItems(string $activeSection = 'dashboard'): array
{
    $items = [
        ['group' => 'Overview', 'key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'admin-dashboard.php'],
        ['group' => 'Student services', 'key' => 'students', 'label' => 'Student Management', 'href' => 'admin-section.php?section=students'],
        ['group' => 'Student services', 'key' => 'admissions', 'label' => 'Admissions Review', 'href' => 'admin-admissions.php'],
        ['group' => 'Staff', 'key' => 'teachers', 'label' => 'Teacher Management', 'href' => 'admin-section.php?section=teachers'],
        ['group' => 'Academic operations', 'key' => 'academic-management', 'label' => 'Academic Management', 'href' => 'admin-section.php?section=academic-management'],
        ['group' => 'Academic operations', 'key' => 'attendance-scheduling', 'label' => 'Attendance & Scheduling', 'href' => 'admin-section.php?section=attendance-scheduling'],
        ['group' => 'Academic operations', 'key' => 'assessment-grading', 'label' => 'Assessment & Grading', 'href' => 'admin-section.php?section=assessment-grading'],
        ['group' => 'Finance & reporting', 'key' => 'finance-payments', 'label' => 'Finance & Payments', 'href' => 'admin-section.php?section=finance-payments'],
        ['group' => 'Finance & reporting', 'key' => 'reports', 'label' => 'Reports', 'href' => 'admin-section.php?section=reports'],
        ['group' => 'School communication', 'key' => 'announcements', 'label' => 'Announcements', 'href' => 'admin-section.php?section=announcements'],
    ];

    foreach ($items as &$item) {
        $item['current'] = $item['key'] === $activeSection;
    }
    unset($item);

    return $items;
}

function adminTeacherSubnavItems(string $currentView): array
{
    $items = [
        ['key' => 'directory', 'label' => 'Faculty directory', 'href' => 'admin-section.php?section=teachers'],
        ['key' => 'hiring', 'label' => 'Hiring pipeline', 'href' => 'admin-teacher-lifecycle.php?view=hiring'],
        ['key' => 'performance', 'label' => 'Performance reviews', 'href' => 'admin-teacher-lifecycle.php?view=performance'],
        ['key' => 'leave', 'label' => 'Leave requests', 'href' => 'admin-teacher-lifecycle.php?view=leave'],
        ['key' => 'resignations', 'label' => 'Resignations', 'href' => 'admin-teacher-lifecycle.php?view=resignations'],
    ];

    foreach ($items as &$item) {
        $item['current'] = $item['key'] === $currentView;
    }
    unset($item);

    return $items;
}

function adminPostString(string $key): string
{
    $value = $_POST[$key] ?? null;

    return is_string($value) ? trim($value) : '';
}

function adminPostInt(string $key): ?int
{
    $value = filter_var(
        $_POST[$key] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    return $value === false ? null : $value;
}

function adminRecordAudit(PDO $connection, int $userId, string $action, string $module, string $description): void
{
    $statement = $connection->prepare(
        'INSERT INTO audit_logs (user_id, action, module_name, description, ip_address)
         VALUES (:user_id, :action, :module_name, :description, :ip_address)'
    );
    $statement->execute([
        'user_id' => $userId,
        'action' => $action,
        'module_name' => $module,
        'description' => $description,
        'ip_address' => loginIpAddress(),
    ]);
}
