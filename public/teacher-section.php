<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';
require_once dirname(__DIR__) . '/app/Core/teacher.php';

$user = requireDashboardUser('Teacher');
$connection = getDatabaseConnection();
$teacherStatement = $connection->prepare(
    'SELECT teacher_id FROM teachers WHERE user_id = :user_id LIMIT 1'
);
$teacherStatement->execute(['user_id' => (int) $user['user_id']]);
$teacherIdValue = $teacherStatement->fetchColumn();
$teacherId = $teacherIdValue === false ? null : (int) $teacherIdValue;

$sectionDefinitions = [
    'classes' => [
        'title' => 'My Classes',
        'eyebrow' => 'Teaching assignments',
        'description' => 'Review the subjects, sections, and active enrollments assigned to you.',
    ],
    'students' => [
        'title' => 'My Students',
        'eyebrow' => 'Class rosters',
        'description' => 'View students currently enrolled in your assigned class sections.',
    ],
    'schedule' => [
        'title' => 'Class Schedule',
        'eyebrow' => 'Teaching timetable',
        'description' => 'Check the rooms and times for your assigned classes.',
    ],
    'attendance' => [
        'title' => 'Attendance',
        'eyebrow' => 'Classroom records',
        'description' => 'Record or update attendance for students in your assigned classes.',
    ],
    'assessments' => [
        'title' => 'Assessments',
        'eyebrow' => 'Learning activities',
        'description' => 'Create assessments for your assigned classes and grading periods.',
    ],
    'grades' => [
        'title' => 'Grade Management',
        'eyebrow' => 'Scores and term grades',
        'description' => 'Enter assessment scores and maintain term grades for your students.',
    ],
    'report-cards' => [
        'title' => 'Report Cards',
        'eyebrow' => 'Published student results',
        'description' => 'Review report cards for students in your assigned classes.',
    ],
    'performance' => [
        'title' => 'Student Performance',
        'eyebrow' => 'Learning progress',
        'description' => 'Review assessment averages, attendance, and term grades across your classes.',
    ],
];

$sectionKey = $_GET['section'] ?? '';
if (!is_string($sectionKey) || !isset($sectionDefinitions[$sectionKey])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Teacher section not found.');
}

$sectionDefinition = $sectionDefinitions[$sectionKey];
$errorMessage = '';
$fieldErrors = [];
$oldInput = [];
$oldAttendanceEnrollmentIds = [];
$oldInputAction = '';
$flashMessage = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
$restoreGradeDraft = false;
if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && $sectionKey === 'grades'
    && is_array($_SESSION['teacher_grade_draft_resume'] ?? null)
) {
    $gradeDraft = $_SESSION['teacher_grade_draft_resume'];
    unset($_SESSION['teacher_grade_draft_resume']);
    if (
        (int) ($gradeDraft['user_id'] ?? 0) === (int) $user['user_id']
        && is_array($gradeDraft['input'] ?? null)
        && (int) ($gradeDraft['created_at'] ?? 0) >= time() - 1800
    ) {
        $oldInput = $gradeDraft['input'];
        $oldInputAction = 'term-grade';
        $restoreGradeDraft = true;
    }
}
$csrfToken = appCsrfToken();

$enum = static fn (string ...$values): array => array_combine($values, $values);
$isValidDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
$field = static fn (
    string $label,
    string $type = 'text',
    bool $required = false,
    array|string|null $options = null
): array => [
    'label' => $label,
    'type' => $type,
    'required' => $required,
    'options' => $options,
];

$formDefinitions = [
    'attendance' => [
        'title' => 'Record student attendance',
        'fields' => [
            'assignment_id' => $field('Class', 'select', true, 'attendance_assignments'),
            'attendance_date' => $field('Attendance date', 'date', true),
            'attendance_status' => $field('Status', 'select', true, $enum('Present', 'Absent', 'Excused')),
            'remarks' => $field('Remarks', 'textarea'),
        ],
        'submitLabel' => 'Save attendance for selected students',
    ],
    'assessment' => [
        'title' => 'Create an assessment',
        'fields' => [
            'assignment_id' => $field('Class assignment', 'select', true, 'assignments'),
            'grading_period_id' => $field('Grading period', 'select', true, 'grading_periods'),
            'category_id' => $field('Category', 'select', true, 'categories'),
            'assessment_title' => $field('Assessment title', 'text', true),
            'assessment_type' => $field('Type', 'select', true, $enum('Quiz', 'Exam', 'Assignment', 'Project', 'Performance Task', 'Activity')),
            'total_points' => $field('Total points', 'number', true),
            'assessment_date' => $field('Assessment date', 'date'),
            'due_date' => $field('Due date', 'date'),
            'description' => $field('Instructions', 'textarea'),
            'status' => $field('Status', 'select', true, $enum('Draft', 'Published', 'Completed')),
        ],
        'submitLabel' => 'Create assessment',
    ],
    'score' => [
        'title' => 'Enter an assessment score',
        'fields' => [
            'assessment_id' => $field('Assessment', 'select', true, 'assessments'),
            'student_id' => $field('Student', 'select', true, 'students'),
            'score' => $field('Score', 'number', true),
            'remarks' => $field('Feedback', 'textarea'),
        ],
        'submitLabel' => 'Save score',
    ],
    'term-grade' => [
        'title' => 'Record a term grade',
        'fields' => [
            'assignment_id' => $field('Class assignment', 'select', true, 'assignments'),
            'enrollment_id' => $field('Student', 'select', true, 'enrollments'),
            'grading_period_id' => $field('Grading period', 'select', true, 'grading_periods'),
            'remarks' => $field('Result', 'select', true, $enum('Passed', 'Failed', 'Incomplete', 'Dropped', 'Pending')),
        ],
        'submitLabel' => 'Save term grade',
    ],
];

$getAssessmentComponentScores = static function (
    PDO $connection,
    int $assignmentId,
    int $enrollmentId,
    int $periodId
): array {
    $statement = $connection->prepare(
        "SELECT a.assessment_type, COUNT(*) AS assessment_count,
                SUM(ss.score) AS points_earned, SUM(a.total_points) AS points_possible
         FROM student_scores ss
         INNER JOIN assessments a ON a.assessment_id = ss.assessment_id
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN enrollments e ON e.enrollment_id = :enrollment_id
           AND e.student_id = ss.student_id
           AND e.section_id = sec.section_id
           AND e.academic_year_id = sec.academic_year_id
           AND e.enrollment_status = 'Enrolled'
         WHERE ta.assignment_id = :assignment_id
           AND a.grading_period_id = :period_id
           AND a.assessment_type IN ('Exam', 'Quiz')
           AND a.status IN ('Published', 'Completed')
         GROUP BY a.assessment_type"
    );
    $statement->execute([
        'enrollment_id' => $enrollmentId,
        'assignment_id' => $assignmentId,
        'period_id' => $periodId,
    ]);

    $components = [
        'component_exam' => null,
        'component_quizzes' => null,
        'exam_count' => 0,
        'quiz_count' => 0,
        'exam_points_earned' => 0.0,
        'exam_points_possible' => 0.0,
        'quiz_points_earned' => 0.0,
        'quiz_points_possible' => 0.0,
    ];
    foreach ($statement->fetchAll() as $row) {
        $type = $row['assessment_type'] === 'Exam' ? 'exam' : 'quiz';
        $pointsPossible = (float) $row['points_possible'];
        if ($pointsPossible <= 0) {
            continue;
        }
        $componentKey = $type === 'exam' ? 'component_exam' : 'component_quizzes';
        $components[$componentKey] = round((float) $row['points_earned'] / $pointsPossible * 100, 1);
        $components["{$type}_count"] = (int) $row['assessment_count'];
        $components["{$type}_points_earned"] = (float) $row['points_earned'];
        $components["{$type}_points_possible"] = $pointsPossible;
    }

    return $components;
};

$recalculateSavedGrades = static function (
    PDO $connection,
    int $enrollmentId,
    int $assignmentId,
    ?int $preserveRemarksPeriodId = null
) use ($getAssessmentComponentScores): array {
    $statement = $connection->prepare(
        'SELECT gr.grade_id, gr.grading_period_id, gp.period_name,
                gr.preliminary_grade, gr.midterm_grade, gr.final_grade,
                gr.component_exam, gr.component_quizzes,
                gr.component_attendance, gr.component_recitation,
                gp.academic_year_id, gp.semester_id
         FROM grade_records gr
         INNER JOIN teaching_assignments ta ON ta.assignment_id = gr.assignment_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN enrollments e ON e.enrollment_id = gr.enrollment_id
           AND e.section_id = sec.section_id
           AND e.academic_year_id = sec.academic_year_id
           AND e.enrollment_status = \'Enrolled\'
         INNER JOIN grading_periods gp ON gp.grading_period_id = gr.grading_period_id
           AND gp.academic_year_id = sec.academic_year_id
           AND (gp.semester_id IS NULL OR gp.semester_id = ta.semester_id)
         WHERE gr.enrollment_id = :enrollment_id
           AND gr.assignment_id = :assignment_id
         ORDER BY gp.start_date, gp.grading_period_id, gr.grade_id'
    );
    $statement->execute([
        'enrollment_id' => $enrollmentId,
        'assignment_id' => $assignmentId,
    ]);
    $rows = $statement->fetchAll();
    $rollingGrade = null;
    $rollingStage = null;
    $finalGradeChanged = false;
    $updatedCount = 0;
    $invalidatedCount = 0;
    $numberIsUnchanged = static function (mixed $before, ?float $after): bool {
        if ($before === null || $after === null) {
            return $before === null && $after === null;
        }
        return abs((float) $before - $after) < 0.005;
    };

    foreach ($rows as $row) {
        $stage = match (strtolower(trim((string) $row['period_name']))) {
            'prelim', 'preliminary' => 'prelim',
            'midterm' => 'midterm',
            'final' => 'final',
            default => null,
        };
        if ($stage === null) {
            continue;
        }
        $assessmentComponents = $getAssessmentComponentScores(
            $connection,
            $assignmentId,
            $enrollmentId,
            (int) $row['grading_period_id']
        );
        $components = [
            $assessmentComponents['component_exam'],
            $assessmentComponents['component_quizzes'],
            $row['component_attendance'] === null ? null : (float) $row['component_attendance'],
            $row['component_recitation'] === null ? null : (float) $row['component_recitation'],
        ];
        $calculatedGrade = null;
        $hasAllComponents = !in_array(null, $components, true);
        if (
            $hasAllComponents
            && (
                $stage === 'prelim'
                || ($stage === 'midterm' && $rollingStage === 'prelim' && $rollingGrade !== null)
                || ($stage === 'final' && $rollingStage === 'midterm' && $rollingGrade !== null)
            )
        ) {
            $weightedComponents = round(
                (float) $components[0] * 0.50
                + (float) $components[1] * 0.25
                + (float) $components[2] * 0.10
                + (float) $components[3] * 0.15,
                1
            );
            $calculatedGrade = $stage === 'prelim'
                ? $weightedComponents
                : round($rollingGrade * 0.25 + $weightedComponents * 0.75, 1);
        }
        $gradeColumn = match ($stage) {
            'prelim' => 'preliminary_grade',
            'midterm' => 'midterm_grade',
            'final' => 'final_grade',
        };
        $hasChanged = !$numberIsUnchanged($row[$gradeColumn], $calculatedGrade)
            || !$numberIsUnchanged($row['component_exam'], $components[0])
            || !$numberIsUnchanged($row['component_quizzes'], $components[1]);
        $resetResultStatus = $hasChanged
            && ($preserveRemarksPeriodId === null || (int) $row['grading_period_id'] !== $preserveRemarksPeriodId);
        $update = $connection->prepare(
            "UPDATE grade_records
             SET {$gradeColumn} = :grade,
                 component_exam = :component_exam,
                 component_quizzes = :component_quizzes,
                 remarks = CASE WHEN :reset_status = 1 THEN 'Pending' ELSE remarks END
             WHERE grade_id = :grade_id"
        );
        $update->execute([
            'grade' => $calculatedGrade,
            'component_exam' => $components[0],
            'component_quizzes' => $components[1],
            'reset_status' => $resetResultStatus ? 1 : 0,
            'grade_id' => (int) $row['grade_id'],
        ]);
        if ($hasChanged) {
            $updatedCount++;
        }
        if ($calculatedGrade === null && $row[$gradeColumn] !== null) {
            $invalidatedCount++;
        }
        if ($stage === 'final') {
            $finalGradeChanged = $hasChanged;
        }
        $rollingGrade = $calculatedGrade;
        $rollingStage = $stage;
    }

    return [
        'final_grade_changed' => $finalGradeChanged,
        'has_final_grade_row' => in_array('final', array_map(
            static fn (array $row): string => strtolower(trim((string) $row['period_name'])),
            $rows
        ), true),
        'updated_count' => $updatedCount,
        'invalidated_count' => $invalidatedCount,
    ];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your security token has expired. Reload this page and try again.';
    } elseif ($teacherId === null) {
        $errorMessage = 'A teacher profile is not linked to your account. Contact the school administrator.';
    } else {
        $action = teacherPostString('action');
        $oldInputAction = $action;
        foreach ($_POST as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $oldInput[$key] = trim($value);
            }
        }

        if ($action === 'attendance') {
            $assignmentId = teacherPostInt('assignment_id');
            $attendanceDate = teacherPostString('attendance_date');
            $attendanceStatus = teacherPostString('attendance_status');
            $remarks = teacherPostString('remarks');
            $postedEnrollmentIds = $_POST['enrollment_ids'] ?? null;
            $invalidEnrollmentInput = false;
            if (is_array($postedEnrollmentIds)) {
                foreach ($postedEnrollmentIds as $postedEnrollmentId) {
                    $validatedEnrollmentId = filter_var(
                        $postedEnrollmentId,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1]]
                    );
                    if ($validatedEnrollmentId !== false) {
                        $oldAttendanceEnrollmentIds[] = (int) $validatedEnrollmentId;
                    } else {
                        $invalidEnrollmentInput = true;
                    }
                }
                $oldAttendanceEnrollmentIds = array_values(array_unique($oldAttendanceEnrollmentIds));
            } elseif (array_key_exists('enrollment_ids', $_POST)) {
                $invalidEnrollmentInput = true;
            }

            if ($assignmentId === null) {
                $fieldErrors['assignment_id'] = 'Choose a class.';
            }
            if ($invalidEnrollmentInput) {
                $fieldErrors['enrollment_ids'] = 'Select valid students from this class.';
            } elseif ($oldAttendanceEnrollmentIds === []) {
                $fieldErrors['enrollment_ids'] = 'Select at least one student from this class.';
            }
            if (!$isValidDate($attendanceDate)) {
                $fieldErrors['attendance_date'] = 'Enter a valid attendance date.';
            }
            if (!in_array($attendanceStatus, ['Present', 'Absent', 'Excused'], true)) {
                $fieldErrors['attendance_status'] = 'Choose an available attendance status.';
            }

            $scheduleDate = $isValidDate($attendanceDate)
                ? DateTimeImmutable::createFromFormat('!Y-m-d', $attendanceDate)
                : false;
            $attendanceSchedule = false;
            $assignmentContext = false;
            if ($fieldErrors === [] && $scheduleDate !== false && $assignmentId !== null) {
                $assignmentStatement = $connection->prepare(
                    'SELECT ta.assignment_id, sec.section_id, sec.academic_year_id
                     FROM teaching_assignments ta
                     INNER JOIN sections sec ON sec.section_id = ta.section_id
                     WHERE ta.assignment_id = :assignment_id
                       AND ta.teacher_id = :teacher_id
                     LIMIT 1'
                );
                $assignmentStatement->execute([
                    'assignment_id' => $assignmentId,
                    'teacher_id' => $teacherId,
                ]);
                $assignmentContext = $assignmentStatement->fetch();
                if ($assignmentContext === false) {
                    $fieldErrors['assignment_id'] = 'Choose one of your assigned classes.';
                } else {
                    $scheduleStatement = $connection->prepare(
                        "SELECT MIN(start_time) AS time_in, MAX(end_time) AS time_out
                         FROM class_schedules
                         WHERE assignment_id = :assignment_id
                           AND day_of_week = :day_of_week
                           AND schedule_status = 'Active'"
                    );
                    $scheduleStatement->execute([
                        'assignment_id' => $assignmentId,
                        'day_of_week' => $scheduleDate->format('l'),
                    ]);
                    $attendanceSchedule = $scheduleStatement->fetch();
                    if (
                        $attendanceSchedule === false
                        || $attendanceSchedule['time_in'] === null
                        || $attendanceSchedule['time_out'] === null
                    ) {
                        $fieldErrors['attendance_date'] = 'This class has no scheduled time on the selected date.';
                    }
                }
            }

            if ($fieldErrors === []) {
                $enrollmentPlaceholders = implode(
                    ', ',
                    array_fill(0, count($oldAttendanceEnrollmentIds), '?')
                );
                $rosterStatement = $connection->prepare(
                    "SELECT e.enrollment_id, e.student_id
                     FROM enrollments e
                     WHERE e.enrollment_id IN ({$enrollmentPlaceholders})
                       AND e.section_id = ?
                       AND e.academic_year_id = ?
                       AND e.enrollment_status = 'Enrolled'"
                );
                $rosterStatement->execute([
                    ...$oldAttendanceEnrollmentIds,
                    (int) $assignmentContext['section_id'],
                    (int) $assignmentContext['academic_year_id'],
                ]);
                $authorizedEnrollments = $rosterStatement->fetchAll();
                if (count($authorizedEnrollments) !== count($oldAttendanceEnrollmentIds)) {
                    $fieldErrors['enrollment_ids'] = 'Select only students enrolled in the chosen class.';
                }
            }

            if ($fieldErrors === []) {
                $connection->beginTransaction();
                $saveAttendance = $connection->prepare(
                    'INSERT INTO attendance
                        (student_id, enrollment_id, assignment_id, attendance_date, time_in, time_out, attendance_status, remarks, recorded_by)
                     VALUES
                        (:student_id, :enrollment_id, :assignment_id, :attendance_date, :time_in, :time_out, :attendance_status, :remarks, :recorded_by)
                     ON DUPLICATE KEY UPDATE
                        enrollment_id = VALUES(enrollment_id),
                        time_in = VALUES(time_in),
                        time_out = VALUES(time_out),
                        attendance_status = VALUES(attendance_status),
                        remarks = VALUES(remarks),
                        recorded_by = VALUES(recorded_by)'
                );
                foreach ($authorizedEnrollments as $enrollment) {
                    $saveAttendance->execute([
                        'student_id' => (int) $enrollment['student_id'],
                        'enrollment_id' => (int) $enrollment['enrollment_id'],
                        'assignment_id' => $assignmentId,
                        'attendance_date' => $attendanceDate,
                        'time_in' => $attendanceSchedule['time_in'],
                        'time_out' => $attendanceSchedule['time_out'],
                        'attendance_status' => $attendanceStatus,
                        'remarks' => $remarks === '' ? null : $remarks,
                        'recorded_by' => (int) $user['user_id'],
                    ]);
                }
                $studentCount = count($authorizedEnrollments);
                teacherRecordAudit(
                    $connection,
                    (int) $user['user_id'],
                    'Record attendance',
                    'Teacher attendance',
                    "Recorded {$attendanceStatus} attendance for {$studentCount} students in assignment {$assignmentId} on {$attendanceDate}."
                );
                $connection->commit();
                $_SESSION['teacher_flash'] = "Attendance saved for {$studentCount} students.";
                header('Location: teacher-section.php?section=attendance', true, 303);
                exit;
            }
            $errorMessage = 'Please correct the highlighted attendance details.';
        } elseif ($action === 'assessment') {
            $assignmentId = teacherPostInt('assignment_id');
            $periodId = teacherPostInt('grading_period_id');
            $categoryId = teacherPostInt('category_id');
            $title = teacherPostString('assessment_title');
            $assessmentType = teacherPostString('assessment_type');
            $totalPoints = teacherPostString('total_points');
            $assessmentDate = teacherPostString('assessment_date');
            $dueDate = teacherPostString('due_date');
            $description = teacherPostString('description');
            $status = teacherPostString('status');

            if ($assignmentId === null) {
                $fieldErrors['assignment_id'] = 'Choose a class assignment.';
            }
            if ($periodId === null) {
                $fieldErrors['grading_period_id'] = 'Choose a grading period.';
            }
            if ($categoryId === null) {
                $fieldErrors['category_id'] = 'Choose an assessment category.';
            }
            if ($title === '' || preg_match('/^.{1,200}$/us', $title) !== 1) {
                $fieldErrors['assessment_title'] = 'Enter a title of 1 to 200 characters.';
            }
            if (!in_array($assessmentType, ['Quiz', 'Exam', 'Assignment', 'Project', 'Performance Task', 'Activity'], true)) {
                $fieldErrors['assessment_type'] = 'Choose an available assessment type.';
            }
            if (!is_numeric($totalPoints) || (float) $totalPoints <= 0) {
                $fieldErrors['total_points'] = 'Enter a number greater than zero.';
            }
            if ($assessmentDate !== '' && !$isValidDate($assessmentDate)) {
                $fieldErrors['assessment_date'] = 'Enter a valid assessment date.';
            }
            if ($dueDate !== '' && !$isValidDate($dueDate)) {
                $fieldErrors['due_date'] = 'Enter a valid due date.';
            }
            if ($assessmentDate !== '' && $dueDate !== '' && $isValidDate($assessmentDate)
                && $isValidDate($dueDate) && $dueDate < $assessmentDate) {
                $fieldErrors['due_date'] = 'The due date must be on or after the assessment date.';
            }
            if (!in_array($status, ['Draft', 'Published', 'Completed'], true)) {
                $fieldErrors['status'] = 'Choose an available assessment status.';
            }

            if ($fieldErrors === []) {
                $assessmentScope = $connection->prepare(
                    "SELECT COUNT(*)
                     FROM teaching_assignments ta
                     INNER JOIN sections s ON s.section_id = ta.section_id
                     INNER JOIN grading_periods gp ON gp.grading_period_id = :period_id
                     WHERE ta.assignment_id = :assignment_id
                       AND ta.teacher_id = :teacher_id
                       AND gp.academic_year_id = s.academic_year_id
                       AND (gp.semester_id IS NULL OR gp.semester_id = ta.semester_id)"
                );
                $assessmentScope->execute([
                    'period_id' => $periodId,
                    'assignment_id' => $assignmentId,
                    'teacher_id' => $teacherId,
                ]);
                if ((int) $assessmentScope->fetchColumn() !== 1) {
                    $fieldErrors['grading_period_id'] = 'Choose a grading period for this assigned class.';
                }
            }

            if ($fieldErrors === []) {
                $connection->beginTransaction();
                $createAssessment = $connection->prepare(
                    'INSERT INTO assessments
                        (assignment_id, grading_period_id, category_id, assessment_title, assessment_type, description, total_points, assessment_date, due_date, status)
                     VALUES
                        (:assignment_id, :grading_period_id, :category_id, :assessment_title, :assessment_type, :description, :total_points, :assessment_date, :due_date, :status)'
                );
                $createAssessment->execute([
                    'assignment_id' => $assignmentId,
                    'grading_period_id' => $periodId,
                    'category_id' => $categoryId,
                    'assessment_title' => $title,
                    'assessment_type' => $assessmentType,
                    'description' => $description === '' ? null : $description,
                    'total_points' => (float) $totalPoints,
                    'assessment_date' => $assessmentDate === '' ? null : $assessmentDate,
                    'due_date' => $dueDate === '' ? null : $dueDate,
                    'status' => $status,
                ]);
                $assessmentId = (int) $connection->lastInsertId();
                teacherRecordAudit($connection, (int) $user['user_id'], 'Create assessment', 'Teacher assessments', "Created assessment {$assessmentId} for assignment {$assignmentId}.");
                $connection->commit();
                $_SESSION['teacher_flash'] = 'Assessment created.';
                header('Location: teacher-section.php?section=assessments', true, 303);
                exit;
            }
            $errorMessage = 'Please correct the highlighted assessment fields.';
        } elseif ($action === 'score') {
            $assessmentId = teacherPostInt('assessment_id');
            $studentId = teacherPostInt('student_id');
            $score = teacherPostString('score');
            $remarks = teacherPostString('remarks');

            if ($assessmentId === null) {
                $fieldErrors['assessment_id'] = 'Choose an assessment.';
            }
            if ($studentId === null) {
                $fieldErrors['student_id'] = 'Choose a student.';
            }
            if (!is_numeric($score) || (float) $score < 0) {
                $fieldErrors['score'] = 'Enter a score of zero or greater.';
            }

            if ($fieldErrors === []) {
                $scoreScope = $connection->prepare(
                    "SELECT a.total_points, a.assignment_id, a.grading_period_id, a.assessment_type,
                          e.enrollment_id, s.user_id, gp.academic_year_id, gp.semester_id
                     FROM assessments a
                     INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
                     INNER JOIN sections sec ON sec.section_id = ta.section_id
                     INNER JOIN enrollments e ON e.section_id = sec.section_id
                       AND e.academic_year_id = sec.academic_year_id
                       AND e.enrollment_status = 'Enrolled'
                     INNER JOIN students s ON s.student_id = e.student_id
                     INNER JOIN grading_periods gp ON gp.grading_period_id = a.grading_period_id
                       AND gp.academic_year_id = sec.academic_year_id
                       AND (gp.semester_id IS NULL OR gp.semester_id = ta.semester_id)
                     WHERE a.assessment_id = :assessment_id
                       AND ta.teacher_id = :teacher_id
                       AND e.student_id = :student_id
                       AND a.status IN ('Published', 'Completed')
                     LIMIT 1"
                );
                $scoreScope->execute([
                    'assessment_id' => $assessmentId,
                    'teacher_id' => $teacherId,
                    'student_id' => $studentId,
                ]);
                $scoreContext = $scoreScope->fetch();
                if ($scoreContext === false) {
                    $fieldErrors['student_id'] = 'Choose a student enrolled in the selected assessment’s class.';
                } elseif ((float) $score > (float) $scoreContext['total_points']) {
                    $fieldErrors['score'] = 'The score cannot exceed the assessment total.';
                }
            }

            if ($fieldErrors === []) {
                $connection->beginTransaction();
                $saveScore = $connection->prepare(
                    'INSERT INTO student_scores (assessment_id, student_id, score, remarks, recorded_by)
                     VALUES (:assessment_id, :student_id, :score, :remarks, :recorded_by)
                     ON DUPLICATE KEY UPDATE
                        score = VALUES(score),
                        remarks = VALUES(remarks),
                        recorded_by = VALUES(recorded_by),
                        recorded_at = CURRENT_TIMESTAMP'
                );
                $saveScore->execute([
                    'assessment_id' => $assessmentId,
                    'student_id' => $studentId,
                    'score' => (float) $score,
                    'remarks' => $remarks === '' ? null : $remarks,
                    'recorded_by' => (int) $user['user_id'],
                ]);
                $gradeDraft = $_SESSION['teacher_grade_draft'] ?? null;
                if (
                    is_array($gradeDraft)
                    && is_array($gradeDraft['input'] ?? null)
                    && (int) ($gradeDraft['user_id'] ?? 0) === (int) $user['user_id']
                    && (int) ($gradeDraft['created_at'] ?? 0) >= time() - 1800
                    && (int) ($gradeDraft['input']['assignment_id'] ?? 0) === (int) $scoreContext['assignment_id']
                    && (int) ($gradeDraft['input']['enrollment_id'] ?? 0) === (int) $scoreContext['enrollment_id']
                    && (int) ($gradeDraft['input']['grading_period_id'] ?? 0) === (int) $scoreContext['grading_period_id']
                    && in_array($scoreContext['assessment_type'], ['Exam', 'Quiz'], true)
                ) {
                    $_SESSION['teacher_grade_draft_resume'] = $gradeDraft;
                    unset($_SESSION['teacher_grade_draft']);
                }
                $scoreSync = null;
                if (in_array($scoreContext['assessment_type'], ['Exam', 'Quiz'], true)) {
                    $scoreSync = $recalculateSavedGrades(
                        $connection,
                        (int) $scoreContext['enrollment_id'],
                        (int) $scoreContext['assignment_id']
                    );
                    if ($scoreSync['has_final_grade_row'] && $scoreSync['final_grade_changed']) {
                        $linkedStudentUserId = $scoreContext['user_id'] === null
                            ? null
                            : (int) $scoreContext['user_id'];
                        if ($linkedStudentUserId !== null && $linkedStudentUserId > 0) {
                            $gradeNotificationKey = 'grade-final-' . $scoreContext['enrollment_id'] . '-'
                                . $scoreContext['assignment_id'] . '-' . $scoreContext['academic_year_id']
                                . '-' . ($scoreContext['semester_id'] ?? 'annual');
                            $deactivateGradeNotification = $connection->prepare(
                                'UPDATE student_notifications
                                 SET is_active = 0
                                 WHERE user_id = :user_id AND notification_key = :notification_key'
                            );
                            $deactivateGradeNotification->execute([
                                'user_id' => $linkedStudentUserId,
                                'notification_key' => $gradeNotificationKey,
                            ]);
                        }
                    }
                }
                teacherRecordAudit($connection, (int) $user['user_id'], 'Save assessment score', 'Teacher grades', "Saved a score for student {$studentId} on assessment {$assessmentId}.");
                $connection->commit();
                $_SESSION['teacher_flash'] = $scoreSync !== null && $scoreSync['updated_count'] > 0
                    ? 'Assessment score saved; affected term grades were recalculated and set to Pending for review.'
                    : 'Assessment score saved.';
                header('Location: teacher-section.php?section=grades', true, 303);
                exit;
            }
            $errorMessage = 'Please correct the highlighted score fields.';
        } elseif ($action === 'term-grade') {
            $assignmentId = teacherPostInt('assignment_id');
            $enrollmentId = teacherPostInt('enrollment_id');
            $periodId = teacherPostInt('grading_period_id');
            $componentValues = [
                'component_attendance' => teacherPostString('component_attendance'),
                'component_recitation' => teacherPostString('component_recitation'),
            ];
            $assessmentComponents = [
                'component_exam' => null,
                'component_quizzes' => null,
            ];
            $remarks = teacherPostString('remarks');

            foreach ([
                'assignment_id' => $assignmentId,
                'enrollment_id' => $enrollmentId,
                'grading_period_id' => $periodId,
            ] as $name => $value) {
                if ($value === null) {
                    $fieldErrors[$name] = 'Choose an available option.';
                }
            }
            foreach ($componentValues as $name => $componentValue) {
                if ($componentValue === '' || !is_numeric($componentValue) || (float) $componentValue < 0 || (float) $componentValue > 100) {
                    $fieldErrors[$name] = 'Enter a percentage from 0 to 100.';
                }
            }
            if (!in_array($remarks, ['Passed', 'Failed', 'Incomplete', 'Dropped', 'Pending'], true)) {
                $fieldErrors['remarks'] = 'Choose an available grade result.';
            }

            $gradeStudent = false;
            $gradeStage = null;
            $previousStageGrade = null;
            $calculatedGrade = null;
            if ($fieldErrors === []) {
                $gradeScope = $connection->prepare(
                    "SELECT e.student_id, s.user_id, su.subject_code, gp.period_name,
                            gp.academic_year_id, gp.semester_id, gp.start_date
                     FROM teaching_assignments ta
                     INNER JOIN sections sec ON sec.section_id = ta.section_id
                     INNER JOIN enrollments e ON e.enrollment_id = :enrollment_id
                       AND e.section_id = sec.section_id
                       AND e.academic_year_id = sec.academic_year_id
                       AND e.enrollment_status = 'Enrolled'
                     INNER JOIN students s ON s.student_id = e.student_id
                     INNER JOIN subjects su ON su.subject_id = ta.subject_id
                     INNER JOIN grading_periods gp ON gp.grading_period_id = :period_id
                       AND gp.academic_year_id = sec.academic_year_id
                       AND (gp.semester_id IS NULL OR gp.semester_id = ta.semester_id)
                     WHERE ta.assignment_id = :assignment_id
                       AND ta.teacher_id = :teacher_id
                     LIMIT 1"
                );
                $gradeScope->execute([
                    'enrollment_id' => $enrollmentId,
                    'period_id' => $periodId,
                    'assignment_id' => $assignmentId,
                    'teacher_id' => $teacherId,
                ]);
                $gradeStudent = $gradeScope->fetch();
                if ($gradeStudent === false) {
                    $fieldErrors['enrollment_id'] = 'Choose a student and grading period for one of your assigned classes.';
                } else {
                    $assessmentComponents = $getAssessmentComponentScores(
                        $connection,
                        (int) $assignmentId,
                        (int) $enrollmentId,
                        (int) $periodId
                    );
                    foreach ([
                        'component_exam' => 'Exam',
                        'component_quizzes' => 'Quiz',
                    ] as $componentKey => $assessmentLabel) {
                        if ($assessmentComponents[$componentKey] === null) {
                            $fieldErrors[$componentKey] = "Record at least one scored {$assessmentLabel} assessment for this grading period.";
                        }
                    }
                    $gradeStage = match (strtolower(trim((string) $gradeStudent['period_name']))) {
                        'prelim', 'preliminary' => 'prelim',
                        'midterm' => 'midterm',
                        'final' => 'final',
                        default => null,
                    };
                    if ($gradeStage === null) {
                        $fieldErrors['grading_period_id'] = 'Choose a Prelim, Midterm, or Final grading period.';
                    } elseif ($gradeStage !== 'prelim') {
                        $previousPeriodName = $gradeStage === 'midterm' ? 'Prelim' : 'Midterm';
                        $previousGradeColumn = $gradeStage === 'midterm' ? 'preliminary_grade' : 'midterm_grade';
                        $alternatePreviousPeriodName = $gradeStage === 'midterm' ? 'preliminary' : strtolower($previousPeriodName);
                        $previousGradeStatement = $connection->prepare(
                            "SELECT previous_grade.{$previousGradeColumn}
                             FROM grade_records previous_grade
                             INNER JOIN grading_periods previous_period
                               ON previous_period.grading_period_id = previous_grade.grading_period_id
                             WHERE previous_grade.enrollment_id = :enrollment_id
                               AND previous_grade.assignment_id = :assignment_id
                               AND previous_period.academic_year_id = :academic_year_id
                               AND previous_period.semester_id <=> :semester_id
                               AND LOWER(previous_period.period_name) IN (:period_name, :alternate_period_name)
                             ORDER BY previous_period.start_date DESC, previous_grade.grade_id DESC
                             LIMIT 1"
                        );
                        $previousGradeStatement->execute([
                            'enrollment_id' => $enrollmentId,
                            'assignment_id' => $assignmentId,
                            'academic_year_id' => (int) $gradeStudent['academic_year_id'],
                            'semester_id' => $gradeStudent['semester_id'] === null
                                ? null
                                : (int) $gradeStudent['semester_id'],
                            'period_name' => strtolower($previousPeriodName),
                            'alternate_period_name' => $alternatePreviousPeriodName,
                        ]);
                        $previousGradeValue = $previousGradeStatement->fetchColumn();
                        if ($previousGradeValue === false || $previousGradeValue === null) {
                            $fieldErrors['grading_period_id'] = "Save the {$previousPeriodName} grade before recording the {$gradeStudent['period_name']} grade.";
                        } else {
                            $previousStageGrade = (float) $previousGradeValue;
                        }
                    }

                    if ($fieldErrors === []) {
                        $tentativeGrade = round(
                            (float) $assessmentComponents['component_exam'] * 0.50
                            + (float) $assessmentComponents['component_quizzes'] * 0.25
                            + (float) $componentValues['component_attendance'] * 0.10
                            + (float) $componentValues['component_recitation'] * 0.15,
                            1
                        );
                        $calculatedGrade = match ($gradeStage) {
                            'prelim' => $tentativeGrade,
                            'midterm', 'final' => round($previousStageGrade * 0.25 + $tentativeGrade * 0.75, 1),
                            default => null,
                        };
                    }
                }
                if (
                    $gradeStudent !== false
                    && (isset($fieldErrors['component_exam']) || isset($fieldErrors['component_quizzes']))
                ) {
                    $_SESSION['teacher_grade_draft'] = [
                        'user_id' => (int) $user['user_id'],
                        'created_at' => time(),
                        'input' => [
                            'assignment_id' => (string) $assignmentId,
                            'enrollment_id' => (string) $enrollmentId,
                            'grading_period_id' => (string) $periodId,
                            'component_attendance' => $componentValues['component_attendance'],
                            'component_recitation' => $componentValues['component_recitation'],
                            'remarks' => $remarks,
                        ],
                    ];
                }
            }

            if ($fieldErrors === []) {
                $connection->beginTransaction();
                $saveGrade = $connection->prepare(
                    'INSERT INTO grade_records
                        (enrollment_id, assignment_id, grading_period_id, preliminary_grade, midterm_grade, final_grade,
                         component_exam, component_quizzes, component_attendance, component_recitation, remarks, encoded_by)
                     VALUES
                        (:enrollment_id, :assignment_id, :grading_period_id, :preliminary_grade, :midterm_grade, :final_grade,
                         :component_exam, :component_quizzes, :component_attendance, :component_recitation, :remarks, :encoded_by)
                     ON DUPLICATE KEY UPDATE
                        preliminary_grade = VALUES(preliminary_grade),
                        midterm_grade = VALUES(midterm_grade),
                        final_grade = VALUES(final_grade),
                        component_exam = VALUES(component_exam),
                        component_quizzes = VALUES(component_quizzes),
                        component_attendance = VALUES(component_attendance),
                        component_recitation = VALUES(component_recitation),
                        remarks = VALUES(remarks),
                        encoded_by = VALUES(encoded_by),
                        date_encoded = CURRENT_TIMESTAMP'
                );
                $saveGrade->execute([
                    'enrollment_id' => $enrollmentId,
                    'assignment_id' => $assignmentId,
                    'grading_period_id' => $periodId,
                    'preliminary_grade' => $gradeStage === 'prelim' ? $calculatedGrade : null,
                    'midterm_grade' => $gradeStage === 'midterm' ? $calculatedGrade : null,
                    'final_grade' => $gradeStage === 'final' ? $calculatedGrade : null,
                    'component_exam' => (float) $assessmentComponents['component_exam'],
                    'component_quizzes' => (float) $assessmentComponents['component_quizzes'],
                    'component_attendance' => (float) $componentValues['component_attendance'],
                    'component_recitation' => (float) $componentValues['component_recitation'],
                    'remarks' => $remarks,
                    'encoded_by' => (int) $user['user_id'],
                ]);
                unset($_SESSION['teacher_grade_draft'], $_SESSION['teacher_grade_draft_resume']);
                $gradeSync = $recalculateSavedGrades($connection, $enrollmentId, $assignmentId, $periodId);
                $downstreamPeriodsUpdated = $gradeSync['updated_count'];
                $downstreamPeriodsInvalidated = $gradeSync['invalidated_count'];
                teacherRecordAudit(
                    $connection,
                    (int) $user['user_id'],
                    'Save term grade',
                    'Teacher grades',
                    "Saved the {$gradeStudent['period_name']} grade for student {$gradeStudent['student_id']} in assignment {$assignmentId}."
                );

                $notificationSent = false;
                $linkedStudentUserId = $gradeStudent['user_id'] === null
                    ? null
                    : (int) $gradeStudent['user_id'];
                $gradeNotificationKey = 'grade-final-' . $enrollmentId . '-' . $assignmentId . '-'
                    . $gradeStudent['academic_year_id'] . '-' . ($gradeStudent['semester_id'] ?? 'annual');
                if ($gradeStage === 'final' && $linkedStudentUserId !== null && $linkedStudentUserId > 0) {
                    $gradeNotification = $connection->prepare(
                        'INSERT INTO student_notifications
                            (user_id, notification_key, notification_type, title, message)
                         VALUES
                            (:user_id, :notification_key, \'Grade posted\', :title, :message)
                         ON DUPLICATE KEY UPDATE
                            notification_type = VALUES(notification_type),
                            title = VALUES(title),
                            message = VALUES(message),
                            is_read = 0,
                            is_active = 1,
                            created_at = CURRENT_TIMESTAMP,
                            read_at = NULL'
                    );
                    $gradeNotification->execute([
                        'user_id' => $linkedStudentUserId,
                        'notification_key' => $gradeNotificationKey,
                        'title' => 'Overall grade posted',
                        'message' => "{$gradeStudent['subject_code']} Final grade: " . number_format((float) $calculatedGrade, 1) . '%. View your grades in My Grades.',
                    ]);
                    $notificationSent = true;
                } elseif (
                    $linkedStudentUserId !== null
                    && $linkedStudentUserId > 0
                    && $gradeSync['has_final_grade_row']
                    && $gradeSync['final_grade_changed']
                ) {
                    $deactivateGradeNotification = $connection->prepare(
                        'UPDATE student_notifications
                         SET is_active = 0
                         WHERE user_id = :user_id AND notification_key = :notification_key'
                    );
                    $deactivateGradeNotification->execute([
                        'user_id' => $linkedStudentUserId,
                        'notification_key' => $gradeNotificationKey,
                    ]);
                }

                $connection->commit();
                $_SESSION['teacher_flash'] = $gradeStage === 'final'
                    ? ($notificationSent
                        ? 'Overall Final grade saved and a student portal notification was sent.'
                        : 'Overall Final grade saved. No student portal account is linked, so a notification could not be sent.')
                    : "{$gradeStudent['period_name']} grade calculated and saved."
                        . ($downstreamPeriodsUpdated > 0
                            ? " {$downstreamPeriodsUpdated} saved grade(s) were recalculated; review their result statuses."
                            : '')
                        . ($downstreamPeriodsInvalidated > 0
                            ? " {$downstreamPeriodsInvalidated} grade(s) were invalidated because their components or a required earlier period are incomplete."
                            : '');
                header('Location: teacher-section.php?section=grades', true, 303);
                exit;
            }
            $errorMessage = 'Please correct the highlighted fields.';
        } else {
            $errorMessage = 'Choose a supported action for this Teacher page.';
        }
    }
}

$assignments = [];
$enrollmentOptions = [];
$studentOptions = [];
$attendanceAssignments = [];
$attendanceRosters = [];
$attendanceSchedules = [];
$gradingPeriods = [];
$gradeRows = [];
$gradeHistory = [];
$gradeAssessmentScores = [];
$assessmentOptions = [];
$categories = [];
$tableDefinitions = [];

if ($teacherId !== null) {
    $assignmentStatement = $connection->prepare(
        "SELECT ta.assignment_id AS id,
                CONCAT(su.subject_code, ' - ', su.subject_name, ' / ', sec.section_name, ' / ', sem.semester_name) AS label
         FROM teaching_assignments ta
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN semesters sem ON sem.semester_id = ta.semester_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY su.subject_name, sec.section_name"
    );
    $assignmentStatement->execute(['teacher_id' => $teacherId]);
    $assignments = $assignmentStatement->fetchAll();

    if ($sectionKey === 'attendance') {
        $attendanceAssignmentStatement = $connection->prepare(
            "SELECT DISTINCT ta.assignment_id AS id,
                    CONCAT(su.subject_code, ' - ', su.subject_name, ' / ', sec.section_name) AS label
             FROM teaching_assignments ta
             INNER JOIN subjects su ON su.subject_id = ta.subject_id
             INNER JOIN sections sec ON sec.section_id = ta.section_id
             INNER JOIN class_schedules cs ON cs.assignment_id = ta.assignment_id
               AND cs.schedule_status = 'Active'
             WHERE ta.teacher_id = :teacher_id
             ORDER BY sec.section_name, su.subject_name"
        );
        $attendanceAssignmentStatement->execute(['teacher_id' => $teacherId]);
        $attendanceAssignments = $attendanceAssignmentStatement->fetchAll();

        $attendanceRosterStatement = $connection->prepare(
            "SELECT ta.assignment_id,
                    e.enrollment_id,
                    s.student_number,
                    CONCAT(s.first_name, ' ', s.last_name) AS student_name
             FROM teaching_assignments ta
             INNER JOIN sections sec ON sec.section_id = ta.section_id
             INNER JOIN enrollments e ON e.section_id = sec.section_id
               AND e.academic_year_id = sec.academic_year_id
               AND e.enrollment_status = 'Enrolled'
             INNER JOIN students s ON s.student_id = e.student_id
             INNER JOIN class_schedules cs ON cs.assignment_id = ta.assignment_id
               AND cs.schedule_status = 'Active'
             WHERE ta.teacher_id = :teacher_id
             GROUP BY ta.assignment_id, e.enrollment_id, s.student_number, s.first_name, s.last_name
             ORDER BY sec.section_name, s.last_name, s.first_name, s.student_number"
        );
        $attendanceRosterStatement->execute(['teacher_id' => $teacherId]);
        foreach ($attendanceRosterStatement->fetchAll() as $attendanceRoster) {
            $attendanceRosters[(int) $attendanceRoster['assignment_id']][] = $attendanceRoster;
        }

        $attendanceScheduleStatement = $connection->prepare(
            "SELECT cs.assignment_id,
                    cs.day_of_week,
                    MIN(cs.start_time) AS time_in,
                    MAX(cs.end_time) AS time_out
             FROM class_schedules cs
             INNER JOIN teaching_assignments ta ON ta.assignment_id = cs.assignment_id
             WHERE ta.teacher_id = :teacher_id
               AND cs.schedule_status = 'Active'
             GROUP BY cs.assignment_id, cs.day_of_week"
        );
        $attendanceScheduleStatement->execute(['teacher_id' => $teacherId]);
        foreach ($attendanceScheduleStatement->fetchAll() as $schedule) {
            $attendanceSchedules[(int) $schedule['assignment_id']][$schedule['day_of_week']] = [
                'time_in' => substr((string) $schedule['time_in'], 0, 5),
                'time_out' => substr((string) $schedule['time_out'], 0, 5),
            ];
        }
    }

    $enrollmentStatement = $connection->prepare(
        "SELECT DISTINCT e.enrollment_id AS id, ta.assignment_id,
                CONCAT(s.student_number, ' - ', s.first_name, ' ', s.last_name, ' / ', sec.section_name) AS label
         FROM teaching_assignments ta
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN enrollments e ON e.section_id = sec.section_id
           AND e.academic_year_id = sec.academic_year_id
           AND e.enrollment_status = 'Enrolled'
         INNER JOIN students s ON s.student_id = e.student_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY label"
    );
    $enrollmentStatement->execute(['teacher_id' => $teacherId]);
    $enrollmentOptions = $enrollmentStatement->fetchAll();

    $studentStatement = $connection->prepare(
        "SELECT DISTINCT s.student_id AS id, ta.assignment_id,
                CONCAT(s.student_number, ' - ', s.first_name, ' ', s.last_name) AS label
         FROM teaching_assignments ta
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN enrollments e ON e.section_id = sec.section_id
           AND e.academic_year_id = sec.academic_year_id
           AND e.enrollment_status = 'Enrolled'
         INNER JOIN students s ON s.student_id = e.student_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY s.last_name, s.first_name"
    );
    $studentStatement->execute(['teacher_id' => $teacherId]);
    $studentOptions = $studentStatement->fetchAll();

    $periodStatement = $connection->prepare(
        'SELECT DISTINCT gp.grading_period_id AS id, gp.period_name,
                gp.academic_year_id, gp.semester_id, gp.start_date,
                CONCAT(ay.year_name, " - ", gp.period_name) AS label
         FROM teaching_assignments ta
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN academic_years ay ON ay.academic_year_id = sec.academic_year_id
         INNER JOIN grading_periods gp ON gp.academic_year_id = sec.academic_year_id
           AND (gp.semester_id IS NULL OR gp.semester_id = ta.semester_id)
         WHERE ta.teacher_id = :teacher_id
         ORDER BY gp.start_date DESC'
    );
    $periodStatement->execute(['teacher_id' => $teacherId]);
    $gradingPeriods = $periodStatement->fetchAll();

    $categoryStatement = $connection->query(
        'SELECT category_id AS id, category_name AS label FROM assessment_categories ORDER BY category_name'
    );
    $categories = $categoryStatement->fetchAll();

    $assessmentOptionStatement = $connection->prepare(
        "SELECT a.assessment_id AS id, ta.assignment_id,
                CONCAT(a.assessment_title, ' / ', su.subject_code, ' / ', sec.section_name) AS label
         FROM assessments a
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         WHERE ta.teacher_id = :teacher_id
           AND a.status IN ('Published', 'Completed')
         ORDER BY a.assessment_date DESC, a.assessment_title"
    );
    $assessmentOptionStatement->execute(['teacher_id' => $teacherId]);
    $assessmentOptions = $assessmentOptionStatement->fetchAll();

    $classStatement = $connection->prepare(
        "SELECT su.subject_code, su.subject_name, sec.section_name, sec.year_level,
                ay.year_name, sem.semester_name, p.program_name,
                COUNT(DISTINCT e.enrollment_id) AS enrolled_students
         FROM teaching_assignments ta
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN academic_years ay ON ay.academic_year_id = sec.academic_year_id
         INNER JOIN semesters sem ON sem.semester_id = ta.semester_id
         LEFT JOIN programs p ON p.program_id = sec.program_id
         LEFT JOIN enrollments e ON e.section_id = sec.section_id AND e.enrollment_status = 'Enrolled'
         WHERE ta.teacher_id = :teacher_id
         GROUP BY ta.assignment_id, su.subject_code, su.subject_name, sec.section_name, sec.year_level, ay.year_name, sem.semester_name, p.program_name
         ORDER BY su.subject_name, sec.section_name"
    );
    $classStatement->execute(['teacher_id' => $teacherId]);
    $classRows = $classStatement->fetchAll();

    $studentRosterStatement = $connection->prepare(
        "SELECT DISTINCT s.student_number,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                sec.section_name, p.program_name, sec.year_level, e.enrollment_status
         FROM teaching_assignments ta
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN enrollments e ON e.section_id = sec.section_id AND e.enrollment_status = 'Enrolled'
         INNER JOIN students s ON s.student_id = e.student_id
         LEFT JOIN programs p ON p.program_id = sec.program_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY sec.section_name, s.last_name, s.first_name"
    );
    $studentRosterStatement->execute(['teacher_id' => $teacherId]);
    $studentRows = $studentRosterStatement->fetchAll();

    $scheduleStatement = $connection->prepare(
        "SELECT cs.day_of_week, cs.start_time, cs.end_time, cs.schedule_status,
                su.subject_code, su.subject_name, sec.section_name,
                CONCAT(r.room_code, ' - ', r.room_name) AS room
         FROM class_schedules cs
         INNER JOIN teaching_assignments ta ON ta.assignment_id = cs.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN rooms r ON r.room_id = cs.room_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), cs.start_time"
    );
    $scheduleStatement->execute(['teacher_id' => $teacherId]);
    $scheduleRows = $scheduleStatement->fetchAll();

    $attendanceStatement = $connection->prepare(
        "SELECT a.attendance_date, s.student_number,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                su.subject_code, sec.section_name, a.attendance_status, a.time_in, a.time_out, a.remarks
         FROM attendance a
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN students s ON s.student_id = a.student_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY a.attendance_date DESC, s.last_name, s.first_name
         LIMIT 100"
    );
    $attendanceStatement->execute(['teacher_id' => $teacherId]);
    $attendanceRows = $attendanceStatement->fetchAll();

    $assessmentStatement = $connection->prepare(
        "SELECT a.assessment_title, a.assessment_type, a.total_points, a.assessment_date,
                a.due_date, a.status, su.subject_code, sec.section_name,
                gp.period_name, ac.category_name
         FROM assessments a
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN grading_periods gp ON gp.grading_period_id = a.grading_period_id
         INNER JOIN assessment_categories ac ON ac.category_id = a.category_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY a.created_at DESC
         LIMIT 100"
    );
    $assessmentStatement->execute(['teacher_id' => $teacherId]);
    $assessmentRows = $assessmentStatement->fetchAll();

    $scoreStatement = $connection->prepare(
        "SELECT a.assessment_title, s.student_number,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                sec.section_name, ss.score, a.total_points, ss.remarks, ss.recorded_at
         FROM student_scores ss
         INNER JOIN assessments a ON a.assessment_id = ss.assessment_id
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN students s ON s.student_id = ss.student_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY ss.recorded_at DESC
         LIMIT 100"
    );
    $scoreStatement->execute(['teacher_id' => $teacherId]);
    $scoreRows = $scoreStatement->fetchAll();

    $gradeStatement = $connection->prepare(
        "SELECT s.student_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                su.subject_code, sec.section_name, gp.period_name,
                gr.preliminary_grade, gr.midterm_grade, gr.final_grade, gr.remarks
         FROM grade_records gr
         INNER JOIN teaching_assignments ta ON ta.assignment_id = gr.assignment_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN enrollments e ON e.enrollment_id = gr.enrollment_id
         INNER JOIN students s ON s.student_id = e.student_id
         INNER JOIN grading_periods gp ON gp.grading_period_id = gr.grading_period_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY gp.start_date DESC, s.last_name, s.first_name
         LIMIT 100"
    );
    $gradeStatement->execute(['teacher_id' => $teacherId]);
    $gradeRows = $gradeStatement->fetchAll();
    $gradeHistoryStatement = $connection->prepare(
        "SELECT gr.grade_id, gr.enrollment_id, gr.assignment_id, gr.grading_period_id,
                gp.period_name, gp.academic_year_id, gp.semester_id, gp.start_date,
                gr.preliminary_grade, gr.midterm_grade, gr.final_grade,
                gr.component_exam, gr.component_quizzes,
                gr.component_attendance, gr.component_recitation
         FROM grade_records gr
         INNER JOIN teaching_assignments ta ON ta.assignment_id = gr.assignment_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN enrollments e ON e.enrollment_id = gr.enrollment_id
           AND e.section_id = sec.section_id
           AND e.academic_year_id = sec.academic_year_id
           AND e.enrollment_status = 'Enrolled'
         INNER JOIN grading_periods gp ON gp.grading_period_id = gr.grading_period_id
           AND gp.academic_year_id = sec.academic_year_id
           AND (gp.semester_id IS NULL OR gp.semester_id = ta.semester_id)
         WHERE ta.teacher_id = :teacher_id
         ORDER BY gp.start_date DESC, gr.grade_id DESC"
    );
    $gradeHistoryStatement->execute(['teacher_id' => $teacherId]);
    $gradeHistoryRows = $gradeHistoryStatement->fetchAll();
    foreach ($gradeHistoryRows as $gradeRow) {
        $gradeHistory[(int) $gradeRow['assignment_id']][(int) $gradeRow['enrollment_id']][] = [
            'grade_id' => (int) $gradeRow['grade_id'],
            'grading_period_id' => (int) $gradeRow['grading_period_id'],
            'period_name' => (string) $gradeRow['period_name'],
            'academic_year_id' => (int) $gradeRow['academic_year_id'],
            'semester_id' => $gradeRow['semester_id'] === null ? null : (int) $gradeRow['semester_id'],
            'start_date' => (string) $gradeRow['start_date'],
            'preliminary_grade' => $gradeRow['preliminary_grade'] === null ? null : (float) $gradeRow['preliminary_grade'],
            'midterm_grade' => $gradeRow['midterm_grade'] === null ? null : (float) $gradeRow['midterm_grade'],
            'final_grade' => $gradeRow['final_grade'] === null ? null : (float) $gradeRow['final_grade'],
            'components' => [
                'component_exam' => $gradeRow['component_exam'] === null ? null : (float) $gradeRow['component_exam'],
                'component_quizzes' => $gradeRow['component_quizzes'] === null ? null : (float) $gradeRow['component_quizzes'],
                'component_attendance' => $gradeRow['component_attendance'] === null ? null : (float) $gradeRow['component_attendance'],
                'component_recitation' => $gradeRow['component_recitation'] === null ? null : (float) $gradeRow['component_recitation'],
            ],
        ];
    }

    $gradeAssessmentScoreStatement = $connection->prepare(
        "SELECT ta.assignment_id, e.enrollment_id, a.grading_period_id,
                a.assessment_type, COUNT(*) AS assessment_count,
                SUM(ss.score) AS points_earned, SUM(a.total_points) AS points_possible
         FROM student_scores ss
         INNER JOIN assessments a ON a.assessment_id = ss.assessment_id
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN enrollments e ON e.student_id = ss.student_id
           AND e.section_id = sec.section_id
           AND e.academic_year_id = sec.academic_year_id
           AND e.enrollment_status = 'Enrolled'
         INNER JOIN grading_periods gp ON gp.grading_period_id = a.grading_period_id
           AND gp.academic_year_id = sec.academic_year_id
           AND (gp.semester_id IS NULL OR gp.semester_id = ta.semester_id)
         WHERE ta.teacher_id = :teacher_id
           AND a.assessment_type IN ('Exam', 'Quiz')
           AND a.status IN ('Published', 'Completed')
         GROUP BY ta.assignment_id, e.enrollment_id, a.grading_period_id, a.assessment_type"
    );
    $gradeAssessmentScoreStatement->execute(['teacher_id' => $teacherId]);
    foreach ($gradeAssessmentScoreStatement->fetchAll() as $assessmentScoreRow) {
        $type = $assessmentScoreRow['assessment_type'] === 'Exam' ? 'exam' : 'quiz';
        $pointsPossible = (float) $assessmentScoreRow['points_possible'];
        if ($pointsPossible <= 0) {
            continue;
        }
        $componentKey = $type === 'exam' ? 'component_exam' : 'component_quizzes';
        $gradeAssessmentScores[(int) $assessmentScoreRow['assignment_id']]
            [(int) $assessmentScoreRow['enrollment_id']]
            [(int) $assessmentScoreRow['grading_period_id']]
            [$componentKey] = [
                'percentage' => round((float) $assessmentScoreRow['points_earned'] / $pointsPossible * 100, 1),
                'assessment_count' => (int) $assessmentScoreRow['assessment_count'],
                'points_earned' => (float) $assessmentScoreRow['points_earned'],
                'points_possible' => $pointsPossible,
            ];
    }

    $reportCardStatement = $connection->prepare(
        'SELECT DISTINCT ay.year_name, gp.period_name, s.student_number,
                CONCAT(s.first_name, " ", s.last_name) AS student_name,
                sec.section_name, rc.general_average, rc.attendance_summary,
                rc.teacher_remarks, rc.status, rc.generated_at
         FROM report_cards rc
         INNER JOIN enrollments e ON e.enrollment_id = rc.enrollment_id
         INNER JOIN teaching_assignments ta ON ta.section_id = e.section_id
         INNER JOIN students s ON s.student_id = e.student_id
         INNER JOIN sections sec ON sec.section_id = e.section_id
         INNER JOIN grading_periods gp ON gp.grading_period_id = rc.grading_period_id
         LEFT JOIN academic_years ay ON ay.academic_year_id = gp.academic_year_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY rc.generated_at DESC, student_name
         LIMIT 100'
    );
    $reportCardStatement->execute(['teacher_id' => $teacherId]);
    $reportCardRows = $reportCardStatement->fetchAll();

    $performanceStatement = $connection->prepare(
        "SELECT s.student_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                su.subject_code, sec.section_name,
                COALESCE(score_summary.assessment_count, 0) AS assessment_count,
                score_summary.average_percent,
                COALESCE(attendance_summary.attendance_count, 0) AS attendance_count,
                (
                    SELECT gr.final_grade
                    FROM grade_records gr
                    INNER JOIN grading_periods gp ON gp.grading_period_id = gr.grading_period_id
                    WHERE gr.enrollment_id = e.enrollment_id
                      AND gr.assignment_id = ta.assignment_id
                    ORDER BY gp.end_date DESC, gr.grade_id DESC
                    LIMIT 1
                ) AS final_grade,
                (
                    SELECT gr.remarks
                    FROM grade_records gr
                    INNER JOIN grading_periods gp ON gp.grading_period_id = gr.grading_period_id
                    WHERE gr.enrollment_id = e.enrollment_id
                      AND gr.assignment_id = ta.assignment_id
                    ORDER BY gp.end_date DESC, gr.grade_id DESC
                    LIMIT 1
                ) AS grade_result
         FROM teaching_assignments ta
         INNER JOIN sections sec ON sec.section_id = ta.section_id
         INNER JOIN enrollments e ON e.section_id = sec.section_id AND e.enrollment_status = 'Enrolled'
         INNER JOIN students s ON s.student_id = e.student_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         LEFT JOIN (
            SELECT a.assignment_id, ss.student_id,
                   COUNT(*) AS assessment_count,
                   ROUND(AVG(ss.score / NULLIF(a.total_points, 0) * 100), 2) AS average_percent
            FROM assessments a
            INNER JOIN student_scores ss ON ss.assessment_id = a.assessment_id
            WHERE a.status IN ('Published', 'Completed')
            GROUP BY a.assignment_id, ss.student_id
         ) score_summary ON score_summary.assignment_id = ta.assignment_id AND score_summary.student_id = s.student_id
         LEFT JOIN (
            SELECT assignment_id, student_id, COUNT(*) AS attendance_count
            FROM attendance
            GROUP BY assignment_id, student_id
         ) attendance_summary ON attendance_summary.assignment_id = ta.assignment_id AND attendance_summary.student_id = s.student_id
         WHERE ta.teacher_id = :teacher_id
         ORDER BY sec.section_name, s.last_name, s.first_name"
    );
    $performanceStatement->execute(['teacher_id' => $teacherId]);
    $performanceRows = $performanceStatement->fetchAll();

    $tableDefinitions = [
        'classes' => [[
            'title' => 'Assigned classes',
            'caption' => 'Teaching assignments connected to your account',
            'columns' => ['Code', 'Subject', 'Class section', 'Year level', 'Program', 'Academic year', 'Semester', 'Enrolled students'],
            'rows' => array_map(static fn (array $row): array => [
                $row['subject_code'], $row['subject_name'], $row['section_name'], $row['year_level'],
                $row['program_name'] ?? '—', $row['year_name'], $row['semester_name'], $row['enrolled_students'],
            ], $classRows),
            'empty' => 'No teaching assignments are linked to your account yet.',
        ]],
        'students' => [[
            'title' => 'Enrolled students',
            'caption' => 'Students in your assigned class sections',
            'columns' => ['Student number', 'Student', 'Class section', 'Program', 'Year level', 'Status'],
            'rows' => array_map(static fn (array $row): array => [
                $row['student_number'], $row['student_name'], $row['section_name'],
                $row['program_name'] ?? '—', $row['year_level'], $row['enrollment_status'],
            ], $studentRows),
            'empty' => 'No currently enrolled students were found in your assigned classes.',
        ]],
        'schedule' => [[
            'title' => 'Class timetable',
            'caption' => 'Schedule entries for your teaching assignments',
            'columns' => ['Day', 'Time', 'Class', 'Section', 'Room', 'Status'],
            'rows' => array_map(static fn (array $row): array => [
                $row['day_of_week'],
                substr((string) $row['start_time'], 0, 5) . '–' . substr((string) $row['end_time'], 0, 5),
                $row['subject_code'] . ' - ' . $row['subject_name'],
                $row['section_name'], $row['room'], $row['schedule_status'],
            ], $scheduleRows),
            'empty' => 'No class schedules are available for your assignments.',
        ]],
        'attendance' => [[
            'title' => 'Recent student attendance',
            'caption' => 'The latest 100 attendance entries for your classes',
            'columns' => ['Date', 'Student number', 'Student', 'Class', 'Section', 'Status', 'Time in', 'Time out', 'Remarks'],
            'rows' => array_map(static fn (array $row): array => [
                $row['attendance_date'], $row['student_number'], $row['student_name'],
                $row['subject_code'], $row['section_name'], $row['attendance_status'],
                $row['time_in'] === null ? '—' : substr((string) $row['time_in'], 0, 5),
                $row['time_out'] === null ? '—' : substr((string) $row['time_out'], 0, 5),
                $row['remarks'] ?? '—',
            ], $attendanceRows),
            'empty' => 'No attendance has been recorded for your classes.',
        ]],
        'assessments' => [[
            'title' => 'Your assessments',
            'caption' => 'Assessments attached to your teaching assignments',
            'columns' => ['Title', 'Type', 'Class', 'Section', 'Category', 'Grading period', 'Points', 'Assessment date', 'Due date', 'Status'],
            'rows' => array_map(static fn (array $row): array => [
                $row['assessment_title'], $row['assessment_type'], $row['subject_code'], $row['section_name'],
                $row['category_name'], $row['period_name'], $row['total_points'],
                $row['assessment_date'] ?? '—', $row['due_date'] ?? '—', $row['status'],
            ], $assessmentRows),
            'empty' => 'No assessments have been created for your classes.',
        ]],
        'grades' => [
            [
                'title' => 'Assessment scores',
                'caption' => 'The latest 100 scores entered for your assessments',
                'columns' => ['Assessment', 'Student number', 'Student', 'Section', 'Score', 'Points possible', 'Feedback', 'Recorded'],
                'rows' => array_map(static fn (array $row): array => [
                    $row['assessment_title'], $row['student_number'], $row['student_name'], $row['section_name'],
                    $row['score'], $row['total_points'], $row['remarks'] ?? '—', $row['recorded_at'],
                ], $scoreRows),
                'empty' => 'No assessment scores have been entered yet.',
            ],
            [
                'title' => 'Term grades',
                'caption' => 'Calculated Prelim, Midterm, and overall Final grades for your assigned classes',
                'columns' => ['Student number', 'Student', 'Class', 'Section', 'Grading period', 'Prelim', 'Midterm', 'Overall Final', 'Result'],
                'rows' => array_map(static fn (array $row): array => [
                    $row['student_number'], $row['student_name'], $row['subject_code'], $row['section_name'],
                    $row['period_name'],
                    $row['preliminary_grade'] === null ? '—' : number_format((float) $row['preliminary_grade'], 1) . '%',
                    $row['midterm_grade'] === null ? '—' : number_format((float) $row['midterm_grade'], 1) . '%',
                    $row['final_grade'] === null ? '—' : number_format((float) $row['final_grade'], 1) . '%',
                    $row['remarks'],
                ], $gradeRows),
                'empty' => 'No term grades have been recorded yet.',
            ],
        ],
        'report-cards' => [[
            'title' => 'Report cards for your classes',
            'caption' => 'The latest 100 report cards for students in your assigned sections',
            'columns' => ['Student number', 'Student', 'Section', 'Academic year', 'Grading period', 'General average', 'Attendance', 'Teacher remarks', 'Status', 'Generated'],
            'rows' => array_map(static fn (array $row): array => [
                $row['student_number'], $row['student_name'], $row['section_name'],
                $row['year_name'] ?? '—', $row['period_name'],
                $row['general_average'] ?? '—', $row['attendance_summary'] ?: '—',
                $row['teacher_remarks'] ?: '—', $row['status'], $row['generated_at'],
            ], $reportCardRows),
            'empty' => 'No report cards are available for students in your assigned classes yet.',
        ]],
        'performance' => [[
            'title' => 'Student performance overview',
            'caption' => 'Student performance overview by student and class; scores are normalized to percentages, and attendance shows recorded entries.',
            'columns' => ['Student number', 'Student', 'Class', 'Section', 'Scored assessments', 'Average score', 'Attendance entries', 'Final grade', 'Result'],
            'rows' => array_map(static fn (array $row): array => [
                $row['student_number'], $row['student_name'], $row['subject_code'], $row['section_name'],
                $row['assessment_count'],
                $row['average_percent'] === null ? '—' : $row['average_percent'] . '%',
                $row['attendance_count'], $row['final_grade'] ?? '—', $row['grade_result'] ?? '—',
            ], $performanceRows),
            'empty' => 'No enrolled students were found in your assigned classes.',
        ]],
    ];
}

$lookupOptions = [
    'assignments' => $assignments,
    'enrollments' => $enrollmentOptions,
    'attendance_assignments' => $attendanceAssignments,
    'students' => $studentOptions,
    'grading_periods' => $gradingPeriods,
    'categories' => $categories,
    'assessments' => $assessmentOptions,
];

$dashboard = [
    'theme' => 'operations',
    'roleLabel' => 'Teacher',
    'pageTitle' => $sectionDefinition['title'],
    'eyebrow' => $sectionDefinition['eyebrow'],
    'heading' => $sectionDefinition['title'],
    'description' => $sectionDefinition['description'],
    'navigationStyle' => 'teacher',
    'navItems' => teacherNavigationItems($sectionKey),
    'sidebarTitle' => 'Teacher Workspace',
    'sidebarCaption' => 'Classroom Management',
    'homeHref' => 'teacher-dashboard.php',
    'sectionContent' => true,
    'sectionContentTemplate' => 'teacher_section_content.php',
];

require dirname(__DIR__) . '/app/Shared/dashboard_layout.php';
