<?php

declare(strict_types=1);

function teacherNavigationItems(string $activeSection = 'dashboard'): array
{
    $items = [
        ['group' => 'Overview', 'key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'teacher-dashboard.php'],
        ['group' => 'Classes & students', 'key' => 'classes', 'label' => 'My Classes', 'href' => 'teacher-section.php?section=classes'],
        ['group' => 'Classes & students', 'key' => 'students', 'label' => 'My Students', 'href' => 'teacher-section.php?section=students'],
        ['group' => 'Classroom', 'key' => 'schedule', 'label' => 'Class Schedule', 'href' => 'teacher-section.php?section=schedule'],
        ['group' => 'Classroom', 'key' => 'attendance', 'label' => 'Attendance', 'href' => 'teacher-section.php?section=attendance'],
        ['group' => 'Learning & assessment', 'key' => 'assessments', 'label' => 'Assessments', 'href' => 'teacher-section.php?section=assessments'],
        ['group' => 'Learning & assessment', 'key' => 'grades', 'label' => 'Grade Management', 'href' => 'teacher-section.php?section=grades'],
        ['group' => 'Learning & assessment', 'key' => 'report-cards', 'label' => 'Report Cards', 'href' => 'teacher-section.php?section=report-cards'],
        ['group' => 'Learning & assessment', 'key' => 'performance', 'label' => 'Student Performance', 'href' => 'teacher-section.php?section=performance'],
        ['group' => 'Staff services', 'key' => 'leave', 'label' => 'My Leave Requests', 'href' => 'teacher-leave.php'],
    ];

    foreach ($items as &$item) {
        $item['current'] = $item['key'] === $activeSection;
    }
    unset($item);

    return $items;
}

function teacherPostString(string $key): string
{
    $value = $_POST[$key] ?? null;

    return is_string($value) ? trim($value) : '';
}

function teacherPostInt(string $key): ?int
{
    $value = filter_var(
        $_POST[$key] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    return $value === false ? null : $value;
}

function teacherRecordAudit(PDO $connection, int $userId, string $action, string $module, string $description): void
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
