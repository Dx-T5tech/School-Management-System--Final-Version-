<?php

declare(strict_types=1);

if (
    isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__
) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('This shared template is not a public page. Open your role dashboard instead.');
}

$userName = trim($user['first_name'] . ' ' . $user['last_name']);
$greetingName = trim((string) $user['first_name']) !== '' ? $user['first_name'] : 'there';
$initials = strtoupper(
    substr((string) $user['first_name'], 0, 1)
    . substr((string) $user['last_name'], 0, 1)
);
$pageDate = date('Y-m-d');
$welcomeLabel = $dashboard['welcomeLabel'] ?? 'Welcome back';
$welcomeCaption = $dashboard['welcomeCaption'] ?? 'Your workspace is ready.';
$sectionContentTemplatePath = $dashboard['sectionContentTemplatePath']
    ?? __DIR__ . '/' . basename($dashboard['sectionContentTemplate'] ?? 'super_admin_section_content.php');
$publishedAnnouncements = $connection->query(
    "SELECT title, message, created_at
     FROM announcements
     WHERE status = 'Published'
     ORDER BY created_at DESC
     LIMIT 5"
)->fetchAll();
$configuredSchoolName = trim((string) $connection->query(
    'SELECT school_name
     FROM school_settings
     ORDER BY setting_id
     LIMIT 1'
)->fetchColumn());
$schoolName = $configuredSchoolName !== '' ? $configuredSchoolName : 'Fullbright College';
$configurationVersion = dashboardConfigurationVersion($connection);
$brandRoleIcon = match ($dashboard['navigationStyle'] ?? '') {
    'super-admin' => 'fa-user-shield',
    'admin' => 'fa-user-gear',
    'teacher' => 'fa-chalkboard-user',
    'student' => 'fa-user-graduate',
    default => null,
};
$brandRoleCaption = match ($dashboard['navigationStyle'] ?? '') {
    'super-admin' => 'Super Admin Dashboard',
    'admin' => 'Admin Dashboard',
    'teacher' => 'Teacher Dashboard',
    'student' => 'Student Dashboard',
    default => $dashboard['sidebarCaption'] ?? 'Integrated School Management',
};
$fontAwesomeCssPath = dirname(__DIR__, 2) . '/public/assets/vendor/fontawesome/css/all.min.css';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1B3654">
    <title><?= dashboardEscape($dashboard['pageTitle']) ?> - ISMS</title>
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css?v=<?= dashboardEscape((string) filemtime($fontAwesomeCssPath)) ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= dashboardEscape((string) filemtime(dirname(__DIR__, 2) . '/public/assets/css/dashboard.css')) ?>">
    <script src="assets/js/dashboard.js?v=<?= dashboardEscape((string) filemtime(dirname(__DIR__, 2) . '/public/assets/js/dashboard.js')) ?>" defer></script>
    <script src="assets/js/auth.js" defer></script>
</head>
<body class="dashboard-page dashboard-page--<?= dashboardEscape($dashboard['theme']) ?><?= !empty($dashboard['bodyClass']) ? ' ' . dashboardEscape($dashboard['bodyClass']) : '' ?>" data-configuration-version="<?= dashboardEscape($configurationVersion) ?>">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <p class="sr-only" role="status" aria-atomic="true" data-configuration-update-status></p>
    <div class="app-shell">
        <aside class="sidebar<?= in_array(($dashboard['navigationStyle'] ?? ''), ['super-admin', 'admin', 'teacher', 'student'], true) ? ' sidebar--' . dashboardEscape($dashboard['navigationStyle']) : '' ?>" id="dashboard-sidebar" aria-label="Dashboard sidebar">
            <a class="brand" href="<?= dashboardEscape($dashboard['homeHref'] ?? '#overview') ?>">
                <?php if ($brandRoleIcon !== null): ?>
                    <span class="brand-mark" aria-hidden="true"><i class="fa-solid <?= dashboardEscape($brandRoleIcon) ?>"></i></span>
                <?php else: ?>
                    <span class="brand-mark" aria-hidden="true">F</span>
                <?php endif; ?>
                <span class="brand-copy">
                    <span class="brand-name"><?= dashboardEscape($dashboard['sidebarTitle'] ?? 'Fullbright') ?></span>
                    <span class="brand-caption"><?= dashboardEscape($brandRoleCaption) ?></span>
                </span>
            </a>

            <p class="sidebar-section-label">Workspace</p>
            <nav class="sidebar-nav" aria-label="Dashboard navigation">
                <?php
                $navigationGroups = [];
                foreach ($dashboard['navItems'] as $index => $item) {
                    $groupLabel = $item['group'] ?? 'Workspace';
                    $navigationGroups[$groupLabel][] = ['index' => $index, 'item' => $item];
                }
                ?>
                <?php foreach ($navigationGroups as $groupLabel => $groupItems): ?>
                    <?php $groupId = 'sidebar-group-' . (int) $groupItems[0]['index']; ?>
                    <div class="sidebar-nav-group">
                        <h2 class="sidebar-nav-group-label"><?= dashboardEscape($groupLabel) ?></h2>
                        <ul class="sidebar-nav-group-list" id="<?= dashboardEscape($groupId) ?>">
                            <?php foreach ($groupItems as $groupItem): ?>
                                <?php
                                $index = $groupItem['index'];
                                $item = $groupItem['item'];
                                $hasCurrentSubnav = (
                                    ($item['key'] ?? '') === 'academic-management'
                                    && count(array_filter(
                                        $dashboard['academicSubnav'] ?? [],
                                        static fn (array $subitem): bool => !empty($subitem['current'])
                                    )) > 0
                                ) || (
                                    ($item['key'] ?? '') === 'attendance-scheduling'
                                    && count(array_filter(
                                        $dashboard['attendanceSubnav'] ?? [],
                                        static fn (array $subitem): bool => !empty($subitem['current'])
                                    )) > 0
                                ) || (
                                    ($item['key'] ?? '') === 'assessment-grading'
                                    && count(array_filter(
                                        $dashboard['assessmentSubnav'] ?? [],
                                        static fn (array $subitem): bool => !empty($subitem['current'])
                                    )) > 0
                                ) || (
                                    ($item['key'] ?? '') === 'finance-payments'
                                    && count(array_filter(
                                        $dashboard['financeSubnav'] ?? [],
                                        static fn (array $subitem): bool => !empty($subitem['current'])
                                    )) > 0
                                ) || (
                                    ($item['key'] ?? '') === 'teachers'
                                    && count(array_filter(
                                        $dashboard['teacherSubnav'] ?? [],
                                        static fn (array $subitem): bool => !empty($subitem['current'])
                                    )) > 0
                                );
                                ?>
                                <li>
                                    <a
                                        class="sidebar-link<?= !empty($item['current']) || (!isset($item['href']) && $index === 0) ? ' is-current' : '' ?>"
                                        href="<?= dashboardEscape($item['href'] ?? ('#' . $item['target'])) ?>"
                                        <?= !empty($item['badge']) ? 'aria-label="' . dashboardEscape($item['label'] . ', ' . $item['badge'] . ' unread notifications') . '"' : '' ?>
                                        <?= !empty($item['current']) && !$hasCurrentSubnav ? 'aria-current="page"' : ((!isset($item['href']) && $index === 0) ? 'aria-current="location"' : '') ?>
                                    >
                                        <?php if (in_array(($dashboard['navigationStyle'] ?? ''), ['super-admin', 'admin', 'teacher', 'student'], true)): ?>
                                            <svg class="sidebar-chevron" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                                <path d="m6 3 5 5-5 5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                                            </svg>
                                        <?php else: ?>
                                            <span class="sidebar-link-index" aria-hidden="true"><?= dashboardEscape(sprintf('%02d', $index + 1)) ?></span>
                                        <?php endif; ?>
                                        <span><?= dashboardEscape($item['label']) ?></span>
                                        <?php if (!empty($item['badge'])): ?>
                                            <span class="sidebar-badge" aria-hidden="true"><?= dashboardEscape((int) $item['badge']) ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <?php if (($item['key'] ?? '') === 'academic-management' && !empty($dashboard['academicSubnav'])): ?>
                                        <ul class="sidebar-subnav" aria-label="Academic Management sections">
                                            <?php foreach ($dashboard['academicSubnav'] as $subitem): ?>
                                                <li>
                                                    <a
                                                        class="sidebar-subnav-link<?= !empty($subitem['current']) ? ' is-current' : '' ?>"
                                                        href="<?= dashboardEscape($subitem['href']) ?>"
                                                        <?= !empty($subitem['current']) ? 'aria-current="page"' : '' ?>
                                                    ><?= dashboardEscape($subitem['label']) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php elseif (($item['key'] ?? '') === 'attendance-scheduling' && !empty($dashboard['attendanceSubnav'])): ?>
                                        <ul class="sidebar-subnav" aria-label="Attendance and scheduling sections">
                                            <?php foreach ($dashboard['attendanceSubnav'] as $subitem): ?>
                                                <li>
                                                    <a
                                                        class="sidebar-subnav-link<?= !empty($subitem['current']) ? ' is-current' : '' ?>"
                                                        href="<?= dashboardEscape($subitem['href']) ?>"
                                                        <?= !empty($subitem['current']) ? 'aria-current="page"' : '' ?>
                                                    ><?= dashboardEscape($subitem['label']) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php elseif (($item['key'] ?? '') === 'assessment-grading' && !empty($dashboard['assessmentSubnav'])): ?>
                                        <ul class="sidebar-subnav" aria-label="Assessment and grading sections">
                                            <?php foreach ($dashboard['assessmentSubnav'] as $subitem): ?>
                                                <li>
                                                    <a
                                                        class="sidebar-subnav-link<?= !empty($subitem['current']) ? ' is-current' : '' ?>"
                                                        href="<?= dashboardEscape($subitem['href']) ?>"
                                                        <?= !empty($subitem['current']) ? 'aria-current="page"' : '' ?>
                                                    ><?= dashboardEscape($subitem['label']) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php elseif (($item['key'] ?? '') === 'finance-payments' && !empty($dashboard['financeSubnav'])): ?>
                                        <ul class="sidebar-subnav" aria-label="Finance and payments sections">
                                            <?php foreach ($dashboard['financeSubnav'] as $subitem): ?>
                                                <li>
                                                    <a
                                                        class="sidebar-subnav-link<?= !empty($subitem['current']) ? ' is-current' : '' ?>"
                                                        href="<?= dashboardEscape($subitem['href']) ?>"
                                                        <?= !empty($subitem['current']) ? 'aria-current="page"' : '' ?>
                                                    ><?= dashboardEscape($subitem['label']) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php elseif (($item['key'] ?? '') === 'teachers' && !empty($dashboard['teacherSubnav'])): ?>
                                        <ul class="sidebar-subnav" aria-label="Teacher Management sections">
                                            <?php foreach ($dashboard['teacherSubnav'] as $subitem): ?>
                                                <li>
                                                    <a
                                                        class="sidebar-subnav-link<?= !empty($subitem['current']) ? ' is-current' : '' ?>"
                                                        href="<?= dashboardEscape($subitem['href']) ?>"
                                                        <?= !empty($subitem['current']) ? 'aria-current="page"' : '' ?>
                                                    ><?= dashboardEscape($subitem['label']) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <span class="sidebar-footer-label">Signed in as</span>
                <span class="sidebar-user-role"><?= dashboardEscape($dashboard['roleLabel']) ?></span>
                <span class="sidebar-user-name"><?= dashboardEscape($userName) ?></span>
            </div>
        </aside>

        <div class="workspace">
            <header class="topbar">
                <button
                    hidden
                    class="menu-toggle"
                    type="button"
                    aria-controls="dashboard-sidebar"
                    aria-expanded="false"
                    data-menu-toggle
                >
                    <span class="menu-toggle-icon" aria-hidden="true"></span>
                    <span>Menu</span>
                </button>
                <div class="topbar-context">
                    <span class="topbar-kicker"><?= dashboardEscape($schoolName) ?></span>
                    <span class="topbar-separator" aria-hidden="true">/</span>
                    <span><?= dashboardEscape($dashboard['roleLabel']) ?> workspace</span>
                </div>
                <div class="topbar-actions">
                    <time class="today-date" datetime="<?= dashboardEscape($pageDate) ?>" data-current-date>
                        <?= dashboardEscape(date('l, F j, Y')) ?>
                    </time>
                    <span class="user-avatar" aria-hidden="true"><?= dashboardEscape($initials) ?></span>
                    <form class="sign-out-form" action="logout.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= dashboardEscape(appCsrfToken()) ?>">
                        <button class="sign-out-button" type="submit">Sign out</button>
                    </form>
                </div>
            </header>

            <div class="admin-notice admin-notice--info configuration-update-notice" hidden data-configuration-update-notice>
                <p data-configuration-update-message>System settings changed. This page will refresh automatically.</p>
                <div class="configuration-update-actions">
                    <button class="admin-button" type="button" data-configuration-refresh>Refresh now</button>
                    <button class="admin-button admin-button--secondary" type="button" data-configuration-postpone>Keep working for 1 minute</button>
                </div>
            </div>

            <main class="main-content" id="main-content" tabindex="-1">
                <section class="page-intro" id="overview" aria-labelledby="page-title" tabindex="-1">
                    <div class="page-intro-copy">
                        <p class="eyebrow">
                            <span class="eyebrow-line" aria-hidden="true"></span>
                            <?= dashboardEscape($dashboard['eyebrow']) ?>
                        </p>
                        <h1 id="page-title"><?= dashboardEscape($dashboard['heading']) ?></h1>
                        <p class="page-description"><?= dashboardEscape($dashboard['description']) ?></p>
                    </div>
                    <div class="welcome-note">
                        <span class="welcome-label"><?= dashboardEscape($welcomeLabel) ?></span>
                        <span class="welcome-name"><?= dashboardEscape($greetingName) ?></span>
                        <span class="welcome-caption"><?= dashboardEscape($welcomeCaption) ?></span>
                    </div>
                </section>

                <?php if ($publishedAnnouncements !== [] && empty($dashboard['hideAnnouncementFeed'])): ?>
                    <div class="announcement-feed">
                        <div class="announcement-feed-heading">
                            <h2 id="announcements-heading">School announcements</h2>
                            <span><?= dashboardEscape(number_format(count($publishedAnnouncements))) ?> recent</span>
                        </div>
                        <ul>
                            <?php foreach ($publishedAnnouncements as $announcement): ?>
                                <li>
                                    <h3><?= dashboardEscape($announcement['title']) ?></h3>
                                    <p><?= nl2br(dashboardEscape($announcement['message'])) ?></p>
                                    <time datetime="<?= dashboardEscape(date('c', strtotime($announcement['created_at']))) ?>">
                                        <?= dashboardEscape($announcement['created_at']) ?>
                                    </time>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($dashboard['sectionContent'])): ?>
                    <?php require $sectionContentTemplatePath; ?>
                <?php else: ?>
                <?php if (!empty($dashboard['assessmentDashboard'])): ?>
                    <?php require __DIR__ . '/assessment_dashboard_content.php'; ?>
                <?php endif; ?>

                <section class="metrics-section" aria-labelledby="metrics-heading">
                    <div class="section-heading">
                        <div>
                            <p class="section-overline">At a glance</p>
                            <h2 id="metrics-heading"><?= dashboardEscape($dashboard['metricsHeading']) ?></h2>
                        </div>
                        <span class="data-note">
                            <span class="data-note-dot" aria-hidden="true"></span>
                            Current database figures
                        </span>
                    </div>
                    <div class="metrics-grid<?= !empty($dashboard['fiveMetricGrid']) ? ' metrics-grid--five' : '' ?>">
                        <?php foreach ($dashboard['metrics'] as $index => $metric): ?>
                            <article class="metric-card metric-card--<?= dashboardEscape($metric['tone']) ?>">
                                <span class="metric-index" aria-hidden="true">
                                    <?= dashboardEscape(sprintf('%02d', $index + 1)) ?>
                                </span>
                                <p class="metric-label"><?= dashboardEscape($metric['label']) ?></p>
                                <p class="metric-value"><?= dashboardEscape($metric['value']) ?></p>
                                <p class="metric-note"><?= dashboardEscape($metric['note']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if (!empty($dashboard['summary'])): ?>
                    <?php require __DIR__ . '/dashboard_summary_content.php'; ?>
                <?php endif; ?>

                <?php if (isset($dashboard['tuitionTermSummary'])): ?>
                    <section class="admin-panel tuition-term-panel" aria-labelledby="tuition-terms-heading">
                        <h2 id="tuition-terms-heading">Tuition by term</h2>
                        <p class="admin-panel-description">
                            <?= !empty($dashboard['hasActiveAcademicYear'])
                                ? 'Active-year enrolled students. Net tuition after discounts is split as evenly as possible across the three terms; completed payments are assigned to a term.'
                                : 'Activate an academic year to see enrolled-student tuition balances by term.' ?>
                        </p>
                        <?php if (!empty($dashboard['hasActiveAcademicYear'])): ?>
                            <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                            <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Tuition totals by term; scroll horizontally to view all columns">
                                <table class="admin-table">
                                    <caption>Tuition billed and collected by term</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Term</th>
                                            <th scope="col">Billed</th>
                                            <th scope="col">Collected</th>
                                            <th scope="col">Outstanding</th>
                                            <th scope="col">Paid students</th>
                                            <th scope="col">Unpaid students</th>
                                            <th scope="col">Partially paid</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dashboard['tuitionTermSummary'] as $termName => $termTotals): ?>
                                            <tr>
                                                <th scope="row"><?= dashboardEscape($termName) ?></th>
                                                <td><?= dashboardEscape(dashboardFormatCurrency($termTotals['billed_cents'] / 100)) ?></td>
                                                <td><?= dashboardEscape(dashboardFormatCurrency($termTotals['collected_cents'] / 100)) ?></td>
                                                <td><?= dashboardEscape(dashboardFormatCurrency($termTotals['outstanding_cents'] / 100)) ?></td>
                                                <td><?= dashboardEscape(number_format($termTotals['paid_students'])) ?></td>
                                                <td><?= dashboardEscape(number_format($termTotals['unpaid_students'])) ?></td>
                                                <td><?= dashboardEscape(number_format($termTotals['partial_students'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="admin-panel-description">Amounts are shown in Philippine pesos (PHP).</p>
                            <?php if ($dashboard['unassignedTuitionPayments']['count'] > 0): ?>
                                <p class="admin-notice admin-notice--info">
                                    <?= dashboardEscape(number_format($dashboard['unassignedTuitionPayments']['count'])) ?>
                                    completed payment(s), totaling
                                    <?= dashboardEscape(dashboardFormatCurrency($dashboard['unassignedTuitionPayments']['amount'])) ?>
                                    have no tuition fee and term assignment, so they are excluded from term collections.
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <div
                    class="content-grid"
                    id="responsibilities"
                    tabindex="-1"
                >
                    <section class="responsibilities-panel" aria-labelledby="responsibilities-heading">
                        <div class="section-heading section-heading--compact">
                            <div>
                                <p class="section-overline">Your remit</p>
                                <h2 id="responsibilities-heading"><?= dashboardEscape($dashboard['responsibilitiesHeading']) ?></h2>
                            </div>
                            <span class="remit-count" aria-hidden="true">
                                <?= dashboardEscape(sprintf('%02d', count($dashboard['responsibilities']))) ?>
                            </span>
                        </div>
                        <ol class="responsibility-list">
                            <?php foreach ($dashboard['responsibilities'] as $index => $responsibility): ?>
                                <li class="responsibility-item">
                                    <span class="responsibility-number" aria-hidden="true">
                                        <?= dashboardEscape(sprintf('%02d', $index + 1)) ?>
                                    </span>
                                    <div>
                                        <h3><?= dashboardEscape($responsibility['title']) ?></h3>
                                        <p><?= dashboardEscape($responsibility['description']) ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </section>

                    <aside class="review-panel" id="review-queue" aria-labelledby="review-heading" tabindex="-1">
                        <p class="section-overline"><?= dashboardEscape($dashboard['reviewOverline']) ?></p>
                        <h2 id="review-heading"><?= dashboardEscape($dashboard['reviewHeading']) ?></h2>
                        <p class="review-description"><?= dashboardEscape($dashboard['reviewDescription']) ?></p>
                        <ul class="review-list">
                            <?php foreach ($dashboard['reviewItems'] as $item): ?>
                                <li class="review-item">
                                    <span
                                        class="review-indicator review-indicator--<?= dashboardEscape($item['tone']) ?>"
                                        aria-hidden="true"
                                    ></span>
                                    <span class="review-item-text"><?= dashboardEscape($item['text']) ?></span>
                                    <span class="review-item-status"><?= dashboardEscape($item['status']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="review-footnote"><?= dashboardEscape($dashboard['reviewFootnote']) ?></p>
                    </aside>
                </div>
                <?php endif; ?>
            </main>

            <footer class="page-footer">
                <span>Integrated School Management System</span>
                <span><?= dashboardEscape($dashboard['roleLabel']) ?> workspace</span>
            </footer>
        </div>
    </div>
</body>
</html>
