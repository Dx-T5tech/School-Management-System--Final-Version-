<?php

declare(strict_types=1);

function superAdminNavigationItems(string $activeSection = 'dashboard'): array
{
    $items = [
        ['group' => 'Overview', 'key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'super-admin-dashboard.php'],
        ['group' => 'Accounts & access', 'key' => 'admins', 'label' => 'Admin Management', 'href' => 'super-admin-section.php?section=admins'],
        ['group' => 'Accounts & access', 'key' => 'users', 'label' => 'User Management', 'href' => 'super-admin-section.php?section=users'],
        ['group' => 'Accounts & access', 'key' => 'roles-permissions', 'label' => 'Roles & Permissions', 'href' => 'super-admin-section.php?section=roles-permissions'],
        ['group' => 'School setup', 'key' => 'school-configuration', 'label' => 'School Configuration', 'href' => 'super-admin-section.php?section=school-configuration'],
        ['group' => 'School setup', 'key' => 'academic-management', 'label' => 'Academic Management', 'href' => 'super-admin-section.php?section=academic-management'],
        ['group' => 'School setup', 'key' => 'admissions', 'label' => 'Admissions Review', 'href' => 'admin-admissions.php'],
        ['group' => 'System oversight', 'key' => 'system-monitoring', 'label' => 'System Monitoring', 'href' => 'super-admin-section.php?section=system-monitoring'],
        ['group' => 'System oversight', 'key' => 'audit-logs', 'label' => 'Audit Logs', 'href' => 'super-admin-section.php?section=audit-logs'],
        ['group' => 'System oversight', 'key' => 'database-backup', 'label' => 'Database Backup', 'href' => 'super-admin-section.php?section=database-backup'],
        ['group' => 'System oversight', 'key' => 'reports', 'label' => 'Reports', 'href' => 'super-admin-section.php?section=reports'],
    ];

    foreach ($items as &$item) {
        $item['current'] = $item['key'] === $activeSection;
    }
    unset($item);

    return $items;
}

function superAdminPostString(string $key): string
{
    $value = $_POST[$key] ?? null;

    return is_string($value) ? trim($value) : '';
}

function superAdminRecordAudit(
    PDO $connection,
    int $userId,
    string $action,
    string $module,
    string $description
): void {
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
