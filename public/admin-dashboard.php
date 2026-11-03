<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';
require_once dirname(__DIR__) . '/app/Core/admin.php';
require_once dirname(__DIR__) . '/app/Core/academic.php';
require_once dirname(__DIR__) . '/app/Core/finance.php';

$user = requireDashboardUser('Admin');
$connection = getDatabaseConnection();
$activeAcademicYearId = academicActiveYearId($connection);

$enrollmentCounts = $connection->prepare(
    "SELECT
        SUM(enrollment_status = 'Enrolled') AS enrolled_count,
        SUM(enrollment_status = 'Pending') AS pending_count
     FROM enrollments
     WHERE academic_year_id = :academic_year_id"
);
$enrollmentCounts->execute(['academic_year_id' => $activeAcademicYearId]);
$enrollmentTotals = $enrollmentCounts->fetch();
$enrolledStudents = (int) ($enrollmentTotals['enrolled_count'] ?? 0);
$pendingEnrollments = (int) ($enrollmentTotals['pending_count'] ?? 0);
$activeTeachers = (int) $connection->query(
    "SELECT COUNT(*) FROM teachers WHERE employment_status = 'Active'"
)->fetchColumn();
$attendanceToday = (int) $connection->query(
    'SELECT COUNT(*) FROM attendance WHERE attendance_date = CURRENT_DATE()'
)->fetchColumn();

$tuitionTermNames = ['Prelim', 'Midterm', 'Final'];
$tuitionTermSummary = [];
$studentTuitionTotals = [];
foreach ($tuitionTermNames as $termName) {
    $tuitionTermSummary[$termName] = [
        'billed_cents' => 0,
        'collected_cents' => 0,
        'outstanding_cents' => 0,
        'paid_students' => 0,
        'unpaid_students' => 0,
        'partial_students' => 0,
    ];
}

if ($activeAcademicYearId !== null) {
    $tuitionBalances = $connection->prepare(
        "SELECT
            e.student_id,
            ROUND(GREATEST(sf.amount - COALESCE(sf.discount_amount, 0), 0) * 100) AS net_tuition_cents,
            COALESCE(p.prelim_cents, 0) AS prelim_cents,
            COALESCE(p.midterm_cents, 0) AS midterm_cents,
            COALESCE(p.final_cents, 0) AS final_cents
         FROM enrollments e
         INNER JOIN student_fees sf ON sf.enrollment_id = e.enrollment_id
         INNER JOIN fee_structures fs ON fs.fee_structure_id = sf.fee_structure_id
         INNER JOIN fee_types ft ON ft.fee_type_id = fs.fee_type_id
         LEFT JOIN (
            SELECT
                student_fee_id,
                SUM(CASE WHEN payment_term = 'Prelim' THEN ROUND(total_amount * 100) ELSE 0 END) AS prelim_cents,
                SUM(CASE WHEN payment_term = 'Midterm' THEN ROUND(total_amount * 100) ELSE 0 END) AS midterm_cents,
                SUM(CASE WHEN payment_term = 'Final' THEN ROUND(total_amount * 100) ELSE 0 END) AS final_cents
            FROM payments
            WHERE payment_status = 'Completed'
              AND student_fee_id IS NOT NULL
            GROUP BY student_fee_id
         ) p ON p.student_fee_id = sf.student_fee_id
         WHERE e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
           AND ft.fee_category = 'Tuition'
           AND sf.fee_status <> 'Waived'
           AND sf.amount > COALESCE(sf.discount_amount, 0)"
    );
    $tuitionBalances->execute(['academic_year_id' => $activeAcademicYearId]);

    foreach ($tuitionBalances->fetchAll() as $balance) {
        $studentId = (int) $balance['student_id'];
        $installments = financeTuitionInstallmentsInCents((int) $balance['net_tuition_cents']);
        $collectedByTerm = [
            'Prelim' => (int) $balance['prelim_cents'],
            'Midterm' => (int) $balance['midterm_cents'],
            'Final' => (int) $balance['final_cents'],
        ];
        foreach ($tuitionTermNames as $termName) {
            $billedCents = $installments[$termName];
            $collectedCents = $collectedByTerm[$termName];
            $tuitionTermSummary[$termName]['billed_cents'] += $billedCents;
            $tuitionTermSummary[$termName]['collected_cents'] += $collectedCents;
            $tuitionTermSummary[$termName]['outstanding_cents'] += max(0, $billedCents - $collectedCents);

            if (!isset($studentTuitionTotals[$studentId][$termName])) {
                $studentTuitionTotals[$studentId][$termName] = ['billed' => 0, 'collected' => 0];
            }
            $studentTuitionTotals[$studentId][$termName]['billed'] += $billedCents;
            $studentTuitionTotals[$studentId][$termName]['collected'] += $collectedCents;
        }
    }

    foreach ($studentTuitionTotals as $termTotals) {
        foreach ($tuitionTermNames as $termName) {
            $billedCents = $termTotals[$termName]['billed'];
            $collectedCents = $termTotals[$termName]['collected'];
            if ($billedCents <= 0) {
                continue;
            }
            if ($collectedCents >= $billedCents) {
                $tuitionTermSummary[$termName]['paid_students']++;
            } else {
                $tuitionTermSummary[$termName]['unpaid_students']++;
                if ($collectedCents > 0) {
                    $tuitionTermSummary[$termName]['partial_students']++;
                }
            }
        }
    }
}

$unassignedTuitionPayments = ['count' => 0, 'amount' => 0.0];
if ($activeAcademicYearId !== null) {
    $unassignedPaymentsQuery = $connection->prepare(
        "SELECT COUNT(*) AS payment_count, COALESCE(SUM(p.total_amount), 0) AS total_amount
         FROM payments p
         INNER JOIN enrollments e ON e.enrollment_id = p.enrollment_id
         WHERE e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
           AND p.payment_status = 'Completed'
           AND (p.student_fee_id IS NULL OR p.payment_term IS NULL)"
    );
    $unassignedPaymentsQuery->execute(['academic_year_id' => $activeAcademicYearId]);
    $unassignedPayments = $unassignedPaymentsQuery->fetch();
    $unassignedTuitionPayments = [
        'count' => (int) $unassignedPayments['payment_count'],
        'amount' => (float) $unassignedPayments['total_amount'],
    ];
}

$reportMonthKeys = [];
$reportMonthLabels = [];
for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
    $month = (new DateTimeImmutable('first day of this month'))->modify('-' . $monthsAgo . ' months');
    $reportMonthKeys[] = $month->format('Y-m');
    $reportMonthLabels[] = $month->format('M');
}
$monthStart = (new DateTimeImmutable('first day of this month'))->modify('-5 months')->format('Y-m-01');
$enrollmentsByMonth = array_fill_keys($reportMonthKeys, 0);
$enrollmentTrendSql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS enrollment_count
                       FROM enrollments
                       WHERE created_at >= :month_start";
$enrollmentTrendParams = ['month_start' => $monthStart];
if ($activeAcademicYearId !== null) {
    $enrollmentTrendSql .= ' AND academic_year_id = :academic_year_id';
    $enrollmentTrendParams['academic_year_id'] = $activeAcademicYearId;
}
$enrollmentTrendSql .= ' GROUP BY month_key';
$enrollmentTrend = $connection->prepare($enrollmentTrendSql);
$enrollmentTrend->execute($enrollmentTrendParams);
foreach ($enrollmentTrend->fetchAll() as $monthRow) {
    if (array_key_exists($monthRow['month_key'], $enrollmentsByMonth)) {
        $enrollmentsByMonth[$monthRow['month_key']] = (int) $monthRow['enrollment_count'];
    }
}

$teacherAttendanceCounts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
$attendanceStatusCounts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
$attendanceStatusRows = $connection->query(
    'SELECT attendance_status, COUNT(*) AS attendance_count
     FROM attendance
     WHERE attendance_date = CURRENT_DATE()
     GROUP BY attendance_status'
)->fetchAll();
foreach ($attendanceStatusRows as $attendanceRow) {
    if (isset($attendanceStatusCounts[$attendanceRow['attendance_status']])) {
        $attendanceStatusCounts[$attendanceRow['attendance_status']] = (int) $attendanceRow['attendance_count'];
    }
}
$teacherAttendanceRows = $connection->query(
    'SELECT attendance_status, COUNT(*) AS attendance_count
     FROM teacher_attendance
     WHERE attendance_date = CURRENT_DATE()
     GROUP BY attendance_status'
)->fetchAll();
foreach ($teacherAttendanceRows as $attendanceRow) {
    if (isset($teacherAttendanceCounts[$attendanceRow['attendance_status']])) {
        $teacherAttendanceCounts[$attendanceRow['attendance_status']] = (int) $attendanceRow['attendance_count'];
    }
}

$roomCount = (int) $connection->query(
    "SELECT COUNT(*) FROM rooms WHERE status = 'Active' AND room_type <> 'Facility'"
)->fetchColumn();
$occupiedRoomQuery = $connection->prepare(
    "SELECT COUNT(DISTINCT cs.room_id)
     FROM class_schedules cs
     INNER JOIN teaching_assignments ta ON ta.assignment_id = cs.assignment_id
     INNER JOIN sections sec ON sec.section_id = ta.section_id
     INNER JOIN academic_years ay ON ay.academic_year_id = sec.academic_year_id
     INNER JOIN rooms r ON r.room_id = cs.room_id
     WHERE cs.schedule_status = 'Active'
       AND ta.assignment_status = 'Active'
       AND cs.day_of_week = :day_of_week
       AND cs.start_time <= :current_time_start
       AND cs.end_time > :current_time_end
       AND sec.status = 'Active'
       AND ay.status = 'Active'
       AND r.status = 'Active'
       AND r.room_type <> 'Facility'"
);
$occupiedRoomQuery->execute([
    'day_of_week' => date('l'),
    'current_time_start' => date('H:i:s'),
    'current_time_end' => date('H:i:s'),
]);
$occupiedRoomCount = (int) $occupiedRoomQuery->fetchColumn();
$availableRoomCount = max(0, $roomCount - $occupiedRoomCount);

$gradingProgressRows = [];
if ($activeAcademicYearId !== null) {
    $gradingProgress = $connection->prepare(
        "SELECT CONCAT(su.subject_code, ' · ', sec.section_name) AS class_label,
                COUNT(DISTINCT CONCAT(a.assessment_id, ':', e.student_id)) AS expected_scores,
                COUNT(DISTINCT CASE
                    WHEN ss.assessment_id IS NOT NULL THEN CONCAT(a.assessment_id, ':', ss.student_id)
                END) AS recorded_scores
         FROM teaching_assignments ta
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN enrollments e
           ON e.section_id = sec.section_id
          AND e.academic_year_id = sec.academic_year_id
          AND e.enrollment_status = 'Enrolled'
         INNER JOIN assessments a
           ON a.assignment_id = ta.assignment_id
          AND a.status IN ('Published', 'Completed')
         LEFT JOIN student_scores ss
           ON ss.assessment_id = a.assessment_id
          AND ss.student_id = e.student_id
         WHERE sec.academic_year_id = :academic_year_id
           AND ta.assignment_status = 'Active'
         GROUP BY ta.assignment_id, su.subject_code, sec.section_name
         ORDER BY su.subject_code, sec.section_name"
    );
    $gradingProgress->execute(['academic_year_id' => $activeAcademicYearId]);
    $gradingProgressRows = $gradingProgress->fetchAll();
}

$publishedAnnouncementCount = (int) $connection->query(
    "SELECT COUNT(*) FROM announcements WHERE status = 'Published'"
)->fetchColumn();
$totalCollectedCents = array_sum(array_column($tuitionTermSummary, 'collected_cents'));
$totalOutstandingCents = array_sum(array_column($tuitionTermSummary, 'outstanding_cents'));

$dashboard = [
    'theme' => 'admin',
    'roleLabel' => 'Admin',
    'pageTitle' => 'Admin dashboard',
    'eyebrow' => 'School operations',
    'heading' => 'Admin dashboard',
    'description' => 'Make today run smoothly. Coordinate enrollment, academic delivery, attendance, and the daily work that supports students and staff.',
    'metricsHeading' => 'Today’s operating picture',
    'navItems' => adminNavigationItems(),
    'navigationStyle' => 'admin',
    'sidebarTitle' => 'Admin Panel',
    'sidebarCaption' => 'School Operations',
    'homeHref' => 'admin-dashboard.php',
    'metrics' => [
        ['label' => 'Enrolled students', 'value' => number_format($enrolledStudents), 'note' => 'Active academic year', 'tone' => 'navy'],
        ['label' => 'Pending enrollments', 'value' => number_format($pendingEnrollments), 'note' => 'Active academic year', 'tone' => 'gold'],
        ['label' => 'Active teachers', 'value' => number_format($activeTeachers), 'note' => 'Available faculty records', 'tone' => 'blue'],
        ['label' => 'Attendance entries today', 'value' => number_format($attendanceToday), 'note' => 'Student attendance records', 'tone' => 'sage'],
    ],
    'summary' => [
        'heading' => 'School reports and daily activity',
        'periodLabel' => $activeAcademicYearId === null ? 'Current school activity' : 'Active academic year · updated on load',
        'cards' => [
            [
                'label' => 'Tuition collected',
                'value' => $activeAcademicYearId === null ? '—' : dashboardFormatCurrency($totalCollectedCents / 100),
                'note' => $activeAcademicYearId === null
                    ? 'Activate an academic year to view collections'
                    : 'Completed tuition payments',
                'href' => 'admin-section.php?section=finance-payments',
            ],
            [
                'label' => 'Student balance',
                'value' => $activeAcademicYearId === null ? '—' : dashboardFormatCurrency($totalOutstandingCents / 100),
                'note' => $activeAcademicYearId === null
                    ? 'Activate an academic year to view balances'
                    : 'Outstanding tuition across enrolled students',
                'href' => 'admin-section.php?section=finance-payments',
            ],
            [
                'label' => 'Rooms available now',
                'value' => number_format($availableRoomCount) . ' / ' . number_format($roomCount),
                'note' => number_format($occupiedRoomCount) . ' rooms occupied at ' . date('g:i A'),
                'href' => 'admin-section.php?section=attendance-scheduling&attendance_view=room-monitoring',
            ],
            [
                'label' => 'Teacher attendance today',
                'value' => number_format($teacherAttendanceCounts['Present']),
                'note' => number_format($teacherAttendanceCounts['Absent']) . ' absent · '
                    . number_format($teacherAttendanceCounts['Late']) . ' late',
                'href' => 'admin-section.php?section=teachers',
            ],
            [
                'label' => 'Published announcements',
                'value' => number_format($publishedAnnouncementCount),
                'note' => 'School updates are displayed above',
                'href' => 'admin-section.php?section=announcements',
            ],
        ],
        'charts' => [
            [
                'title' => 'Enrollment growth',
                'description' => 'New enrollment records created in the last six months.',
                'format' => 'integer',
                'labels' => $reportMonthLabels,
                'series' => [
                    ['label' => 'Enrollments', 'tone' => 'blue', 'values' => array_values($enrollmentsByMonth)],
                ],
                'emptyMessage' => 'No enrollment records are available for this period.',
            ],
            [
                'title' => 'Tuition by term',
                'description' => 'Billed, collected, and outstanding amounts for active-year enrolled students.',
                'format' => 'amount',
                'labels' => $activeAcademicYearId === null ? [] : $tuitionTermNames,
                'series' => [
                    ['label' => 'Billed', 'tone' => 'navy', 'values' => $activeAcademicYearId === null ? [] : array_map(static fn (array $term): float => $term['billed_cents'] / 100, $tuitionTermSummary)],
                    ['label' => 'Collected', 'tone' => 'sage', 'values' => $activeAcademicYearId === null ? [] : array_map(static fn (array $term): float => $term['collected_cents'] / 100, $tuitionTermSummary)],
                    ['label' => 'Outstanding', 'tone' => 'gold', 'values' => $activeAcademicYearId === null ? [] : array_map(static fn (array $term): float => $term['outstanding_cents'] / 100, $tuitionTermSummary)],
                ],
                'emptyMessage' => 'No active academic year tuition totals are available.',
            ],
            [
                'title' => 'Teacher activity today',
                'description' => 'Teacher attendance records by status.',
                'format' => 'integer',
                'labels' => ['Today'],
                'series' => [
                    ['label' => 'Present', 'tone' => 'sage', 'values' => [$teacherAttendanceCounts['Present']]],
                    ['label' => 'Absent', 'tone' => 'danger', 'values' => [$teacherAttendanceCounts['Absent']]],
                    ['label' => 'Late', 'tone' => 'gold', 'values' => [$teacherAttendanceCounts['Late']]],
                    ['label' => 'Excused', 'tone' => 'blue', 'values' => [$teacherAttendanceCounts['Excused']]],
                ],
                'emptyMessage' => 'No teacher attendance has been recorded today.',
            ],
            [
                'title' => 'Student attendance today',
                'description' => 'Attendance records grouped by status.',
                'format' => 'integer',
                'labels' => ['Today'],
                'series' => [
                    ['label' => 'Present', 'tone' => 'sage', 'values' => [$attendanceStatusCounts['Present']]],
                    ['label' => 'Absent', 'tone' => 'danger', 'values' => [$attendanceStatusCounts['Absent']]],
                    ['label' => 'Late', 'tone' => 'gold', 'values' => [$attendanceStatusCounts['Late']]],
                    ['label' => 'Excused', 'tone' => 'blue', 'values' => [$attendanceStatusCounts['Excused']]],
                ],
                'emptyMessage' => 'No student attendance has been recorded today.',
            ],
            [
                'title' => 'Grading progress by class',
                'description' => 'Recorded student scores compared with published assessment slots.',
                'format' => 'integer',
                'labels' => array_column($gradingProgressRows, 'class_label'),
                'series' => [
                    ['label' => 'Expected scores', 'tone' => 'navy', 'values' => array_map('intval', array_column($gradingProgressRows, 'expected_scores'))],
                    ['label' => 'Recorded scores', 'tone' => 'blue', 'values' => array_map('intval', array_column($gradingProgressRows, 'recorded_scores'))],
                ],
                'emptyMessage' => 'No published assessments are available in the active academic year.',
            ],
        ],
    ],
    'tuitionTermSummary' => $tuitionTermSummary,
    'unassignedTuitionPayments' => $unassignedTuitionPayments,
    'hasActiveAcademicYear' => $activeAcademicYearId !== null,
    'responsibilitiesHeading' => 'Day-to-day responsibilities',
    'responsibilities' => [
        ['title' => 'Student services', 'description' => 'Coordinate enrollment workflows and keep student records moving through the school.'],
        ['title' => 'Academic delivery', 'description' => 'Support class schedules, sections, and teaching assignments across programs.'],
        ['title' => 'Daily coordination', 'description' => 'Monitor attendance, staff readiness, and the needs of students and teachers.'],
        ['title' => 'Assessment support', 'description' => 'Keep grading periods, assessment work, and report-card preparation on track.'],
    ],
    'reviewOverline' => 'Daily check-in',
    'reviewHeading' => 'Operational pulse',
    'reviewDescription' => 'Use these live counts to spot areas needing attention today.',
    'reviewItems' => [
        [
            'text' => $pendingEnrollments > 0
                ? number_format($pendingEnrollments) . ' enrollment(s) are awaiting processing.'
                : 'No enrollments are waiting for processing.',
            'status' => $pendingEnrollments > 0 ? 'Action needed' : 'Clear',
            'tone' => $pendingEnrollments > 0 ? 'attention' : 'good',
        ],
        [
            'text' => $attendanceToday > 0
                ? 'Attendance has been recorded for today.'
                : 'No attendance has been recorded for today yet.',
            'status' => $attendanceToday > 0 ? 'Started' : 'Check',
            'tone' => $attendanceToday > 0 ? 'good' : 'attention',
        ],
        [
            'text' => $activeTeachers > 0
                ? 'Active teacher records are available.'
                : 'No active teacher records are available.',
            'status' => $activeTeachers > 0 ? 'In place' : 'Review',
            'tone' => $activeTeachers > 0 ? 'good' : 'attention',
        ],
    ],
    'reviewFootnote' => 'Counts update whenever you load the dashboard.',
];

$dashboardLayout = dirname(__DIR__) . '/app/Shared/dashboard_layout.php';
if (!is_file($dashboardLayout)) {
    header('Location: account.php', true, 303);
    exit;
}

require $dashboardLayout;
