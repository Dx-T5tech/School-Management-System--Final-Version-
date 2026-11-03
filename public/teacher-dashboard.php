<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';
require_once dirname(__DIR__) . '/app/Core/teacher.php';

$user = requireDashboardUser('Teacher');
$connection = getDatabaseConnection();
$today = date('l');

$statement = $connection->prepare('SELECT teacher_id FROM teachers WHERE user_id = :user_id LIMIT 1');
$statement->execute(['user_id' => (int) $user['user_id']]);
$teacherId = $statement->fetchColumn();
$assignmentCount = 0;
$scheduleCount = 0;
$attendanceCount = 0;
$pendingScoreCount = 0;
$teacherClassRows = [];
$recentAssessmentRows = [];
$teacherScheduleRows = [];
$teacherAttendanceByClass = [];
$teacherGradesByClass = [];

if ($teacherId !== false) {
    $teacherId = (int) $teacherId;

    $statement = $connection->prepare("SELECT COUNT(*) FROM teaching_assignments WHERE teacher_id = :teacher_id AND assignment_status = 'Active'");
    $statement->execute(['teacher_id' => $teacherId]);
    $assignmentCount = (int) $statement->fetchColumn();

    $statement = $connection->prepare(
        "SELECT COUNT(*)
         FROM class_schedules cs
         INNER JOIN teaching_assignments ta ON ta.assignment_id = cs.assignment_id
         WHERE ta.teacher_id = :teacher_id
           AND ta.assignment_status = 'Active'
           AND cs.day_of_week = :day_of_week
           AND cs.schedule_status = :schedule_status"
    );
    $statement->execute([
        'teacher_id' => $teacherId,
        'day_of_week' => $today,
        'schedule_status' => 'Active',
    ]);
    $scheduleCount = (int) $statement->fetchColumn();

    $statement = $connection->prepare(
        'SELECT COUNT(*)
         FROM teacher_attendance
         WHERE teacher_id = :teacher_id
           AND attendance_date = CURRENT_DATE()'
    );
    $statement->execute(['teacher_id' => $teacherId]);
    $attendanceCount = (int) $statement->fetchColumn();

    $statement = $connection->prepare(
        "SELECT COUNT(*)
         FROM assessments a
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN enrollments e
           ON e.section_id = ta.section_id
          AND e.enrollment_status = 'Enrolled'
         LEFT JOIN student_scores ss
           ON ss.assessment_id = a.assessment_id
          AND ss.student_id = e.student_id
         WHERE ta.teacher_id = :teacher_id
           AND ta.assignment_status = 'Active'
           AND a.status = 'Published'
           AND ss.assessment_id IS NULL"
    );
    $statement->execute(['teacher_id' => $teacherId]);
    $pendingScoreCount = (int) $statement->fetchColumn();

    $classStatement = $connection->prepare(
        "SELECT su.subject_code, su.subject_name, sec.section_name, sec.year_level,
                ay.year_name, sem.semester_name,
                COUNT(DISTINCT e.enrollment_id) AS enrolled_students
         FROM teaching_assignments ta
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN academic_years ay ON ay.academic_year_id = sec.academic_year_id
         INNER JOIN semesters sem ON sem.semester_id = ta.semester_id
         LEFT JOIN enrollments e
           ON e.section_id = sec.section_id
          AND e.academic_year_id = sec.academic_year_id
          AND e.enrollment_status = 'Enrolled'
         WHERE ta.teacher_id = :teacher_id
           AND ta.assignment_status = 'Active'
         GROUP BY ta.assignment_id, su.subject_code, su.subject_name, sec.section_name,
                  sec.year_level, ay.year_name, sem.semester_name
         ORDER BY su.subject_name, sec.section_name"
    );
    $classStatement->execute(['teacher_id' => $teacherId]);
    $teacherClassRows = $classStatement->fetchAll();

    $assessmentStatement = $connection->prepare(
        "SELECT a.assessment_title, a.assessment_type, a.total_points,
                a.assessment_date, a.due_date, a.status, su.subject_code, sec.section_name,
                gp.period_name
         FROM assessments a
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         LEFT JOIN grading_periods gp ON gp.grading_period_id = a.grading_period_id
         WHERE ta.teacher_id = :teacher_id
           AND ta.assignment_status = 'Active'
         ORDER BY a.created_at DESC, a.assessment_id DESC
         LIMIT 6"
    );
    $assessmentStatement->execute(['teacher_id' => $teacherId]);
    $recentAssessmentRows = $assessmentStatement->fetchAll();

    $scheduleStatement = $connection->prepare(
        "SELECT cs.start_time, cs.end_time, su.subject_code, su.subject_name,
                sec.section_name, r.room_code, r.room_name
         FROM class_schedules cs
         INNER JOIN teaching_assignments ta ON ta.assignment_id = cs.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN rooms r ON r.room_id = cs.room_id
         WHERE ta.teacher_id = :teacher_id
           AND ta.assignment_status = 'Active'
           AND cs.day_of_week = :day_of_week
           AND cs.schedule_status = 'Active'
         ORDER BY cs.start_time"
    );
    $scheduleStatement->execute(['teacher_id' => $teacherId, 'day_of_week' => $today]);
    $teacherScheduleRows = $scheduleStatement->fetchAll();

    $attendanceStatement = $connection->prepare(
        "SELECT CONCAT(su.subject_code, ' · ', sec.section_name) AS class_label,
                SUM(a.attendance_status = 'Present') AS present_count,
                SUM(a.attendance_status = 'Absent') AS absent_count,
                SUM(a.attendance_status = 'Late') AS late_count,
                SUM(a.attendance_status = 'Excused') AS excused_count
         FROM attendance a
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         WHERE ta.teacher_id = :teacher_id
           AND ta.assignment_status = 'Active'
           AND a.attendance_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
         GROUP BY ta.assignment_id, su.subject_code, sec.section_name
         ORDER BY su.subject_code, sec.section_name"
    );
    $attendanceStatement->execute(['teacher_id' => $teacherId]);
    $teacherAttendanceByClass = $attendanceStatement->fetchAll();

    $gradeStatement = $connection->prepare(
        "SELECT CONCAT(su.subject_code, ' · ', sec.section_name) AS class_label,
                ROUND(AVG(ss.score / NULLIF(a.total_points, 0) * 100), 1) AS average_percent,
                COUNT(ss.score_id) AS graded_count
         FROM teaching_assignments ta
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         LEFT JOIN assessments a
           ON a.assignment_id = ta.assignment_id
          AND a.status IN ('Published', 'Completed')
          AND a.total_points > 0
         LEFT JOIN student_scores ss ON ss.assessment_id = a.assessment_id
         WHERE ta.teacher_id = :teacher_id
           AND ta.assignment_status = 'Active'
         GROUP BY ta.assignment_id, su.subject_code, sec.section_name
         ORDER BY su.subject_code, sec.section_name"
    );
    $gradeStatement->execute(['teacher_id' => $teacherId]);
    $teacherGradesByClass = $gradeStatement->fetchAll();
}
$teacherGradesWithScores = array_values(array_filter(
    $teacherGradesByClass,
    static fn (array $row): bool => $row['average_percent'] !== null
));

$dashboard = [
    'theme' => 'operations',
    'roleLabel' => 'Teacher',
    'pageTitle' => 'Teacher dashboard',
    'eyebrow' => 'Teaching workspace',
    'heading' => 'Your classes, at a glance.',
    'description' => 'A clear starting point for today’s teaching, schedules, and classroom work.',
    'metricsHeading' => 'Your teaching overview',
    'navigationStyle' => 'teacher',
    'navItems' => teacherNavigationItems(),
    'sidebarTitle' => 'Teacher Workspace',
    'sidebarCaption' => 'Classroom Management',
    'homeHref' => 'teacher-dashboard.php',
    'metrics' => [
        ['label' => 'Teaching assignments', 'value' => number_format($assignmentCount), 'note' => 'Classes assigned to you', 'tone' => 'navy'],
        ['label' => 'Classes scheduled today', 'value' => number_format($scheduleCount), 'note' => $today, 'tone' => 'blue'],
        ['label' => 'Attendance entries today', 'value' => number_format($attendanceCount), 'note' => 'Your recorded attendance', 'tone' => 'gold'],
        ['label' => 'Account status', 'value' => 'Active', 'note' => 'Your school account is active', 'tone' => 'sage'],
    ],
    'summary' => [
        'heading' => 'Teaching reports and schedule',
        'periodLabel' => 'Schedule today · attendance last 30 days',
        'cards' => [
            [
                'label' => 'Classes today',
                'value' => number_format(count($teacherScheduleRows)),
                'note' => $today,
                'href' => 'teacher-section.php?section=schedule',
            ],
            [
                'label' => 'Scores to enter',
                'value' => number_format($pendingScoreCount),
                'note' => 'Published assessment slots awaiting grades',
                'href' => 'teacher-section.php?section=grades',
            ],
            [
                'label' => 'Attendance records',
                'value' => number_format(array_sum(array_map(
                    static fn (array $row): int => (int) $row['present_count']
                        + (int) $row['absent_count']
                        + (int) $row['late_count']
                        + (int) $row['excused_count'],
                    $teacherAttendanceByClass
                ))),
                'note' => 'Across your classes in the last 30 days',
                'href' => 'teacher-section.php?section=attendance',
            ],
        ],
        'charts' => [
            [
                'title' => 'Attendance by class',
                'description' => 'Recorded student attendance by status over the last 30 days.',
                'format' => 'integer',
                'labels' => array_column($teacherAttendanceByClass, 'class_label'),
                'series' => [
                    ['label' => 'Present', 'tone' => 'sage', 'values' => array_map('intval', array_column($teacherAttendanceByClass, 'present_count'))],
                    ['label' => 'Absent', 'tone' => 'danger', 'values' => array_map('intval', array_column($teacherAttendanceByClass, 'absent_count'))],
                    ['label' => 'Late', 'tone' => 'gold', 'values' => array_map('intval', array_column($teacherAttendanceByClass, 'late_count'))],
                    ['label' => 'Excused', 'tone' => 'blue', 'values' => array_map('intval', array_column($teacherAttendanceByClass, 'excused_count'))],
                ],
                'emptyMessage' => 'No class attendance has been recorded in the last 30 days.',
            ],
            [
                'title' => 'Average grades by class',
                'description' => 'Average score as a percentage of assessment points for your classes.',
                'format' => 'percent',
                'labels' => array_column($teacherGradesWithScores, 'class_label'),
                'series' => [
                    ['label' => 'Average', 'tone' => 'blue', 'values' => array_map(
                        'floatval',
                        array_column($teacherGradesWithScores, 'average_percent')
                    )],
                ],
                'emptyMessage' => 'No graded assessments are available for your classes yet.',
            ],
        ],
        'lists' => [
            [
                'title' => 'Today’s schedule',
                'description' => $today . ' · classes assigned to you',
                'items' => array_map(
                    static fn (array $schedule): array => [
                        'title' => date('g:i A', strtotime((string) $schedule['start_time']))
                            . '–' . date('g:i A', strtotime((string) $schedule['end_time']))
                            . ' · ' . $schedule['subject_code'] . ' · ' . $schedule['section_name'],
                        'detail' => $schedule['room_code'] . ' — ' . $schedule['room_name'],
                        'status' => '',
                    ],
                    $teacherScheduleRows
                ),
                'emptyMessage' => 'No active classes are scheduled for you today.',
                'href' => 'teacher-section.php?section=schedule',
                'linkLabel' => 'View schedule',
            ],
            [
                'title' => 'Grade summary by class',
                'description' => 'Published assessment results across every teaching assignment.',
                'items' => array_map(
                    static fn (array $grade): array => [
                        'title' => $grade['class_label'],
                        'detail' => (int) $grade['graded_count'] > 0
                            ? number_format((int) $grade['graded_count']) . ' recorded scores'
                            : 'No graded assessments yet',
                        'status' => $grade['average_percent'] === null
                            ? 'No grades'
                            : number_format((float) $grade['average_percent'], 1) . '% average',
                    ],
                    $teacherGradesByClass
                ),
                'emptyMessage' => 'No teaching assignments are linked to your account yet.',
                'href' => 'teacher-section.php?section=report-cards',
                'linkLabel' => 'View report cards',
            ],
        ],
    ],
    'responsibilitiesHeading' => 'Your teaching responsibilities',
    'responsibilities' => [
        ['title' => 'Prepare and deliver lessons', 'description' => 'Review teaching assignments, follow class schedules, and guide students through learning activities.'],
        ['title' => 'Track attendance and progress', 'description' => 'Record classroom attendance and keep academic records accurate and up to date.'],
        ['title' => 'Assess and support students', 'description' => 'Provide feedback, identify learning needs, and direct students to appropriate support.'],
        ['title' => 'Communicate professionally', 'description' => 'Share relevant updates with students, families, and school staff through approved channels.'],
    ],
    'reviewOverline' => 'Today’s check-in',
    'reviewHeading' => 'Teaching day',
    'reviewDescription' => 'Your assignment and attendance counts update when you load this page.',
    'reviewItems' => [
        [
            'text' => $assignmentCount > 0 ? 'Your teaching assignments are available.' : 'No teaching assignments are linked to your account yet.',
            'status' => $assignmentCount > 0 ? 'Ready' : 'Review',
            'tone' => $assignmentCount > 0 ? 'good' : 'attention',
        ],
        [
            'text' => $scheduleCount > 0 ? 'You have classes scheduled today.' : 'No active classes are scheduled for today.',
            'status' => $scheduleCount > 0 ? 'Scheduled' : 'Clear',
            'tone' => $scheduleCount > 0 ? 'good' : 'attention',
        ],
        [
            'text' => $attendanceCount > 0 ? 'Your attendance has been recorded today.' : 'Your attendance has not been recorded today.',
            'status' => $attendanceCount > 0 ? 'Recorded' : 'Check',
            'tone' => $attendanceCount > 0 ? 'good' : 'attention',
        ],
    ],
    'reviewFootnote' => 'Contact the school administrator if your teaching assignments are missing.',
    'assessmentDashboard' => [
        'role' => 'teacher',
        'classes' => $teacherClassRows,
        'assessments' => $recentAssessmentRows,
        'pendingScoreCount' => $pendingScoreCount,
    ],
];

$dashboardLayout = dirname(__DIR__) . '/app/Shared/dashboard_layout.php';
if (!is_file($dashboardLayout)) {
    header('Location: account.php', true, 303);
    exit;
}

require $dashboardLayout;
