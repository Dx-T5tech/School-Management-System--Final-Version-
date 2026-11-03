<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';
require_once dirname(__DIR__) . '/app/Core/super_admin.php';

$user = requireDashboardUser('Super Admin');
$connection = getDatabaseConnection();

$administratorCount = (int) $connection->query(
    "SELECT COUNT(*)
     FROM users u
     INNER JOIN roles r ON r.role_id = u.role_id
     WHERE r.role_name = 'Admin'"
)->fetchColumn();
$studentCount = (int) $connection->query('SELECT COUNT(*) FROM students')->fetchColumn();
$teacherCount = (int) $connection->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
$enrollmentCount = (int) $connection->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
$completedCollections = (float) $connection->query(
    "SELECT COALESCE(SUM(total_amount), 0)
     FROM payments
     WHERE payment_status = 'Completed'"
)->fetchColumn();
$activeAcademicYears = (int) $connection->query(
    "SELECT COUNT(*) FROM academic_years WHERE status = 'Active'"
)->fetchColumn();
$recentAuditEvents = (int) $connection->query(
    'SELECT COUNT(*)
     FROM audit_logs
     WHERE created_at >= CURRENT_TIMESTAMP - INTERVAL 30 DAY'
)->fetchColumn();
$schoolSettingsCount = (int) $connection->query('SELECT COUNT(*) FROM school_settings')->fetchColumn();
$accountStatusCounts = ['Active' => 0, 'Inactive' => 0];
$activeUserCount = 0;
$inactiveUserCount = 0;
$accountsByRole = [];
foreach ($connection->query(
    'SELECT r.role_name, u.account_status, COUNT(*) AS account_count
     FROM users u
     INNER JOIN roles r ON r.role_id = u.role_id
     GROUP BY r.role_id, r.role_name, u.account_status
     ORDER BY r.role_name'
)->fetchAll() as $accountRow) {
    $roleName = (string) $accountRow['role_name'];
    $status = (string) $accountRow['account_status'];
    $accountsByRole[$roleName] ??= ['Active' => 0, 'Inactive' => 0];
    if (isset($accountsByRole[$roleName][$status])) {
        $accountsByRole[$roleName][$status] = (int) $accountRow['account_count'];
    }
    if (isset($accountStatusCounts[$status])) {
        $accountStatusCounts[$status] += (int) $accountRow['account_count'];
    }
}
$activeUserCount = $accountStatusCounts['Active'];
$inactiveUserCount = $accountStatusCounts['Inactive'];

$enrollmentStatusCounts = [];
foreach ($connection->query(
    'SELECT enrollment_status, COUNT(*) AS enrollment_count
     FROM enrollments
     GROUP BY enrollment_status
     ORDER BY enrollment_status'
)->fetchAll() as $statusRow) {
    $enrollmentStatusCounts[(string) $statusRow['enrollment_status']] = (int) $statusRow['enrollment_count'];
}

$reportMonthKeys = [];
$reportMonthLabels = [];
for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
    $month = (new DateTimeImmutable('first day of this month'))->modify('-' . $monthsAgo . ' months');
    $reportMonthKeys[] = $month->format('Y-m');
    $reportMonthLabels[] = $month->format('M');
}
$monthStart = (new DateTimeImmutable('first day of this month'))->modify('-5 months')->format('Y-m-01');
$accountsByMonth = array_fill_keys($reportMonthKeys, 0);
$accountTrend = $connection->prepare(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS account_count
     FROM users
     WHERE created_at >= :month_start
     GROUP BY month_key"
);
$accountTrend->execute(['month_start' => $monthStart]);
foreach ($accountTrend->fetchAll() as $monthRow) {
    if (array_key_exists($monthRow['month_key'], $accountsByMonth)) {
        $accountsByMonth[$monthRow['month_key']] = (int) $monthRow['account_count'];
    }
}
$collectionsByMonth = array_fill_keys($reportMonthKeys, 0.0);
$collectionTrend = $connection->prepare(
    "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month_key, COALESCE(SUM(total_amount), 0) AS collection_total
     FROM payments
     WHERE payment_status = 'Completed'
       AND payment_date >= :month_start
     GROUP BY month_key"
);
$collectionTrend->execute(['month_start' => $monthStart]);
foreach ($collectionTrend->fetchAll() as $monthRow) {
    if (array_key_exists($monthRow['month_key'], $collectionsByMonth)) {
        $collectionsByMonth[$monthRow['month_key']] = (float) $monthRow['collection_total'];
    }
}
$databaseSizeBytes = (int) $connection->query(
    'SELECT COALESCE(SUM(data_length + index_length), 0)
     FROM information_schema.TABLES
     WHERE table_schema = DATABASE()'
)->fetchColumn();
$lastAuditAt = $connection->query(
    'SELECT created_at FROM audit_logs ORDER BY created_at DESC LIMIT 1'
)->fetchColumn();
$enrollmentStatusSummary = [];
foreach ($enrollmentStatusCounts as $status => $count) {
    $enrollmentStatusSummary[] = $status . ': ' . number_format($count);
}

$dashboard = [
    'theme' => 'executive',
    'roleLabel' => 'Super Admin',
    'pageTitle' => 'Super Admin dashboard',
    'eyebrow' => 'Executive control room',
    'heading' => 'Super Admin overview',
    'description' => 'Govern access, maintain institution-wide settings, and keep a clear view of school-wide activity.',
    'metricsHeading' => 'Institution-wide overview',
    'fiveMetricGrid' => true,
    'navigationStyle' => 'super-admin',
    'sidebarTitle' => 'SUPER ADMIN PANEL',
    'sidebarCaption' => 'SYSTEM CONTROL',
    'homeHref' => 'super-admin-dashboard.php',
    'navItems' => superAdminNavigationItems(),
    'metrics' => [
        ['label' => 'Total administrators', 'value' => number_format($administratorCount), 'note' => 'Admin-role accounts', 'tone' => 'navy'],
        ['label' => 'Total teachers', 'value' => number_format($teacherCount), 'note' => 'Teacher records', 'tone' => 'blue'],
        ['label' => 'Total students', 'value' => number_format($studentCount), 'note' => 'Student records', 'tone' => 'gold'],
        ['label' => 'Total enrollments', 'value' => number_format($enrollmentCount), 'note' => 'All enrollment statuses', 'tone' => 'sage'],
        ['label' => 'School-wide collections', 'value' => dashboardFormatCurrency($completedCollections), 'note' => 'Completed payments · PHP', 'tone' => 'blue'],
    ],
    'summary' => [
        'heading' => 'Institution reports',
        'periodLabel' => 'Updated when this page loads',
        'cards' => [
            [
                'label' => 'Active user accounts',
                'value' => number_format($activeUserCount),
                'note' => number_format($inactiveUserCount) . ' inactive · account status across all roles',
                'href' => 'super-admin-section.php?section=users',
            ],
            [
                'label' => 'Enrollment status',
                'value' => number_format($enrollmentCount),
                'note' => $enrollmentStatusSummary !== [] ? implode(' · ', $enrollmentStatusSummary) : 'No enrollment records',
                'href' => 'super-admin-section.php?section=reports',
            ],
            [
                'label' => 'Database footprint',
                'value' => number_format($databaseSizeBytes / 1024 / 1024, 1) . ' MB',
                'note' => 'Current table data and index size; backup status is not tracked here',
                'href' => 'super-admin-section.php?section=database-backup',
            ],
            [
                'label' => 'Latest audit activity',
                'value' => $lastAuditAt === false ? 'None' : date('M j, Y', strtotime((string) $lastAuditAt)),
                'note' => number_format($recentAuditEvents) . ' events recorded in the last 30 days',
                'href' => 'super-admin-section.php?section=audit-logs',
            ],
        ],
        'charts' => [
            [
                'title' => 'Accounts by role and status',
                'description' => 'Active and inactive accounts for each system role.',
                'format' => 'integer',
                'labels' => array_keys($accountsByRole),
                'series' => [
                    ['label' => 'Active', 'tone' => 'blue', 'values' => array_column($accountsByRole, 'Active')],
                    ['label' => 'Inactive', 'tone' => 'gold', 'values' => array_column($accountsByRole, 'Inactive')],
                ],
                'emptyMessage' => 'No user accounts have been created yet.',
            ],
            [
                'title' => 'Account growth',
                'description' => 'New user accounts created in the last six months.',
                'format' => 'integer',
                'labels' => $reportMonthLabels,
                'series' => [
                    ['label' => 'Accounts', 'tone' => 'sage', 'values' => array_values($accountsByMonth)],
                ],
                'emptyMessage' => 'No new accounts are available for this period.',
            ],
            [
                'title' => 'Enrollment status',
                'description' => 'All enrollment records grouped by their current status.',
                'format' => 'integer',
                'labels' => array_keys($enrollmentStatusCounts),
                'series' => [
                    ['label' => 'Enrollments', 'tone' => 'gold', 'values' => array_values($enrollmentStatusCounts)],
                ],
                'emptyMessage' => 'No enrollment records have been created yet.',
            ],
            [
                'title' => 'Completed payment report',
                'description' => 'Payment collections by month, shown in Philippine pesos (PHP).',
                'format' => 'amount',
                'labels' => $reportMonthLabels,
                'series' => [
                    ['label' => 'Collected', 'tone' => 'blue', 'values' => array_values($collectionsByMonth)],
                ],
                'emptyMessage' => 'No completed payments are available for this period.',
            ],
        ],
    ],
    'responsibilitiesHeading' => 'System-wide responsibilities',
    'responsibilities' => [
        ['title' => 'Administrator accounts', 'description' => 'Create, manage, and deactivate Admin accounts.'],
        ['title' => 'Roles and permissions', 'description' => 'Assign roles and permissions, and maintain system-wide access controls.'],
        ['title' => 'School configuration', 'description' => 'Configure school information and system settings.'],
        ['title' => 'System modules', 'description' => 'Manage all system modules and their school-wide configuration.'],
        ['title' => 'School records and reports', 'description' => 'Access school records and reports across the institution.'],
        ['title' => 'Activity and audit logs', 'description' => 'Monitor system activities and review audit logs.'],
        ['title' => 'Backup and maintenance', 'description' => 'Manage database backups and routine system maintenance.'],
        ['title' => 'Academic years and policies', 'description' => 'Configure academic years and school-wide policies.'],
    ],
    'reviewOverline' => 'System oversight',
    'reviewHeading' => 'Activity and configuration',
    'reviewDescription' => 'A current view of recent audit activity and essential school setup.',
    'reviewItems' => [
        [
            'text' => $activeAcademicYears > 0 ? 'An academic year is active.' : 'No academic year is marked active.',
            'status' => $activeAcademicYears > 0 ? 'In place' : 'Review',
            'tone' => $activeAcademicYears > 0 ? 'good' : 'attention',
        ],
        [
            'text' => $schoolSettingsCount > 0 ? 'School information is configured.' : 'School information has not been configured.',
            'status' => $schoolSettingsCount > 0 ? 'In place' : 'Review',
            'tone' => $schoolSettingsCount > 0 ? 'good' : 'attention',
        ],
        [
            'text' => $recentAuditEvents > 0
                ? number_format($recentAuditEvents) . ' audit event(s) recorded in the last 30 days.'
                : 'No audit events have been recorded in the last 30 days.',
            'status' => $recentAuditEvents > 0 ? 'Activity' : 'Quiet',
            'tone' => $recentAuditEvents > 0 ? 'good' : 'attention',
        ],
    ],
    'reviewFootnote' => 'Collections include completed payments only; amounts are shown in Philippine pesos (PHP).',
];

$dashboardLayout = dirname(__DIR__) . '/app/Shared/dashboard_layout.php';
if (!is_file($dashboardLayout)) {
    header('Location: account.php', true, 303);
    exit;
}

require $dashboardLayout;
