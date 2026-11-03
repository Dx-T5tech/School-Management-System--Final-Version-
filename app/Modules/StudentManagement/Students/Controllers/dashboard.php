<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/app/Core/dashboard.php';
require_once dirname(__DIR__, 5) . '/app/Core/finance.php';
require_once dirname(__DIR__, 2) . '/Students/Services/student.php';

$user = requireDashboardUser('Student');
$connection = getDatabaseConnection();
$studentId = studentFindProfileId($connection, (int) $user['user_id']);
$activeAcademicYear = academicActiveYear($connection);
$activeAcademicYearId = $activeAcademicYear === null
    ? null
    : (int) $activeAcademicYear['academic_year_id'];
if ($activeAcademicYearId === null || !studentHasActiveEnrollment($connection, (int) $user['user_id'])) {
    header('Location: student-enrollment.php', true, 303);
    exit;
}
if ($studentId !== null) {
    financeSyncStudentFeeNotifications(
        $connection,
        (int) $user['user_id'],
        $studentId,
        $activeAcademicYearId
    );
}
$unreadNotifications = financeStudentUnreadNotificationCount($connection, (int) $user['user_id']);

$enrollmentCount = 0;
$attendanceCount = 0;
$presentCount = 0;
$studentNumber = '';
$studentClassRows = [];
$studentAssessmentRows = [];
$studentNotificationRows = [];
$studentAttendanceBySubject = [];
$studentGradesBySubject = [];
$studentGradeChartRows = [];
$studentScheduleRows = [];
$studentPaymentRows = [];
$tuitionBalanceCents = 0;
$tuitionPaidCents = 0;
$paymentTrendMonths = [];

if ($studentId !== null) {
    $profileStatement = $connection->prepare(
        'SELECT student_number FROM students WHERE student_id = :student_id LIMIT 1'
    );
    $profileStatement->execute(['student_id' => $studentId]);
    $studentNumberValue = $profileStatement->fetchColumn();
    $studentNumber = $studentNumberValue === false ? '' : (string) $studentNumberValue;

    $statement = $connection->prepare(
        'SELECT COUNT(*)
         FROM enrollments
         WHERE student_id = :student_id
           AND academic_year_id = :academic_year_id
           AND enrollment_status = :enrollment_status'
    );
    $statement->execute([
        'student_id' => $studentId,
        'academic_year_id' => $activeAcademicYearId,
        'enrollment_status' => 'Enrolled',
    ]);
    $enrollmentCount = (int) $statement->fetchColumn();

    $statement = $connection->prepare(
        'SELECT COUNT(*)
         FROM attendance
         WHERE student_id = :student_id
           AND attendance_date = CURRENT_DATE()'
    );
    $statement->execute(['student_id' => $studentId]);
    $attendanceCount = (int) $statement->fetchColumn();

    $statement = $connection->prepare(
        'SELECT COUNT(*)
         FROM attendance
         WHERE student_id = :student_id
           AND attendance_status = :attendance_status'
    );
    $statement->execute([
        'student_id' => $studentId,
        'attendance_status' => 'Present',
    ]);
    $presentCount = (int) $statement->fetchColumn();

    $classStatement = $connection->prepare(
        "SELECT DISTINCT su.subject_code, su.subject_name, sec.section_name,
                sem.semester_name,
                CONCAT_WS(' ', teacher_user.first_name, teacher_user.last_name) AS teacher_name
         FROM enrollments e
         INNER JOIN sections sec ON sec.section_id = e.section_id
         INNER JOIN teaching_assignments ta ON ta.section_id = sec.section_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN semesters sem
           ON sem.semester_id = ta.semester_id
          AND sem.status = 'Active'
         LEFT JOIN teachers t ON t.teacher_id = ta.teacher_id
         LEFT JOIN users teacher_user ON teacher_user.user_id = t.user_id
         WHERE e.student_id = :student_id
           AND e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
         ORDER BY su.subject_name, sec.section_name"
    );
    $classStatement->execute([
        'student_id' => $studentId,
        'academic_year_id' => $activeAcademicYearId,
    ]);
    $studentClassRows = $classStatement->fetchAll();

    $assessmentStatement = $connection->prepare(
        "SELECT a.assessment_title, a.assessment_type, a.total_points,
                a.due_date, a.assessment_date, su.subject_code, gp.period_name,
                ss.score, ss.remarks, ss.recorded_at
         FROM enrollments e
         INNER JOIN teaching_assignments ta ON ta.section_id = e.section_id
         INNER JOIN assessments a ON a.assignment_id = ta.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         LEFT JOIN grading_periods gp ON gp.grading_period_id = a.grading_period_id
         LEFT JOIN student_scores ss
           ON ss.assessment_id = a.assessment_id
          AND ss.student_id = e.student_id
         WHERE e.student_id = :student_id
           AND e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
           AND a.status IN ('Published', 'Completed')
         ORDER BY COALESCE(ss.recorded_at, a.due_date, a.assessment_date, a.created_at) DESC,
                  a.assessment_id DESC
         LIMIT 6"
    );
    $assessmentStatement->execute([
        'student_id' => $studentId,
        'academic_year_id' => $activeAcademicYearId,
    ]);
    $studentAssessmentRows = $assessmentStatement->fetchAll();

    $notificationStatement = $connection->prepare(
        'SELECT notification_type, title, message, is_read, created_at
         FROM student_notifications
         WHERE user_id = :user_id
           AND is_active = 1
         ORDER BY is_read ASC, created_at DESC, notification_id DESC
         LIMIT 5'
    );
    $notificationStatement->execute(['user_id' => (int) $user['user_id']]);
    $studentNotificationRows = $notificationStatement->fetchAll();

    $attendanceBySubjectStatement = $connection->prepare(
        "SELECT su.subject_code, su.subject_name,
                SUM(a.attendance_status = 'Present') AS present_count,
                SUM(a.attendance_status = 'Absent') AS absent_count,
                SUM(a.attendance_status = 'Late') AS late_count,
                SUM(a.attendance_status = 'Excused') AS excused_count
         FROM attendance a
         INNER JOIN enrollments e
           ON e.enrollment_id = a.enrollment_id
          AND e.student_id = a.student_id
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         WHERE e.student_id = :student_id
           AND e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
         GROUP BY su.subject_id, su.subject_code, su.subject_name
         ORDER BY su.subject_name"
    );
    $attendanceBySubjectStatement->execute([
        'student_id' => $studentId,
        'academic_year_id' => $activeAcademicYearId,
    ]);
    $studentAttendanceBySubject = $attendanceBySubjectStatement->fetchAll();

    $gradesBySubjectStatement = $connection->prepare(
        "SELECT su.subject_code, su.subject_name,
                ROUND(AVG(ss.score / NULLIF(a.total_points, 0) * 100), 1) AS average_percent,
                COUNT(ss.score_id) AS graded_count
         FROM enrollments e
         INNER JOIN semesters sem
           ON sem.academic_year_id = e.academic_year_id
          AND sem.status = 'Active'
         INNER JOIN teaching_assignments ta
           ON ta.section_id = e.section_id
          AND ta.semester_id = sem.semester_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         LEFT JOIN assessments a
           ON a.assignment_id = ta.assignment_id
          AND a.status IN ('Published', 'Completed')
          AND a.total_points > 0
         LEFT JOIN student_scores ss
           ON ss.assessment_id = a.assessment_id
          AND ss.student_id = e.student_id
         WHERE e.student_id = :student_id
           AND e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
         GROUP BY su.subject_id, su.subject_code, su.subject_name
         ORDER BY su.subject_name"
    );
    $gradesBySubjectStatement->execute([
        'student_id' => $studentId,
        'academic_year_id' => $activeAcademicYearId,
    ]);
    $studentGradesBySubject = $gradesBySubjectStatement->fetchAll();
    $studentGradeChartRows = array_values(array_filter(
        $studentGradesBySubject,
        static fn (array $row): bool => $row['average_percent'] !== null
    ));

    $scheduleStatement = $connection->prepare(
        "SELECT cs.start_time, cs.end_time, su.subject_code, su.subject_name,
                sec.section_name, CONCAT_WS(' — ', r.room_code, r.room_name) AS room
         FROM enrollments e
         INNER JOIN semesters sem
           ON sem.academic_year_id = e.academic_year_id
          AND sem.status = 'Active'
         INNER JOIN teaching_assignments ta
           ON ta.section_id = e.section_id
          AND ta.semester_id = sem.semester_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = e.section_id
         INNER JOIN class_schedules cs ON cs.assignment_id = ta.assignment_id
         LEFT JOIN rooms r ON r.room_id = cs.room_id
         WHERE e.student_id = :student_id
           AND e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
           AND cs.day_of_week = :day_of_week
           AND cs.schedule_status IN ('Active', 'Rescheduled')
         ORDER BY cs.start_time, su.subject_name"
    );
    $scheduleStatement->execute([
        'student_id' => $studentId,
        'academic_year_id' => $activeAcademicYearId,
        'day_of_week' => date('l'),
    ]);
    $studentScheduleRows = $scheduleStatement->fetchAll();

    $tuitionBalanceStatement = $connection->prepare(
        "SELECT
            ROUND(GREATEST(sf.amount - COALESCE(sf.discount_amount, 0), 0) * 100) AS net_cents,
            COALESCE(p.paid_cents, 0) AS paid_cents
         FROM student_fees sf
         INNER JOIN enrollments e ON e.enrollment_id = sf.enrollment_id
         INNER JOIN fee_structures fs ON fs.fee_structure_id = sf.fee_structure_id
         INNER JOIN fee_types ft ON ft.fee_type_id = fs.fee_type_id
         LEFT JOIN (
            SELECT student_fee_id, SUM(ROUND(total_amount * 100)) AS paid_cents
            FROM payments
            WHERE payment_status = 'Completed'
              AND student_fee_id IS NOT NULL
            GROUP BY student_fee_id
         ) p ON p.student_fee_id = sf.student_fee_id
         WHERE e.student_id = :student_id
           AND e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
           AND ft.fee_category = 'Tuition'
           AND sf.fee_status <> 'Waived'"
    );
    $tuitionBalanceStatement->execute([
        'student_id' => $studentId,
        'academic_year_id' => $activeAcademicYearId,
    ]);
    foreach ($tuitionBalanceStatement->fetchAll() as $feeBalance) {
        $netCents = max(0, (int) $feeBalance['net_cents']);
        $paidCents = (int) $feeBalance['paid_cents'];
        $tuitionBalanceCents += max(0, $netCents - $paidCents);
        $tuitionPaidCents += min($netCents, $paidCents);
    }

    $paymentHistoryStatement = $connection->prepare(
        "SELECT p.payment_date, p.total_amount, p.payment_status, p.payment_term, p.receipt_number
         FROM payments p
         INNER JOIN enrollments e ON e.enrollment_id = p.enrollment_id
         WHERE p.student_id = :student_id
           AND e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
         ORDER BY p.payment_date DESC, p.payment_id DESC
         LIMIT 5"
    );
    $paymentHistoryStatement->execute([
        'student_id' => $studentId,
        'academic_year_id' => $activeAcademicYearId,
    ]);
    $studentPaymentRows = $paymentHistoryStatement->fetchAll();

    $reportMonthKeys = [];
    $reportMonthLabels = [];
    for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
        $month = (new DateTimeImmutable('first day of this month'))->modify('-' . $monthsAgo . ' months');
        $reportMonthKeys[] = $month->format('Y-m');
        $reportMonthLabels[] = $month->format('M');
    }
    $monthStart = (new DateTimeImmutable('first day of this month'))->modify('-5 months')->format('Y-m-01');
    $paymentTrendMonths = array_fill_keys($reportMonthKeys, 0.0);
    $paymentTrendStatement = $connection->prepare(
        "SELECT DATE_FORMAT(p.payment_date, '%Y-%m') AS month_key,
                COALESCE(SUM(p.total_amount), 0) AS amount_total
         FROM payments p
         INNER JOIN enrollments e ON e.enrollment_id = p.enrollment_id
         WHERE p.student_id = :student_id
           AND e.academic_year_id = :academic_year_id
           AND e.enrollment_status = 'Enrolled'
           AND p.payment_status = 'Completed'
           AND p.payment_date >= :month_start
         GROUP BY month_key"
    );
    $paymentTrendStatement->execute([
        'student_id' => $studentId,
        'academic_year_id' => $activeAcademicYearId,
        'month_start' => $monthStart,
    ]);
    foreach ($paymentTrendStatement->fetchAll() as $monthRow) {
        if (array_key_exists($monthRow['month_key'], $paymentTrendMonths)) {
            $paymentTrendMonths[$monthRow['month_key']] = (float) $monthRow['amount_total'];
        }
    }
}

$dashboard = [
    'theme' => 'executive',
    'roleLabel' => 'Student',
    'pageTitle' => 'Student dashboard',
    'eyebrow' => 'Student workspace',
    'heading' => 'Your school day starts here.',
    'description' => 'A simple overview of your enrollment and attendance, all in one place.',
    'navigationStyle' => 'student',
    'sidebarTitle' => 'Student Workspace',
    'sidebarCaption' => 'Your School Records',
    'homeHref' => 'student-dashboard.php',
    'metricsHeading' => 'Your school overview',
    'navItems' => studentNavigationItems('dashboard', $unreadNotifications),
    'metrics' => [
        ['label' => 'Active enrollments', 'value' => number_format($enrollmentCount), 'note' => 'Your current enrollment records', 'tone' => 'navy'],
        ['label' => 'Attendance today', 'value' => number_format($attendanceCount), 'note' => 'Attendance entries for today', 'tone' => 'blue'],
        ['label' => 'Present records', 'value' => number_format($presentCount), 'note' => 'All recorded present days', 'tone' => 'sage'],
        ['label' => 'Account status', 'value' => 'Active', 'note' => 'Your school account is active', 'tone' => 'gold'],
    ],
    'summary' => [
        'heading' => 'Your academic and finance reports',
        'periodLabel' => 'Active academic year · updated on load',
        'cards' => [
            [
                'label' => 'Tuition balance',
                'value' => dashboardFormatCurrency($tuitionBalanceCents / 100),
                'note' => 'Outstanding tuition after completed payments',
                'href' => 'student-section.php?section=financial-report',
            ],
            [
                'label' => 'Tuition paid',
                'value' => dashboardFormatCurrency($tuitionPaidCents / 100),
                'note' => 'Completed payments this academic year',
                'href' => 'student-section.php?section=payments',
            ],
            [
                'label' => 'Classes today',
                'value' => number_format(count($studentScheduleRows)),
                'note' => date('l'),
                'href' => 'student-section.php?section=schedule',
            ],
            [
                'label' => 'Subjects with grades',
                'value' => number_format(count($studentGradeChartRows)),
                'note' => 'Subjects with published score records',
                'href' => 'student-section.php?section=grades',
            ],
        ],
        'charts' => [
            [
                'title' => 'Attendance by subject',
                'description' => 'Attendance record totals for each subject in your current enrollment.',
                'format' => 'integer',
                'labels' => array_map(
                    static fn (array $row): string => $row['subject_code'] . ' · ' . $row['subject_name'],
                    $studentAttendanceBySubject
                ),
                'series' => [
                    ['label' => 'Present', 'tone' => 'sage', 'values' => array_map('intval', array_column($studentAttendanceBySubject, 'present_count'))],
                    ['label' => 'Absent', 'tone' => 'danger', 'values' => array_map('intval', array_column($studentAttendanceBySubject, 'absent_count'))],
                    ['label' => 'Late', 'tone' => 'gold', 'values' => array_map('intval', array_column($studentAttendanceBySubject, 'late_count'))],
                    ['label' => 'Excused', 'tone' => 'blue', 'values' => array_map('intval', array_column($studentAttendanceBySubject, 'excused_count'))],
                ],
                'emptyMessage' => 'No attendance records are available for your current subjects yet.',
            ],
            [
                'title' => 'Current grades by subject',
                'description' => 'Average of published assessment scores as a percentage of available points.',
                'format' => 'percent',
                'labels' => array_map(
                    static fn (array $row): string => $row['subject_code'] . ' · ' . $row['subject_name'],
                    $studentGradeChartRows
                ),
                'series' => [
                    ['label' => 'Average', 'tone' => 'blue', 'values' => array_map('floatval', array_column($studentGradeChartRows, 'average_percent'))],
                ],
                'emptyMessage' => 'Your teachers have not published grade records for your subjects yet.',
            ],
            [
                'title' => 'Completed payments',
                'description' => 'Your completed payment amounts by month for the last six months.',
                'format' => 'amount',
                'labels' => $reportMonthLabels ?? [],
                'series' => [
                    ['label' => 'Paid', 'tone' => 'gold', 'values' => array_values($paymentTrendMonths)],
                ],
                'emptyMessage' => 'No completed payments are available for this period.',
            ],
        ],
        'lists' => [
            [
                'title' => 'Grades by subject',
                'description' => 'Published assessment results for each of your active subjects.',
                'items' => array_map(
                    static fn (array $grade): array => [
                        'title' => $grade['subject_code'] . ' · ' . $grade['subject_name'],
                        'detail' => (int) $grade['graded_count'] > 0
                            ? number_format((int) $grade['graded_count']) . ' recorded scores'
                            : 'No published scores yet',
                        'status' => $grade['average_percent'] === null
                            ? 'Pending'
                            : number_format((float) $grade['average_percent'], 1) . '% average',
                    ],
                    $studentGradesBySubject
                ),
                'emptyMessage' => 'No active subjects are linked to your current enrollment.',
                'href' => 'student-section.php?section=grades',
                'linkLabel' => 'View grades',
            ],
            [
                'title' => 'Today’s class schedule',
                'description' => date('l') . ' · classes in your enrolled sections',
                'items' => array_map(
                    static fn (array $schedule): array => [
                        'title' => date('g:i A', strtotime((string) $schedule['start_time']))
                            . '–' . date('g:i A', strtotime((string) $schedule['end_time']))
                            . ' · ' . $schedule['subject_code'] . ' · ' . $schedule['section_name'],
                        'detail' => $schedule['room'] ?: 'Room not listed',
                        'status' => '',
                    ],
                    $studentScheduleRows
                ),
                'emptyMessage' => 'No classes are scheduled for you today.',
                'href' => 'student-section.php?section=schedule',
                'linkLabel' => 'Full schedule',
            ],
            [
                'title' => 'Recent payment history',
                'description' => 'Your five most recent payments for this academic year.',
                'items' => array_map(
                    static fn (array $payment): array => [
                        'title' => date('M j, Y', strtotime((string) $payment['payment_date']))
                            . ' · ' . dashboardFormatCurrency((float) $payment['total_amount']),
                        'detail' => $payment['payment_term'] ?: ($payment['receipt_number'] ?: 'Payment record'),
                        'status' => (string) $payment['payment_status'],
                    ],
                    $studentPaymentRows
                ),
                'emptyMessage' => 'No payments are recorded for this academic year yet.',
                'href' => 'student-section.php?section=payments',
                'linkLabel' => 'Payment history',
            ],
        ],
    ],
    'responsibilitiesHeading' => 'Your student responsibilities',
    'responsibilities' => [
        ['title' => 'Take part in learning', 'description' => 'Attend your classes, participate in learning activities, and complete assigned work.'],
        ['title' => 'Keep attendance accurate', 'description' => 'Follow your school’s attendance process and tell a teacher if a record looks incorrect.'],
        ['title' => 'Review your progress', 'description' => 'Check feedback and academic updates, and ask questions when something is unclear.'],
        ['title' => 'Ask for support', 'description' => 'Contact a teacher or school administrator when you need help with schoolwork or records.'],
    ],
    'reviewOverline' => 'Today’s check-in',
    'reviewHeading' => 'Student day',
    'reviewDescription' => 'Your enrollment and attendance counts update when you load this page.',
    'reviewItems' => [
        [
            'text' => $enrollmentCount > 0 ? 'You have an active enrollment.' : 'No active enrollment is linked to your account yet.',
            'status' => $enrollmentCount > 0 ? 'Enrolled' : 'Review',
            'tone' => $enrollmentCount > 0 ? 'good' : 'attention',
        ],
        [
            'text' => $attendanceCount > 0 ? 'An attendance record is available for today.' : 'No attendance record is available for today yet.',
            'status' => $attendanceCount > 0 ? 'Recorded' : 'Check',
            'tone' => $attendanceCount > 0 ? 'good' : 'attention',
        ],
        ['text' => 'Your account is active.', 'status' => 'Ready', 'tone' => 'good'],
    ],
    'reviewFootnote' => 'Contact your school administrator if your student records are missing.',
    'assessmentDashboard' => [
        'role' => 'student',
        'studentNumber' => $studentNumber,
        'classes' => $studentClassRows,
        'assessments' => $studentAssessmentRows,
        'notifications' => $studentNotificationRows,
    ],
];

$dashboardLayout = dirname(__DIR__, 5) . '/app/Shared/dashboard_layout.php';
if (!is_file($dashboardLayout)) {
    header('Location: account.php', true, 303);
    exit;
}

require $dashboardLayout;
