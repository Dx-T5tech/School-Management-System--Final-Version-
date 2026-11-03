<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';
require_once dirname(__DIR__) . '/app/Core/admin.php';
require_once dirname(__DIR__) . '/app/Core/academic.php';
require_once dirname(__DIR__) . '/app/Core/finance.php';

$user = requireDashboardUser('Admin');
$connection = getDatabaseConnection();
$sectionKey = $_GET['section'] ?? '';
$sectionDefinitions = [
    'students' => ['title' => 'Student Management', 'eyebrow' => 'Student records', 'description' => 'Maintain student profiles and review enrollment status.'],
    'teachers' => ['title' => 'Teacher Management', 'eyebrow' => 'Faculty records', 'description' => 'Maintain teacher profiles, departments, and employment status.'],
    'academic-management' => ['title' => 'Academic Management', 'eyebrow' => 'School structure', 'description' => 'Manage academic years, departments, programs, sections, and subjects. Activating an academic year opens student enrollment; only one year can be active at a time.'],
    'attendance-scheduling' => ['title' => 'Attendance & Scheduling', 'eyebrow' => 'Daily operations', 'description' => 'Record attendance and maintain class schedules.'],
    'assessment-grading' => ['title' => 'Assessment & Grading', 'eyebrow' => 'Learning progress', 'description' => 'Manage assessments and student grade records.'],
    'finance-payments' => ['title' => 'Finance & Payments', 'eyebrow' => 'Student finance', 'description' => 'Record and review payments while preserving the financial audit trail.'],
    'reports' => ['title' => 'Reports', 'eyebrow' => 'School-wide reporting', 'description' => 'Review current school totals and previously generated reports.'],
    'announcements' => ['title' => 'Announcements', 'eyebrow' => 'School communication', 'description' => 'Publish and maintain school-wide announcements.'],
];

if (!is_string($sectionKey) || !isset($sectionDefinitions[$sectionKey])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Admin section not found.');
}

$sectionDefinition = $sectionDefinitions[$sectionKey];
$academicSubnav = [
    'curriculum' => ['label' => 'Curriculum', 'description' => 'Browse and manage curriculum entries by program, academic year, year level, and semester.'],
    'subjects' => ['label' => 'Subjects', 'description' => 'Maintain the shared subject catalog used by curricula and class sections.'],
    'classes' => ['label' => 'Classes', 'description' => 'Manage class sections and their academic-year assignments.'],
    'teacher-assignments' => ['label' => 'Teacher assignments', 'description' => 'Assign teachers and subjects to sections and semesters.'],
    'academic-setup' => ['label' => 'Academic setup', 'description' => 'Manage academic years, departments, and programs.'],
];
$academicView = 'curriculum';
if ($sectionKey === 'academic-management') {
    $requestedAcademicView = $_GET['academic_view'] ?? 'curriculum';
    if (is_string($requestedAcademicView) && isset($academicSubnav[$requestedAcademicView])) {
        $academicView = $requestedAcademicView;
    }
}
$attendanceSubnav = [
    'attendance-overview' => ['label' => 'Attendance Overview', 'description' => 'Review attendance recorded for a selected date.'],
    'teacher-schedule' => ['label' => 'Teacher Schedule', 'description' => 'Review teaching assignments across the day.'],
    'student-schedule' => ['label' => 'Student Schedule', 'description' => 'Review class schedules and weekly section allocations.'],
    'room-monitoring' => ['label' => 'Room Monitoring', 'description' => 'Review room availability and current occupancy.'],
    'conflict-review' => ['label' => 'Conflict Review', 'description' => 'Find overlapping teacher, room, and section schedules.'],
];
$attendanceView = 'attendance-overview';
if ($sectionKey === 'attendance-scheduling') {
    $requestedAttendanceView = $_GET['attendance_view'] ?? $_POST['attendance_view'] ?? 'attendance-overview';
    if (is_string($requestedAttendanceView) && isset($attendanceSubnav[$requestedAttendanceView])) {
        $attendanceView = $requestedAttendanceView;
    }
}
$errorMessage = '';
$flashMessage = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$oldInput = [];
$oldInputEntity = '';
$fieldErrors = [];
$csrfToken = appCsrfToken();
$returnProgramId = filter_var(
    $_POST['return_program_id'] ?? $_GET['curriculum_program_id'] ?? $_GET['program_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$returnProgramId = $returnProgramId === false ? null : $returnProgramId;

$field = static fn (
    string $label,
    string $type = 'text',
    bool $required = false,
    array|string|null $options = null,
    bool $nullable = true
): array => [
    'label' => $label,
    'type' => $type,
    'required' => $required,
    'options' => $options,
    'nullable' => $nullable,
];

$enum = static fn (string ...$values): array => array_combine($values, $values);
$entityDefinitions = [
    'students' => [
        'section' => 'students', 'table' => 'students', 'primaryKey' => 'student_id', 'label' => 'Student profiles',
        'fields' => [
            'student_number' => $field('Student number', 'text', true, null, false),
            'desired_program_id' => $field('Program of interest', 'select', false, 'programs'),
            'first_name' => $field('First name', 'text', true, null, false),
            'middle_name' => $field('Middle name'),
            'last_name' => $field('Last name', 'text', true, null, false),
            'birth_date' => $field('Birth date', 'date'),
            'gender' => $field('Gender', 'select', false, $enum('Male', 'Female', 'Other')),
            'email' => $field('Email address', 'email'),
            'phone' => $field('Phone', 'tel'),
            'address' => $field('Address', 'textarea'),
            'admission_date' => $field('Admission date', 'date'),
            'student_status' => $field('Student status', 'select', true, $enum('Applicant', 'Enrolled', 'Inactive', 'Graduated', 'Transferred', 'Dropped'), false),
        ],
        'delete' => ['type' => 'status', 'field' => 'student_status', 'value' => 'Inactive', 'label' => 'Deactivate'],
    ],
    'enrollments' => [
        'section' => 'students', 'table' => 'enrollments', 'primaryKey' => 'enrollment_id', 'label' => 'Enrollments',
        'fields' => [
            'student_id' => $field('Student', 'select', true, 'students', false),
            'academic_year_id' => $field('Academic year', 'select', true, 'academic_years', false),
            'program_id' => $field('Program', 'select', false, 'programs'),
            'section_id' => $field('Class section', 'select', false, 'sections'),
            'year_level' => $field('Year level', 'number', true, null, false),
            'enrollment_date' => $field('Enrollment date', 'date', true, null, false),
            'enrollment_status' => $field('Status', 'select', true, $enum('Pending', 'Enrolled', 'Completed', 'Dropped', 'Cancelled'), false),
            'remarks' => $field('Remarks', 'textarea'),
        ],
        'delete' => ['type' => 'status', 'field' => 'enrollment_status', 'value' => 'Cancelled', 'label' => 'Cancel'],
    ],
    'teachers' => [
        'section' => 'teachers', 'table' => 'teachers', 'primaryKey' => 'teacher_id', 'label' => 'Teacher profiles',
        'fields' => [
            'employee_number' => $field('Employee number', 'text', true, null, false),
            'first_name' => $field('First name', 'text', true, null, false),
            'last_name' => $field('Last name', 'text', true, null, false),
            'email' => $field('Email address', 'email'),
            'phone' => $field('Phone', 'tel'),
            'department_id' => $field('Department', 'select', false, 'departments'),
            'specialization' => $field('Specialization'),
            'employment_status' => $field('Employment status', 'select', true, $enum('Active', 'Inactive', 'On Leave', 'Resigned'), false),
            'hire_date' => $field('Hire date', 'date'),
        ],
        'delete' => ['type' => 'status', 'field' => 'employment_status', 'value' => 'Inactive', 'label' => 'Deactivate'],
    ],
    'academic_years' => [
        'section' => 'academic-management', 'academicView' => 'academic-setup', 'table' => 'academic_years', 'primaryKey' => 'academic_year_id', 'label' => 'Academic years',
        'fields' => [
            'year_name' => $field('Academic year', 'text', true, null, false),
            'start_date' => $field('Start date', 'date', true, null, false),
            'end_date' => $field('End date', 'date', true, null, false),
            'status' => $field('Status', 'select', true, $enum('Upcoming', 'Active', 'Completed'), false),
        ],
        'delete' => ['type' => 'status', 'field' => 'status', 'value' => 'Completed', 'label' => 'Archive'],
    ],
    'departments' => [
        'section' => 'academic-management', 'academicView' => 'academic-setup', 'table' => 'departments', 'primaryKey' => 'department_id', 'label' => 'Departments',
        'fields' => [
            'department_code' => $field('Department code'),
            'department_name' => $field('Department name', 'text', true, null, false),
            'description' => $field('Description', 'textarea'),
        ],
        'delete' => ['type' => 'delete'],
    ],
    'programs' => [
        'section' => 'academic-management', 'academicView' => 'academic-setup', 'table' => 'programs', 'primaryKey' => 'program_id', 'label' => 'Programs',
        'fields' => [
            'department_id' => $field('Department', 'select', false, 'departments'),
            'program_code' => $field('Program code', 'text', true, null, false),
            'program_name' => $field('Program name', 'text', true, null, false),
            'duration_years' => $field('Duration in years', 'number'),
            'status' => $field('Status', 'select', true, $enum('Active', 'Inactive'), false),
        ],
        'delete' => ['type' => 'status', 'field' => 'status', 'value' => 'Inactive', 'label' => 'Deactivate'],
    ],
    'curriculum' => [
        'section' => 'academic-management', 'academicView' => 'curriculum', 'table' => 'curriculum', 'primaryKey' => 'curriculum_id', 'label' => 'Curriculum subjects',
        'fields' => [
            'program_id' => $field('Program', 'select', true, 'programs', false),
            'academic_year_id' => $field('Academic year', 'select', true, 'academic_years', false),
            'year_level' => $field('Year level', 'number', true, null, false),
            'semester_number' => $field('Semester number', 'number'),
            'subject_id' => $field('Subject', 'select', true, 'subjects', false),
            'is_required' => $field('Subject requirement', 'select', true, ['1' => 'Required', '0' => 'Elective'], false),
        ],
        'delete' => ['type' => 'delete'],
    ],
    'sections' => [
        'section' => 'academic-management', 'academicView' => 'classes', 'table' => 'sections', 'primaryKey' => 'section_id', 'label' => 'Class sections',
        'fields' => [
            'program_id' => $field('Program', 'select', false, 'programs'),
            'academic_year_id' => $field('Academic year', 'select', true, 'academic_years', false),
            'section_name' => $field('Section name', 'text', true, null, false),
            'year_level' => $field('Year level', 'number', true, null, false),
            'capacity' => $field('Capacity', 'number'),
            'status' => $field('Status', 'select', true, $enum('Active', 'Inactive'), false),
        ],
        'delete' => ['type' => 'status', 'field' => 'status', 'value' => 'Inactive', 'label' => 'Deactivate'],
    ],
    'subjects' => [
        'section' => 'academic-management', 'academicView' => 'subjects', 'table' => 'subjects', 'primaryKey' => 'subject_id', 'label' => 'Subjects',
        'fields' => [
            'subject_code' => $field('Subject code', 'text', true, null, false),
            'subject_name' => $field('Subject name', 'text', true, null, false),
            'description' => $field('Description', 'textarea'),
            'units' => $field('Units', 'number'),
            'subject_type' => $field('Subject type', 'select', true, $enum('Core', 'Major', 'Minor', 'Elective'), false),
            'status' => $field('Status', 'select', true, $enum('Active', 'Inactive'), false),
        ],
        'delete' => ['type' => 'status', 'field' => 'status', 'value' => 'Inactive', 'label' => 'Deactivate'],
    ],
    'teaching_assignments' => [
        'section' => 'academic-management', 'academicView' => 'teacher-assignments', 'table' => 'teaching_assignments', 'primaryKey' => 'assignment_id', 'label' => 'Teaching assignments',
        'fields' => [
            'teacher_id' => $field('Teacher', 'select', true, 'teachers', false),
            'subject_id' => $field('Subject', 'select', true, 'subjects', false),
            'section_id' => $field('Class section', 'select', true, 'sections', false),
            'semester_id' => $field('Semester', 'select', true, 'semesters', false),
        ],
        'delete' => ['type' => 'delete'],
    ],
    'attendance' => [
        'section' => 'attendance-scheduling', 'attendanceView' => 'attendance-overview', 'table' => 'attendance', 'primaryKey' => 'attendance_id', 'label' => 'Attendance records',
        'fields' => [
            'enrollment_id' => $field('Student enrollment', 'select', true, 'enrollments', false),
            'assignment_id' => $field('Teaching assignment', 'select', true, 'assignments', false),
            'attendance_date' => $field('Attendance date', 'date', true, null, false),
            'time_in' => $field('Time in', 'time'),
            'time_out' => $field('Time out', 'time'),
            'attendance_status' => $field('Status', 'select', true, $enum('Present', 'Absent', 'Late', 'Excused'), false),
            'remarks' => $field('Remarks', 'textarea'),
        ],
        'delete' => ['type' => 'delete'],
    ],
    'class_schedules' => [
        'section' => 'attendance-scheduling', 'attendanceView' => 'teacher-schedule', 'table' => 'class_schedules', 'primaryKey' => 'schedule_id', 'label' => 'Class schedules',
        'fields' => [
            'assignment_id' => $field('Teaching assignment', 'select', true, 'assignments', false),
            'room_id' => $field('Room', 'select', true, 'rooms', false),
            'day_of_week' => $field('Day', 'select', true, $enum('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), false),
            'start_time' => $field('Start time', 'time', true, null, false),
            'end_time' => $field('End time', 'time', true, null, false),
            'schedule_status' => $field('Status', 'select', true, $enum('Active', 'Cancelled', 'Rescheduled'), false),
        ],
        'delete' => ['type' => 'status', 'field' => 'schedule_status', 'value' => 'Cancelled', 'label' => 'Cancel'],
    ],
    'assessments' => [
        'section' => 'assessment-grading', 'table' => 'assessments', 'primaryKey' => 'assessment_id', 'label' => 'Assessments',
        'fields' => [
            'assignment_id' => $field('Teaching assignment', 'select', true, 'assignments', false),
            'grading_period_id' => $field('Grading period', 'select', true, 'grading_periods', false),
            'category_id' => $field('Category', 'select', true, 'assessment_categories', false),
            'assessment_title' => $field('Assessment title', 'text', true, null, false),
            'assessment_type' => $field('Assessment type', 'select', true, $enum('Quiz', 'Exam', 'Assignment', 'Project', 'Performance Task', 'Activity'), false),
            'total_points' => $field('Total points', 'number', true, null, false),
            'assessment_date' => $field('Assessment date', 'date'),
            'due_date' => $field('Due date', 'date'),
            'status' => $field('Status', 'select', true, $enum('Draft', 'Published', 'Completed', 'Cancelled'), false),
        ],
        'delete' => ['type' => 'status', 'field' => 'status', 'value' => 'Cancelled', 'label' => 'Cancel'],
    ],
    'grade_records' => [
        'section' => 'assessment-grading', 'table' => 'grade_records', 'primaryKey' => 'grade_id', 'label' => 'Grade records',
        'fields' => [
            'enrollment_id' => $field('Student enrollment', 'select', true, 'enrollments', false),
            'assignment_id' => $field('Teaching assignment', 'select', true, 'assignments', false),
            'grading_period_id' => $field('Grading period', 'select', true, 'grading_periods', false),
            'preliminary_grade' => $field('Preliminary grade', 'number'),
            'final_grade' => $field('Final grade', 'number'),
            'remarks' => $field('Result', 'select', true, $enum('Passed', 'Failed', 'Incomplete', 'Dropped', 'Pending'), false),
        ],
        'delete' => ['type' => 'delete'],
    ],
    'student_scores' => [
        'section' => 'assessment-grading', 'table' => 'student_scores', 'primaryKey' => 'score_id', 'label' => 'Assessment scores',
        'fields' => [
            'assessment_id' => $field('Assessment', 'select', true, 'assessments', false),
            'student_id' => $field('Student', 'select', true, 'students', false),
            'score' => $field('Score', 'number', true, null, false),
            'remarks' => $field('Remarks', 'textarea'),
        ],
        'delete' => ['type' => 'delete'],
    ],
    'student_fees' => [
        'section' => 'finance-payments', 'table' => 'student_fees', 'primaryKey' => 'student_fee_id', 'label' => 'Student fees',
        'fields' => [
            'enrollment_id' => $field('Student enrollment', 'select', true, 'enrollments', false),
            'fee_structure_id' => $field('Fee structure', 'select', true, 'fee_structures', false),
            'scholarship_id' => $field('Scholarship', 'select', false, 'scholarships'),
            'amount' => $field('Amount', 'number', true, null, false),
            'discount_amount' => $field('Discount amount', 'number'),
            'due_date' => $field('Due date', 'date'),
            'fee_status' => $field('Status', 'select', true, $enum('Unpaid', 'Partially Paid', 'Paid', 'Overdue', 'Waived'), false),
        ],
        'delete' => ['type' => 'status', 'field' => 'fee_status', 'value' => 'Waived', 'label' => 'Waive'],
    ],
    'payments' => [
        'section' => 'finance-payments', 'table' => 'payments', 'primaryKey' => 'payment_id', 'label' => 'Payment records',
        'fields' => [
            'enrollment_id' => $field('Student enrollment', 'select', true, 'enrollments', false),
            'student_fee_id' => $field('Fee (optional)', 'select', false, 'tuition_fees'),
            'payment_term' => $field('Tuition term (if applicable)', 'select', false, $enum('Prelim', 'Midterm', 'Final')),
            'receipt_number' => $field('Receipt number', 'text', true, null, false),
            'payment_date' => $field('Payment date and time', 'datetime-local'),
            'total_amount' => $field('Amount', 'number', true, null, false),
            'payment_method' => $field('Payment method', 'select', true, $enum('Cash', 'Bank Transfer', 'GCash', 'Maya', 'Card', 'Other'), false),
            'payment_status' => $field('Status', 'select', true, $enum('Pending', 'Completed', 'Failed', 'Refunded', 'Cancelled'), false),
            'reference_number' => $field('Reference number'),
            'remarks' => $field('Remarks', 'textarea'),
        ],
        'delete' => ['type' => 'status', 'field' => 'payment_status', 'value' => 'Cancelled', 'label' => 'Cancel'],
    ],
    'announcements' => [
        'section' => 'announcements', 'table' => 'announcements', 'primaryKey' => 'announcement_id', 'label' => 'School announcements',
        'fields' => [
            'title' => $field('Title', 'text', true, null, false),
            'message' => $field('Announcement', 'textarea', true, null, false),
            'status' => $field('Status', 'select', true, $enum('Draft', 'Published'), false),
        ],
        'delete' => ['type' => 'delete'],
    ],
];

$lookupDefinitions = [
    'departments' => ['SELECT department_id AS id, department_name AS label FROM departments ORDER BY department_name'],
    'programs' => ['SELECT program_id AS id, program_name AS label FROM programs ORDER BY program_name'],
    'academic_years' => ['SELECT academic_year_id AS id, year_name AS label FROM academic_years ORDER BY start_date DESC'],
    'sections' => ['SELECT section_id AS id, section_name AS label FROM sections ORDER BY section_name'],
    'students' => ["SELECT student_id AS id, CONCAT(student_number, ' - ', first_name, ' ', last_name) AS label FROM students ORDER BY last_name, first_name"],
    'teachers' => ["SELECT teacher_id AS id, CONCAT(employee_number, ' - ', first_name, ' ', last_name) AS label FROM teachers ORDER BY last_name, first_name"],
    'subjects' => ['SELECT subject_id AS id, subject_name AS label FROM subjects ORDER BY subject_name'],
    'semesters' => ['SELECT semester_id AS id, semester_name AS label FROM semesters ORDER BY start_date DESC'],
    'enrollments' => ["SELECT e.enrollment_id AS id, CONCAT(s.student_number, ' - ', s.first_name, ' ', s.last_name, ' (', e.enrollment_status, ')') AS label FROM enrollments e INNER JOIN students s ON s.student_id = e.student_id ORDER BY s.last_name, s.first_name"],
    'assignments' => ["SELECT ta.assignment_id AS id, CONCAT(t.first_name, ' ', t.last_name, ' - ', su.subject_name, ' - ', se.section_name) AS label FROM teaching_assignments ta INNER JOIN teachers t ON t.teacher_id = ta.teacher_id INNER JOIN subjects su ON su.subject_id = ta.subject_id INNER JOIN sections se ON se.section_id = ta.section_id WHERE ta.assignment_status = 'Active' ORDER BY t.last_name, su.subject_name"],
    'rooms' => ['SELECT room_id AS id, CONCAT(room_code, " - ", room_name) AS label FROM rooms ORDER BY room_code'],
    'grading_periods' => ['SELECT grading_period_id AS id, period_name AS label FROM grading_periods ORDER BY start_date DESC'],
    'assessment_categories' => ['SELECT category_id AS id, category_name AS label FROM assessment_categories ORDER BY category_name'],
    'assessments' => ['SELECT assessment_id AS id, assessment_title AS label FROM assessments ORDER BY assessment_date DESC'],
    'fee_structures' => ["SELECT fs.fee_structure_id AS id, CONCAT(ft.fee_name, ' - ', FORMAT(fs.amount, 2)) AS label FROM fee_structures fs INNER JOIN fee_types ft ON ft.fee_type_id = fs.fee_type_id ORDER BY ft.fee_name"],
    'tuition_fees' => [
        "SELECT sf.student_fee_id AS id,
                CONCAT(s.student_number, ' - ', s.first_name, ' ', s.last_name, ' / ',
                       ft.fee_name, ' (', ft.fee_category, ') / Enrollment ', e.enrollment_id, ' / Net ',
                       FORMAT(GREATEST(sf.amount - COALESCE(sf.discount_amount, 0), 0), 2)) AS label
         FROM student_fees sf
         INNER JOIN enrollments e ON e.enrollment_id = sf.enrollment_id
         INNER JOIN students s ON s.student_id = e.student_id
         INNER JOIN fee_structures fs ON fs.fee_structure_id = sf.fee_structure_id
         INNER JOIN fee_types ft ON ft.fee_type_id = fs.fee_type_id
         WHERE sf.fee_status <> 'Waived'
           AND sf.amount > COALESCE(sf.discount_amount, 0)
         ORDER BY s.last_name, s.first_name, e.enrollment_id, sf.student_fee_id"
    ],
    'scholarships' => ['SELECT scholarship_id AS id, scholarship_name AS label FROM scholarships ORDER BY scholarship_name'],
];

$lookupOptions = [];
foreach ($lookupDefinitions as $lookupName => [$lookupQuery]) {
    $lookupOptions[$lookupName] = $connection->query($lookupQuery)->fetchAll();
}

$validDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
$validDateTimeLocal = static function (string $value): bool {
    $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
    return $dateTime !== false && $dateTime->format('Y-m-d\TH:i') === $value;
};
$validTime = static function (string $value): bool {
    $time = DateTimeImmutable::createFromFormat('!H:i', $value);
    return $time !== false && $time->format('H:i') === $value;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your security token has expired. Reload this page and try again.';
    } else {
        $action = adminPostString('action');
        $entityKey = adminPostString('entity');
        $oldInputEntity = $entityKey;
        $entity = $entityDefinitions[$entityKey] ?? null;
        $recordId = adminPostInt('record_id');
        $hasRecordId = array_key_exists('record_id', $_POST);

        if ($entity === null || $entity['section'] !== $sectionKey
            || ($sectionKey === 'academic-management' && ($entity['academicView'] ?? null) !== $academicView)
            || ($sectionKey === 'attendance-scheduling' && ($entity['attendanceView'] ?? null) !== $attendanceView)) {
            $errorMessage = 'Choose a supported action for this Admin section.';
        } elseif ($hasRecordId && $recordId === null) {
            $errorMessage = 'The selected record identifier is invalid. Reload the page and try again.';
        } elseif ($action === 'save') {
            $values = [];
            foreach ($entity['fields'] as $name => $definition) {
                $rawValue = adminPostString($name);
                $oldInput[$name] = $rawValue;
                $value = $rawValue === '' ? null : $rawValue;

                if ($definition['required'] && $value === null) {
                    $fieldErrors[$name] = 'Complete this required field.';
                } elseif ($definition['type'] === 'email' && $value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $fieldErrors[$name] = 'Enter a valid email address.';
                } elseif ($definition['type'] === 'date' && $value !== null && !$validDate($value)) {
                    $fieldErrors[$name] = 'Enter a valid calendar date.';
                } elseif ($definition['type'] === 'datetime-local' && $value !== null
                    && (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) !== 1
                        || !$validDateTimeLocal($value))) {
                    $fieldErrors[$name] = 'Enter a valid date and time.';
                } elseif ($definition['type'] === 'time' && $value !== null
                    && (preg_match('/^\d{2}:\d{2}$/', $value) !== 1
                        || !$validTime($value))) {
                    $fieldErrors[$name] = 'Enter a valid time.';
                } elseif ($definition['type'] === 'number' && $value !== null && !is_numeric($value)) {
                    $fieldErrors[$name] = 'Enter a valid number.';
                } elseif (is_array($definition['options']) && $value !== null && !array_key_exists($value, $definition['options'])) {
                    $fieldErrors[$name] = 'Choose one of the available options.';
                } elseif (is_string($definition['options']) && $value !== null) {
                    $optionIds = array_column($lookupOptions[$definition['options']], 'id');
                    if (!in_array((string) $value, array_map('strval', $optionIds), true)) {
                        $fieldErrors[$name] = 'Choose a valid available record.';
                    } else {
                        $value = (int) $value;
                    }
                } elseif ($definition['type'] === 'number' && $value !== null) {
                    $value = str_contains((string) $value, '.') ? (float) $value : (int) $value;
                }

                $values[$name] = $value;
            }

            if ($entityKey === 'academic_years' && isset($values['start_date'], $values['end_date'])
                && $values['start_date'] !== null && $values['end_date'] !== null
                && $values['end_date'] < $values['start_date']) {
                $fieldErrors['end_date'] = 'The end date must be on or after the start date.';
            }
            if ($entityKey === 'class_schedules' && isset($values['start_time'], $values['end_time'])
                && $values['start_time'] !== null && $values['end_time'] !== null
                && $values['end_time'] <= $values['start_time']) {
                $fieldErrors['end_time'] = 'The end time must be later than the start time.';
            }
            if ($entityKey === 'assessments' && isset($values['total_points']) && $values['total_points'] !== null
                && $values['total_points'] <= 0) {
                $fieldErrors['total_points'] = 'Total points must be greater than zero.';
            }
            foreach (['payments' => 'total_amount', 'student_fees' => 'amount'] as $amountEntity => $amountField) {
                if ($entityKey === $amountEntity && isset($values[$amountField]) && $values[$amountField] !== null
                    && $values[$amountField] <= 0) {
                    $fieldErrors[$amountField] = 'Amount must be greater than zero.';
                }
            }
            if ($entityKey === 'student_fees' && isset($values['discount_amount']) && $values['discount_amount'] !== null
                && $values['discount_amount'] < 0) {
                $fieldErrors['discount_amount'] = 'Discount cannot be negative.';
            }
            if ($entityKey === 'student_fees' && isset($values['amount'], $values['discount_amount'])
                && $values['discount_amount'] !== null && $values['discount_amount'] > $values['amount']) {
                $fieldErrors['discount_amount'] = 'Discount cannot exceed the fee amount.';
            }
            if ($entityKey === 'payments' && $values['student_fee_id'] === null
                && $values['payment_term'] !== null) {
                $fieldErrors['student_fee_id'] = 'Select a fee before assigning a tuition term.';
            }
            foreach (['preliminary_grade', 'final_grade'] as $gradeField) {
                if ($entityKey === 'grade_records' && isset($values[$gradeField]) && $values[$gradeField] !== null
                    && ($values[$gradeField] < 0 || $values[$gradeField] > 100)) {
                    $fieldErrors[$gradeField] = 'Enter a grade from 0 to 100.';
                }
            }
            if ($entityKey === 'student_scores' && isset($values['assessment_id'], $values['score'])) {
                $maximumQuery = $connection->prepare('SELECT total_points FROM assessments WHERE assessment_id = :assessment_id');
                $maximumQuery->execute(['assessment_id' => $values['assessment_id']]);
                $maximumScore = $maximumQuery->fetchColumn();
                if ($maximumScore === false || $values['score'] < 0 || $values['score'] > (float) $maximumScore) {
                    $fieldErrors['score'] = 'Enter a score from 0 to the selected assessment’s total points.';
                }
            }
            foreach (['duration_years', 'year_level', 'semester_number', 'capacity', 'units'] as $positiveField) {
                if (isset($values[$positiveField]) && $values[$positiveField] !== null && $values[$positiveField] <= 0) {
                    $fieldErrors[$positiveField] = 'Enter a number greater than zero.';
                }
            }
            if ($entityKey === 'curriculum') {
                foreach (['year_level', 'semester_number'] as $wholeNumberField) {
                    if (
                        isset($values[$wholeNumberField])
                        && $values[$wholeNumberField] !== null
                        && floor((float) $values[$wholeNumberField]) !== (float) $values[$wholeNumberField]
                    ) {
                        $fieldErrors[$wholeNumberField] = 'Enter a whole number.';
                    }
                }
            }

            if ($fieldErrors === []) {
                if ($entityKey === 'attendance' || $entityKey === 'payments') {
                    $studentLookup = $connection->prepare(
                        'SELECT student_id FROM enrollments WHERE enrollment_id = :enrollment_id LIMIT 1'
                    );
                    $studentLookup->execute(['enrollment_id' => $values['enrollment_id']]);
                    $studentId = $studentLookup->fetchColumn();
                    if ($studentId === false) {
                        $fieldErrors['enrollment_id'] = 'Select an enrollment that still exists.';
                    } else {
                        $values['student_id'] = (int) $studentId;
                    }
                }
                if ($entityKey === 'attendance' || $entityKey === 'grade_records') {
                    $enrollmentAssignment = $connection->prepare(
                        'SELECT e.section_id AS enrollment_section_id, ta.section_id AS assignment_section_id
                         FROM enrollments e
                         INNER JOIN teaching_assignments ta ON ta.assignment_id = :assignment_id
                         WHERE e.enrollment_id = :enrollment_id
                         LIMIT 1'
                    );
                    $enrollmentAssignment->execute([
                        'assignment_id' => $values['assignment_id'],
                        'enrollment_id' => $values['enrollment_id'],
                    ]);
                    $sectionMatch = $enrollmentAssignment->fetch();
                    if ($sectionMatch === false || $sectionMatch['enrollment_section_id'] === null
                        || (int) $sectionMatch['enrollment_section_id'] !== (int) $sectionMatch['assignment_section_id']) {
                        $fieldErrors['enrollment_id'] = 'Choose an enrollment in the selected class section.';
                    }
                }
                if ($entityKey === 'student_scores') {
                    $scoreStudent = $connection->prepare(
                        'SELECT COUNT(*)
                         FROM assessments a
                         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
                         INNER JOIN enrollments e ON e.section_id = ta.section_id
                         WHERE a.assessment_id = :assessment_id
                           AND e.student_id = :student_id'
                    );
                    $scoreStudent->execute([
                        'assessment_id' => $values['assessment_id'],
                        'student_id' => $values['student_id'],
                    ]);
                    if ((int) $scoreStudent->fetchColumn() === 0) {
                        $fieldErrors['student_id'] = 'Choose a student enrolled in the assessment’s class section.';
                    }
                }
                if ($entityKey === 'enrollments' && $values['section_id'] !== null) {
                    $sectionQuery = $connection->prepare(
                        'SELECT academic_year_id, program_id FROM sections WHERE section_id = :section_id LIMIT 1'
                    );
                    $sectionQuery->execute(['section_id' => $values['section_id']]);
                    $sectionData = $sectionQuery->fetch();
                    if ($sectionData === false
                        || (int) $sectionData['academic_year_id'] !== (int) $values['academic_year_id']
                        || ($sectionData['program_id'] === null ? null : (int) $sectionData['program_id'])
                            !== $values['program_id']) {
                        $fieldErrors['section_id'] = 'The selected section must belong to this academic year and program.';
                    }
                }
                if ($entityKey === 'curriculum') {
                    $duplicateSql = 'SELECT COUNT(*)
                                     FROM curriculum
                                     WHERE program_id = :program_id
                                       AND subject_id = :subject_id
                                       AND academic_year_id = :academic_year_id
                                       AND year_level = :year_level
                                       AND semester_number <=> :semester_number';
                    $duplicateParams = [
                        'program_id' => $values['program_id'],
                        'subject_id' => $values['subject_id'],
                        'academic_year_id' => $values['academic_year_id'],
                        'year_level' => $values['year_level'],
                        'semester_number' => $values['semester_number'],
                    ];
                    if ($recordId !== null) {
                        $duplicateSql .= ' AND curriculum_id <> :record_id';
                        $duplicateParams['record_id'] = $recordId;
                    }
                    $duplicateCurriculum = $connection->prepare($duplicateSql);
                    $duplicateCurriculum->execute($duplicateParams);
                    if ((int) $duplicateCurriculum->fetchColumn() > 0) {
                        $fieldErrors['subject_id'] = 'This subject is already listed for the selected program, academic year, year level, and semester.';
                    }
                }
                if ($entityKey === 'class_schedules') {
                    $conflictSql = 'SELECT COUNT(*) FROM class_schedules
                                    WHERE day_of_week = :day_of_week
                                      AND schedule_status = :active_status
                                      AND start_time < :end_time
                                      AND end_time > :start_time
                                      AND (room_id = :room_id OR assignment_id = :assignment_id)';
                    $conflictParams = [
                        'day_of_week' => $values['day_of_week'],
                        'active_status' => 'Active',
                        'end_time' => $values['end_time'],
                        'start_time' => $values['start_time'],
                        'room_id' => $values['room_id'],
                        'assignment_id' => $values['assignment_id'],
                    ];
                    if ($recordId !== null) {
                        $conflictSql .= ' AND schedule_id <> :record_id';
                        $conflictParams['record_id'] = $recordId;
                    }
                    $conflicts = $connection->prepare($conflictSql);
                    $conflicts->execute($conflictParams);
                    if ((int) $conflicts->fetchColumn() > 0) {
                        $fieldErrors['start_time'] = 'This room or teaching assignment already has an overlapping active schedule.';
                    }
                }

                if ($entityKey === 'payments') {
                    $values['received_by'] = (int) $user['user_id'];
                    if ($values['payment_date'] === null) {
                        $values['payment_date'] = date('Y-m-d H:i:s');
                    } else {
                        $values['payment_date'] = str_replace('T', ' ', $values['payment_date']) . ':00';
                    }
                }
                if ($entityKey === 'attendance') {
                    $values['recorded_by'] = (int) $user['user_id'];
                }
                if ($entityKey === 'grade_records') {
                    $values['encoded_by'] = (int) $user['user_id'];
                }
                if ($entityKey === 'student_scores') {
                    $values['recorded_by'] = (int) $user['user_id'];
                }
                if ($entityKey === 'announcements' && $recordId === null) {
                    $values['created_by'] = (int) $user['user_id'];
                }
            }

            if ($fieldErrors !== []) {
                $errorMessage = 'Please correct the highlighted fields before saving.';
            }
            if ($fieldErrors === []) {
                $table = $entity['table'];
                $primaryKey = $entity['primaryKey'];
                $fieldNames = array_keys($entity['fields']);
                if (isset($values['student_id'])) {
                    $fieldNames[] = 'student_id';
                }
                if (isset($values['received_by'])) {
                    $fieldNames[] = 'received_by';
                }
                if (isset($values['encoded_by'])) {
                    $fieldNames[] = 'encoded_by';
                }
                if (isset($values['recorded_by'])) {
                    $fieldNames[] = 'recorded_by';
                }
                if (isset($values['created_by'])) {
                    $fieldNames[] = 'created_by';
                }

                try {
                    $connection->beginTransaction();
                    if ($entityKey === 'academic_years' && $values['status'] === 'Active') {
                        $completeOtherYearsSql = "UPDATE academic_years
                                                  SET status = 'Completed'
                                                  WHERE status = 'Active'";
                        $completeOtherYearsParams = [];
                        if ($recordId !== null) {
                            $completeOtherYearsSql .= ' AND academic_year_id <> :record_id';
                            $completeOtherYearsParams['record_id'] = $recordId;
                        }
                        $completeOtherYears = $connection->prepare($completeOtherYearsSql);
                        $completeOtherYears->execute($completeOtherYearsParams);
                    }
                    if ($entityKey === 'enrollments' && $values['enrollment_status'] === 'Enrolled') {
                        $academicYearCheck = $connection->prepare(
                            "SELECT status
                             FROM academic_years
                             WHERE academic_year_id = :academic_year_id
                             FOR UPDATE"
                        );
                        $academicYearCheck->execute(['academic_year_id' => $values['academic_year_id']]);
                        if ($academicYearCheck->fetchColumn() !== 'Active') {
                            throw new RuntimeException('An enrollment can be marked Enrolled only in the active academic year.');
                        }
                        if ($values['section_id'] === null) {
                            throw new RuntimeException('Select an active class section before marking this enrollment Enrolled.');
                        }

                        $sectionCheck = $connection->prepare(
                            'SELECT sec.academic_year_id, sec.program_id, sec.year_level, sec.capacity,
                                    sec.status AS section_status, p.status AS program_status
                             FROM sections sec
                             LEFT JOIN programs p ON p.program_id = sec.program_id
                             WHERE sec.section_id = :section_id
                             FOR UPDATE'
                        );
                        $sectionCheck->execute(['section_id' => $values['section_id']]);
                        $sectionData = $sectionCheck->fetch();
                        if (
                            $sectionData === false
                            || $sectionData['section_status'] !== 'Active'
                            || (int) $sectionData['academic_year_id'] !== (int) $values['academic_year_id']
                            || ($sectionData['program_id'] === null ? null : (int) $sectionData['program_id'])
                                !== $values['program_id']
                            || (int) $sectionData['year_level'] !== (int) $values['year_level']
                            || ($sectionData['program_id'] !== null && $sectionData['program_status'] !== 'Active')
                        ) {
                            throw new RuntimeException('An enrollment marked Enrolled must match an active section, program, academic year, and year level.');
                        }

                        $capacitySql = "SELECT COUNT(*)
                                        FROM enrollments
                                        WHERE section_id = :section_id
                                          AND enrollment_status = 'Enrolled'";
                        $capacityParams = ['section_id' => $values['section_id']];
                        if ($recordId !== null) {
                            $capacitySql .= ' AND enrollment_id <> :record_id';
                            $capacityParams['record_id'] = $recordId;
                        }
                        $capacityCheck = $connection->prepare($capacitySql);
                        $capacityCheck->execute($capacityParams);
                        if (
                            $sectionData['capacity'] !== null
                            && (int) $capacityCheck->fetchColumn() >= (int) $sectionData['capacity']
                        ) {
                            throw new RuntimeException('The selected section has reached capacity.');
                        }
                    }
                    $previousPayment = null;
                    if ($entityKey === 'payments' && $recordId !== null) {
                        $previousPaymentQuery = $connection->prepare(
                            'SELECT student_id, payment_status
                             FROM payments
                             WHERE payment_id = :payment_id
                             FOR UPDATE'
                        );
                        $previousPaymentQuery->execute(['payment_id' => $recordId]);
                        $previousPayment = $previousPaymentQuery->fetch();
                    }
                    if ($entityKey === 'payments' && $values['student_fee_id'] !== null) {
                        $tuitionFeeCheck = $connection->prepare(
                            "SELECT
                                ROUND(GREATEST(sf.amount - COALESCE(sf.discount_amount, 0), 0) * 100) AS net_tuition_cents,
                                ft.fee_category,
                                sf.fee_status
                             FROM student_fees sf
                             INNER JOIN fee_structures fs ON fs.fee_structure_id = sf.fee_structure_id
                             INNER JOIN fee_types ft ON ft.fee_type_id = fs.fee_type_id
                             WHERE sf.student_fee_id = :student_fee_id
                               AND sf.enrollment_id = :enrollment_id
                             FOR UPDATE"
                        );
                        $tuitionFeeCheck->execute([
                            'student_fee_id' => $values['student_fee_id'],
                            'enrollment_id' => $values['enrollment_id'],
                        ]);
                        $tuitionFee = $tuitionFeeCheck->fetch();
                        if ($tuitionFee === false || $tuitionFee['fee_status'] === 'Waived'
                            || (int) $tuitionFee['net_tuition_cents'] <= 0) {
                            $fieldErrors['student_fee_id'] = 'Choose a non-waived fee belonging to the selected enrollment.';
                            throw new RuntimeException('Choose a non-waived fee belonging to the selected enrollment.');
                        }

                        if ($tuitionFee['fee_category'] === 'Tuition' && $values['payment_term'] === null) {
                            $fieldErrors['payment_term'] = 'Choose a tuition term for a tuition fee.';
                            throw new RuntimeException($fieldErrors['payment_term']);
                        }
                        if ($tuitionFee['fee_category'] !== 'Tuition' && $values['payment_term'] !== null) {
                            $fieldErrors['payment_term'] = 'Only tuition fees can be assigned a tuition term.';
                            throw new RuntimeException($fieldErrors['payment_term']);
                        }

                        if ($values['payment_status'] === 'Completed') {
                            $paymentBalanceSql = "SELECT COALESCE(SUM(ROUND(total_amount * 100)), 0)
                                                  FROM payments
                                                  WHERE student_fee_id = :student_fee_id
                                                    AND payment_status = 'Completed'";
                            $paymentBalanceParams = ['student_fee_id' => $values['student_fee_id']];
                            if ($tuitionFee['fee_category'] === 'Tuition') {
                                $paymentBalanceSql .= ' AND payment_term = :payment_term';
                                $paymentBalanceParams['payment_term'] = $values['payment_term'];
                                $installments = financeTuitionInstallmentsInCents((int) $tuitionFee['net_tuition_cents']);
                                $termBalanceCents = $installments[$values['payment_term']];
                            } else {
                                $termBalanceCents = (int) $tuitionFee['net_tuition_cents'];
                            }
                            if ($recordId !== null) {
                                $paymentBalanceSql .= ' AND payment_id <> :record_id';
                                $paymentBalanceParams['record_id'] = $recordId;
                            }
                            $completedPayments = $connection->prepare($paymentBalanceSql);
                            $completedPayments->execute($paymentBalanceParams);
                            $termPaidCents = (int) $completedPayments->fetchColumn();
                            $paymentCents = (int) round((float) $values['total_amount'] * 100);
                            if ($termPaidCents + $paymentCents > $termBalanceCents) {
                                $remainingCents = max(0, $termBalanceCents - $termPaidCents);
                                $fieldErrors['total_amount'] = 'This term has '
                                    . number_format($remainingCents / 100, 2)
                                    . ' remaining. Enter no more than this amount.';
                                throw new RuntimeException($fieldErrors['total_amount']);
                            }
                        }
                    }
                    if ($recordId === null) {
                        $columns = implode(', ', $fieldNames);
                        $placeholders = implode(', ', array_map(static fn (string $name): string => ':' . $name, $fieldNames));
                        $statement = $connection->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
                        $statement->execute(array_intersect_key($values, array_flip($fieldNames)));
                        $auditAction = 'Create';
                        $auditId = (int) $connection->lastInsertId();
                    } else {
                        $setClause = implode(', ', array_map(static fn (string $name): string => "{$name} = :{$name}", $fieldNames));
                        $statement = $connection->prepare("UPDATE {$table} SET {$setClause} WHERE {$primaryKey} = :record_id");
                        $statement->execute(array_merge(
                            array_intersect_key($values, array_flip($fieldNames)),
                            ['record_id' => $recordId]
                        ));
                        if ($statement->rowCount() === 0) {
                            $exists = $connection->prepare("SELECT COUNT(*) FROM {$table} WHERE {$primaryKey} = :record_id");
                            $exists->execute(['record_id' => $recordId]);
                            if ((int) $exists->fetchColumn() === 0) {
                                $connection->rollBack();
                                $errorMessage = 'The selected record no longer exists. Reload the page and try again.';
                            }
                        }
                        if ($errorMessage !== '') {
                            throw new RuntimeException($errorMessage);
                        }
                        $auditAction = 'Update';
                        $auditId = $recordId;
                    }
                    if (
                        $entityKey === 'payments'
                        && (
                            ($recordId === null && $values['payment_status'] !== 'Pending')
                            || ($recordId !== null && $previousPayment !== false
                                && $previousPayment !== null
                                && $previousPayment['payment_status'] !== $values['payment_status'])
                        )
                    ) {
                        $paymentStudentId = $recordId === null
                            ? (int) $values['student_id']
                            : (int) $previousPayment['student_id'];
                        $studentUserQuery = $connection->prepare(
                            'SELECT user_id FROM students WHERE student_id = :student_id'
                        );
                        $studentUserQuery->execute(['student_id' => $paymentStudentId]);
                        $studentUserId = $studentUserQuery->fetchColumn();
                        if ($studentUserId !== false && $studentUserId !== null) {
                            $paymentId = $recordId ?? $auditId;
                            financeCreateStudentNotification(
                                $connection,
                                (int) $studentUserId,
                                'payment-status-' . $paymentId . '-' . $values['payment_status'],
                                'Payment status',
                                'Payment status updated',
                                'Payment ' . $values['receipt_number'] . ' for ₱'
                                    . number_format((float) $values['total_amount'], 2)
                                    . ' is now ' . $values['payment_status']
                                    . ($values['payment_term'] !== null ? ' for ' . $values['payment_term'] . '.' : '.')
                            );
                        }
                    }
                    adminRecordAudit(
                        $connection,
                        (int) $user['user_id'],
                        $auditAction,
                        $sectionDefinition['title'],
                        "{$auditAction} {$entity['label']} record {$auditId}."
                    );
                    $connection->commit();
                    $_SESSION['admin_flash'] = $recordId === null
                        ? "{$entity['label']} record created."
                        : "{$entity['label']} record updated.";
                    $redirectUrl = 'admin-section.php?section=' . rawurlencode($sectionKey);
                    if ($sectionKey === 'academic-management') {
                        $redirectUrl .= '&academic_view=' . rawurlencode($academicView);
                        if ($entityKey === 'curriculum' && $returnProgramId !== null) {
                            $redirectUrl .= '&program_id=' . $returnProgramId
                                . '&curriculum_program_id=' . $returnProgramId;
                        }
                    } elseif ($sectionKey === 'attendance-scheduling') {
                        $redirectUrl .= '&attendance_view=' . rawurlencode($attendanceView);
                    }
                    header('Location: ' . $redirectUrl, true, 303);
                    exit;
                } catch (PDOException $exception) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
                    }
                    $errorMessage = 'The record could not be saved because a value is already in use or a linked record is unavailable.';
                } catch (RuntimeException $exception) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                    $errorMessage = $exception->getMessage();
                } catch (Throwable $exception) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                    throw $exception;
                }
            }
        } elseif ($action === 'delete' && $recordId !== null
            && ($entity['section'] !== 'academic-management'
                || ($entity['academicView'] ?? null) === $academicView)) {
            $deleteDefinition = $entity['delete'];
            try {
                $connection->beginTransaction();
                if ($entityKey === 'students') {
                    $account = $connection->prepare(
                        "SELECT s.user_id
                         FROM students s
                         INNER JOIN users u ON u.user_id = s.user_id
                         INNER JOIN roles r ON r.role_id = u.role_id
                         WHERE s.student_id = :record_id AND r.role_name = 'Student'"
                    );
                    $account->execute(['record_id' => $recordId]);
                    $linkedUserId = $account->fetchColumn();
                    $statement = $connection->prepare(
                        "UPDATE students SET student_status = 'Inactive' WHERE student_id = :record_id"
                    );
                    $statement->execute(['record_id' => $recordId]);
                    if ($linkedUserId !== false && $linkedUserId !== null) {
                        $deactivateUser = $connection->prepare("UPDATE users SET account_status = 'Inactive' WHERE user_id = :user_id");
                        $deactivateUser->execute(['user_id' => (int) $linkedUserId]);
                    }
                } elseif ($entityKey === 'teachers') {
                    $account = $connection->prepare(
                        "SELECT t.user_id
                         FROM teachers t
                         INNER JOIN users u ON u.user_id = t.user_id
                         INNER JOIN roles r ON r.role_id = u.role_id
                         WHERE t.teacher_id = :record_id AND r.role_name = 'Teacher'"
                    );
                    $account->execute(['record_id' => $recordId]);
                    $linkedUserId = $account->fetchColumn();
                    $statement = $connection->prepare(
                        "UPDATE teachers SET employment_status = 'Inactive' WHERE teacher_id = :record_id"
                    );
                    $statement->execute(['record_id' => $recordId]);
                    if ($linkedUserId !== false && $linkedUserId !== null) {
                        $deactivateUser = $connection->prepare("UPDATE users SET account_status = 'Inactive' WHERE user_id = :user_id");
                        $deactivateUser->execute(['user_id' => (int) $linkedUserId]);
                    }
                } elseif ($deleteDefinition['type'] === 'status') {
                    $paymentBeingCancelled = null;
                    if ($entityKey === 'payments') {
                        $paymentBeingCancelledQuery = $connection->prepare(
                            'SELECT student_id, receipt_number, total_amount, payment_term, payment_status
                             FROM payments
                             WHERE payment_id = :payment_id
                             FOR UPDATE'
                        );
                        $paymentBeingCancelledQuery->execute(['payment_id' => $recordId]);
                        $paymentBeingCancelled = $paymentBeingCancelledQuery->fetch();
                    }
                    $statement = $connection->prepare(
                        "UPDATE {$entity['table']} SET {$deleteDefinition['field']} = :status WHERE {$entity['primaryKey']} = :record_id"
                    );
                    $statement->execute(['status' => $deleteDefinition['value'], 'record_id' => $recordId]);
                    if (
                        $entityKey === 'payments'
                        && $paymentBeingCancelled !== false
                        && $paymentBeingCancelled !== null
                        && $paymentBeingCancelled['payment_status'] !== $deleteDefinition['value']
                    ) {
                        $studentUserQuery = $connection->prepare(
                            'SELECT user_id FROM students WHERE student_id = :student_id'
                        );
                        $studentUserQuery->execute(['student_id' => (int) $paymentBeingCancelled['student_id']]);
                        $studentUserId = $studentUserQuery->fetchColumn();
                        if ($studentUserId !== false && $studentUserId !== null) {
                            financeCreateStudentNotification(
                                $connection,
                                (int) $studentUserId,
                                'payment-status-' . $recordId . '-' . $deleteDefinition['value'],
                                'Payment status',
                                'Payment status updated',
                                'Payment ' . $paymentBeingCancelled['receipt_number'] . ' for ₱'
                                    . number_format((float) $paymentBeingCancelled['total_amount'], 2)
                                    . ' is now ' . $deleteDefinition['value']
                                    . ($paymentBeingCancelled['payment_term'] !== null
                                        ? ' for ' . $paymentBeingCancelled['payment_term'] . '.'
                                        : '.')
                            );
                        }
                    }
                } else {
                    $statement = $connection->prepare(
                        "DELETE FROM {$entity['table']} WHERE {$entity['primaryKey']} = :record_id"
                    );
                    $statement->execute(['record_id' => $recordId]);
                }

                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException('The selected record could not be found.');
                }
                adminRecordAudit(
                    $connection,
                    (int) $user['user_id'],
                    $entityKey === 'curriculum'
                        ? 'Remove from curriculum'
                        : ($deleteDefinition['type'] === 'status' ? 'Deactivate' : 'Delete'),
                    $sectionDefinition['title'],
                    $entityKey === 'curriculum'
                        ? "Removed curriculum entry {$recordId}; the shared subject remains unchanged."
                        : "{$deleteDefinition['label']} {$entity['label']} record {$recordId}."
                );
                $connection->commit();
                $_SESSION['admin_flash'] = "{$deleteDefinition['label']} action completed.";
                $redirectUrl = 'admin-section.php?section=' . rawurlencode($sectionKey);
                if ($sectionKey === 'academic-management') {
                    $redirectUrl .= '&academic_view=' . rawurlencode($academicView);
                    if ($entityKey === 'curriculum' && $returnProgramId !== null) {
                        $redirectUrl .= '&program_id=' . $returnProgramId
                            . '&curriculum_program_id=' . $returnProgramId;
                    }
                } elseif ($sectionKey === 'attendance-scheduling') {
                    $redirectUrl .= '&attendance_view=' . rawurlencode($attendanceView);
                }
                header('Location: ' . $redirectUrl, true, 303);
                exit;
            } catch (PDOException $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $errorMessage = 'This record is linked to other school records and cannot be permanently deleted. Deactivate or cancel it instead.';
            } catch (RuntimeException $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                $errorMessage = $exception->getMessage();
            } catch (Throwable $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                throw $exception;
            }
        } else {
            $errorMessage = 'Choose a valid record action.';
        }
    }
}

$entityRows = [];
foreach ($entityDefinitions as $entityKey => $entity) {
    if ($entity['section'] !== $sectionKey
        || ($sectionKey === 'academic-management' && ($entity['academicView'] ?? null) !== $academicView)
        || ($sectionKey === 'attendance-scheduling' && ($entity['attendanceView'] ?? null) !== $attendanceView)) {
        continue;
    }
    if ($sectionKey === 'academic-management' && $academicView === 'subjects' && $entityKey === 'subjects') {
        continue;
    }
    if ($sectionKey === 'academic-management' && $academicView === 'curriculum' && $entityKey === 'curriculum') {
        continue;
    }
    $entityRows[$entityKey] = $connection->query(
        "SELECT * FROM {$entity['table']} ORDER BY {$entity['primaryKey']} DESC LIMIT 100"
    )->fetchAll();
}

$requestedCurriculumProgramId = $_GET['curriculum_program_id'] ?? $returnProgramId;
$selectedCurriculumProgramId = filter_var(
    $requestedCurriculumProgramId,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$selectedCurriculumProgramId = $selectedCurriculumProgramId === false ? null : $selectedCurriculumProgramId;

$subjectsCatalog = null;
if ($sectionKey === 'academic-management' && $academicView === 'subjects') {
    $selectedSubjectProgramId = filter_var(
        $_GET['program_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $selectedSubjectProgramId = $selectedSubjectProgramId === false ? null : $selectedSubjectProgramId;
    $selectedSubjectYearLevel = filter_var(
        $_GET['year_level'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $selectedSubjectYearLevel = $selectedSubjectYearLevel === false ? null : $selectedSubjectYearLevel;
    $semesterFilter = $_GET['semester_number'] ?? null;
    if ($semesterFilter === 'unspecified') {
        $selectedSubjectSemester = 'unspecified';
    } else {
        $selectedSubjectSemester = filter_var(
            $semesterFilter,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $selectedSubjectSemester = $selectedSubjectSemester === false ? null : $selectedSubjectSemester;
    }

    $subjectProgramOptions = $connection->query(
        'SELECT program_id, program_code, program_name
         FROM programs
         ORDER BY program_name'
    )->fetchAll();
    $subjectYearOptions = $connection->query(
        'SELECT DISTINCT year_level
         FROM curriculum
         ORDER BY year_level'
    )->fetchAll(PDO::FETCH_COLUMN);
    $subjectSemesterOptions = $connection->query(
        'SELECT DISTINCT semester_number
         FROM curriculum
         ORDER BY semester_number'
    )->fetchAll(PDO::FETCH_COLUMN);

    $subjectFilters = [];
    $subjectFilterParams = [];
    if ($selectedSubjectProgramId !== null) {
        $subjectFilters[] = 'c.program_id = :program_id';
        $subjectFilterParams['program_id'] = $selectedSubjectProgramId;
    }
    if ($selectedSubjectYearLevel !== null) {
        $subjectFilters[] = 'c.year_level = :year_level';
        $subjectFilterParams['year_level'] = $selectedSubjectYearLevel;
    }
    if ($selectedSubjectSemester === 'unspecified') {
        $subjectFilters[] = 'c.semester_number IS NULL AND c.curriculum_id IS NOT NULL';
    } elseif (is_int($selectedSubjectSemester)) {
        $subjectFilters[] = 'c.semester_number = :semester_number';
        $subjectFilterParams['semester_number'] = $selectedSubjectSemester;
    }
    $subjectWhere = $subjectFilters === [] ? '' : 'WHERE ' . implode(' AND ', $subjectFilters);
    $subjectCatalogQuery = $connection->prepare(
        "SELECT s.subject_id, s.subject_code, s.subject_name, s.description, s.units,
                s.subject_type, s.status, c.program_id, p.program_code, p.program_name,
                c.year_level, c.semester_number,
                MAX(c.is_required) AS is_required,
                COUNT(DISTINCT c.curriculum_id) AS curriculum_count,
                COUNT(DISTINCT CASE
                    WHEN ta.assignment_status = 'Active' AND sec.status = 'Active'
                    THEN sec.section_id
                END) AS class_count
         FROM subjects s
         LEFT JOIN curriculum c ON c.subject_id = s.subject_id
         LEFT JOIN programs p ON p.program_id = c.program_id
         LEFT JOIN sections sec
            ON sec.program_id = c.program_id
           AND sec.year_level = c.year_level
           AND sec.status = 'Active'
         LEFT JOIN teaching_assignments ta
            ON ta.subject_id = s.subject_id
           AND ta.section_id = sec.section_id
           AND ta.assignment_status = 'Active'
         {$subjectWhere}
         GROUP BY s.subject_id, s.subject_code, s.subject_name, s.description,
                  s.units, s.subject_type, s.status, c.program_id, p.program_code,
                  p.program_name, c.year_level, c.semester_number
         ORDER BY p.program_name, c.year_level, c.semester_number, s.subject_code"
    );
    $subjectCatalogQuery->execute($subjectFilterParams);

    $subjectsCatalog = [
        'programOptions' => $subjectProgramOptions,
        'yearOptions' => $subjectYearOptions,
        'semesterOptions' => $subjectSemesterOptions,
        'selectedProgramId' => $selectedSubjectProgramId,
        'selectedYearLevel' => $selectedSubjectYearLevel,
        'selectedSemester' => $selectedSubjectSemester,
        'rows' => $subjectCatalogQuery->fetchAll(),
    ];
}

$editingEntity = null;
$editingRecord = null;
$editToken = $_GET['edit'] ?? null;
if (is_string($editToken) && preg_match('/^([a-z_]+):([1-9][0-9]*)$/', $editToken, $editMatch) === 1) {
    $editingEntity = $entityDefinitions[$editMatch[1]] ?? null;
    if ($editingEntity !== null && $editingEntity['section'] === $sectionKey
        && ($sectionKey !== 'academic-management' || ($editingEntity['academicView'] ?? null) === $academicView)
        && ($sectionKey !== 'attendance-scheduling' || ($editingEntity['attendanceView'] ?? null) === $attendanceView)) {
        $editSql = "SELECT * FROM {$editingEntity['table']} WHERE {$editingEntity['primaryKey']} = :record_id";
        $editParams = ['record_id' => (int) $editMatch[2]];
        if ($editMatch[1] === 'curriculum' && $selectedCurriculumProgramId !== null) {
            $editSql .= ' AND program_id = :program_id';
            $editParams['program_id'] = $selectedCurriculumProgramId;
        }
        $editQuery = $connection->prepare(
            $editSql . ' LIMIT 1'
        );
        $editQuery->execute($editParams);
        $editingRecord = $editQuery->fetch();
    }
}

$reportData = [];
$academicOverview = null;
if ($sectionKey === 'academic-management') {
    $activeAcademicYear = academicActiveYear($connection);
    $selectedProgramId = filter_var(
        $_GET['program_id'] ?? $returnProgramId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $selectedProgramId = $selectedProgramId === false ? null : $selectedProgramId;
    if ($academicView === 'curriculum' && $selectedCurriculumProgramId !== null) {
        $selectedProgramId = $selectedCurriculumProgramId;
    }

    $curriculumProgramOptions = $connection->query(
        'SELECT p.program_id, p.program_code, p.program_name, p.status,
                COUNT(DISTINCT c.subject_id) AS subject_count,
                COUNT(DISTINCT c.year_level) AS year_level_count,
                COUNT(DISTINCT c.semester_number) AS semester_count
         FROM programs p
         LEFT JOIN curriculum c ON c.program_id = p.program_id
         GROUP BY p.program_id, p.program_code, p.program_name, p.status
         ORDER BY p.program_name'
    )->fetchAll();
    $curriculumPrograms = $curriculumProgramOptions;
    if ($selectedProgramId !== null) {
        $curriculumPrograms = array_values(array_filter(
            $curriculumPrograms,
            static fn (array $program): bool => (int) $program['program_id'] === $selectedProgramId
        ));
    }

    $curriculumQuery = $connection->prepare(
        'SELECT c.curriculum_id, c.program_id, c.subject_id, c.academic_year_id,
                c.year_level, c.semester_number, c.is_required,
                ay.year_name, ay.status AS academic_year_status, ay.start_date,
                su.subject_code, su.subject_name, su.description, su.units,
                su.subject_type, su.status AS subject_status
         FROM curriculum c
         INNER JOIN academic_years ay ON ay.academic_year_id = c.academic_year_id
         INNER JOIN subjects su ON su.subject_id = c.subject_id
         WHERE (:program_filter IS NULL OR c.program_id = :program_id)
         ORDER BY c.program_id, ay.start_date DESC, c.year_level,
                  c.semester_number, su.subject_code'
    );
    $curriculumQuery->execute([
        'program_filter' => $selectedProgramId,
        'program_id' => $selectedProgramId,
    ]);
    $curriculumRows = $curriculumQuery->fetchAll();

    if ($academicView === 'curriculum') {
        $academicOverview = [
            'activeYear' => $activeAcademicYear,
            'programOptions' => $curriculumProgramOptions,
            'programs' => $curriculumPrograms,
            'curriculumRows' => $curriculumRows,
            'selectedProgramId' => $selectedProgramId,
            'selectedCurriculumProgramId' => $selectedCurriculumProgramId,
        ];
    } else {
        $academicOverview = null;
    }
}

$attendanceOverview = null;
$scheduleOverview = null;
$teacherSchedule = null;
$studentSchedule = null;
$roomOverview = null;
$conflictOverview = null;
if ($sectionKey === 'attendance-scheduling') {
    $scheduleRows = $connection->query(
        "SELECT cs.schedule_id, cs.day_of_week, cs.start_time, cs.end_time,
                cs.schedule_status, cs.room_id, r.room_code, r.room_name,
                r.building, r.floor_number,
                ta.assignment_id, ta.teacher_id, ta.section_id,
                t.employee_number, t.first_name AS teacher_first_name,
                t.last_name AS teacher_last_name, t.department_id,
                d.department_name, su.subject_code, su.subject_name,
                se.section_name, se.year_level, se.program_id,
                p.program_code, p.program_name
         FROM class_schedules cs
         INNER JOIN teaching_assignments ta ON ta.assignment_id = cs.assignment_id
         INNER JOIN teachers t ON t.teacher_id = ta.teacher_id
         LEFT JOIN departments d ON d.department_id = t.department_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections se ON se.section_id = ta.section_id
         INNER JOIN academic_years ay ON ay.academic_year_id = se.academic_year_id
         LEFT JOIN programs p ON p.program_id = se.program_id
         INNER JOIN rooms r ON r.room_id = cs.room_id
         WHERE cs.schedule_status = 'Active'
           AND ta.assignment_status = 'Active'
           AND se.status = 'Active'
           AND ay.status = 'Active'
         ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                  cs.start_time, t.last_name, su.subject_code"
    )->fetchAll();

    $teacherOptions = $connection->query(
        "SELECT t.teacher_id, t.employee_number, t.first_name, t.last_name,
                t.department_id, d.department_name
         FROM teachers t
         LEFT JOIN departments d ON d.department_id = t.department_id
         WHERE t.employment_status = 'Active'
         ORDER BY t.last_name, t.first_name"
    )->fetchAll();
    $departmentOptions = $connection->query(
        'SELECT department_id, department_name FROM departments ORDER BY department_name'
    )->fetchAll();
    $roomOptions = $connection->query(
        "SELECT room_id, room_code, room_name, building, floor_number, room_type, capacity, status
         FROM rooms
         ORDER BY building, floor_number, room_code"
    )->fetchAll();
    $sectionOptions = $connection->query(
        "SELECT se.section_id, se.section_name, se.year_level, se.program_id,
                p.program_code, p.program_name, ay.year_name
         FROM sections se
         LEFT JOIN programs p ON p.program_id = se.program_id
         INNER JOIN academic_years ay ON ay.academic_year_id = se.academic_year_id
         WHERE se.status = 'Active'
           AND ay.status = 'Active'
         ORDER BY p.program_name, se.year_level, se.section_name"
    )->fetchAll();
    $attendanceAssignmentOptions = $connection->query(
        "SELECT ta.assignment_id,
                CONCAT(su.subject_code, ' — ', su.subject_name, ' · ', se.section_name,
                       ' · Year ', se.year_level, ' · ', t.first_name, ' ', t.last_name) AS label
         FROM teaching_assignments ta
         INNER JOIN teachers t ON t.teacher_id = ta.teacher_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections se ON se.section_id = ta.section_id
         INNER JOIN academic_years ay ON ay.academic_year_id = se.academic_year_id
         WHERE se.status = 'Active'
           AND ay.status = 'Active'
           AND ta.assignment_status = 'Active'
           AND t.employment_status = 'Active'
         ORDER BY su.subject_code, se.section_name, t.last_name"
    )->fetchAll();

    if ($attendanceView === 'attendance-overview') {
        $attendanceDate = $_GET['attendance_date'] ?? date('Y-m-d');
        if (!is_string($attendanceDate) || !$validDate($attendanceDate)) {
            $attendanceDate = date('Y-m-d');
        }
        $attendanceAssignmentId = filter_var(
            $_GET['assignment_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $attendanceAssignmentId = $attendanceAssignmentId === false ? null : $attendanceAssignmentId;
        if ($attendanceAssignmentId !== null && !in_array(
            $attendanceAssignmentId,
            array_map(static fn (array $assignment): int => (int) $assignment['assignment_id'], $attendanceAssignmentOptions),
            true
        )) {
            $attendanceAssignmentId = null;
        }
        $attendanceQuery = $connection->prepare(
            'SELECT a.attendance_id, a.attendance_date, a.attendance_status,
                    a.time_in, a.time_out, a.remarks,
                    s.student_number, s.first_name, s.last_name,
                    se.section_name, su.subject_code, su.subject_name,
                    t.first_name AS teacher_first_name, t.last_name AS teacher_last_name
             FROM attendance a
             INNER JOIN students s ON s.student_id = a.student_id
             LEFT JOIN enrollments e ON e.enrollment_id = a.enrollment_id
             LEFT JOIN sections se ON se.section_id = e.section_id
             INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
             INNER JOIN subjects su ON su.subject_id = ta.subject_id
             INNER JOIN teachers t ON t.teacher_id = ta.teacher_id
             WHERE a.attendance_date = :attendance_date
               AND (:assignment_filter IS NULL OR a.assignment_id = :assignment_id)
             ORDER BY se.section_name, s.last_name, s.first_name'
        );
        $attendanceQuery->execute([
            'attendance_date' => $attendanceDate,
            'assignment_filter' => $attendanceAssignmentId,
            'assignment_id' => $attendanceAssignmentId,
        ]);
        $attendanceRows = $attendanceQuery->fetchAll();
        $attendanceCounts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
        foreach ($attendanceRows as $attendanceRow) {
            if (isset($attendanceCounts[$attendanceRow['attendance_status']])) {
                $attendanceCounts[$attendanceRow['attendance_status']]++;
            }
        }
        $attendanceOverview = [
            'date' => $attendanceDate,
            'assignmentId' => $attendanceAssignmentId,
            'assignments' => $attendanceAssignmentOptions,
            'rows' => $attendanceRows,
            'counts' => $attendanceCounts,
        ];
        if (($_GET['export'] ?? null) === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="attendance-' . $attendanceDate . '.csv"');
            $csvOutput = fopen('php://output', 'wb');
            if ($csvOutput === false) {
                throw new RuntimeException('The attendance CSV export could not be opened.');
            }
            $csvSafeCell = static fn (string $value): string => preg_match('/^[\t\r ]*[=+\-@]/', $value) === 1
                ? "'" . $value
                : $value;
            fputcsv($csvOutput, ['Date', 'Student number', 'Student', 'Section', 'Subject', 'Teacher', 'Time in', 'Time out', 'Status']);
            foreach ($attendanceOverview['rows'] as $record) {
                fputcsv($csvOutput, array_map($csvSafeCell, [
                    $record['attendance_date'],
                    $record['student_number'],
                    $record['first_name'] . ' ' . $record['last_name'],
                    $record['section_name'] ?? '',
                    $record['subject_code'] . ' - ' . $record['subject_name'],
                    $record['teacher_first_name'] . ' ' . $record['teacher_last_name'],
                    $record['time_in'] ?? '',
                    $record['time_out'] ?? '',
                    $record['attendance_status'],
                ]));
            }
            fclose($csvOutput);
            exit;
        }
    } elseif ($attendanceView === 'teacher-schedule') {
        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $teacherFilterDepartmentId = filter_var(
            $_GET['department_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $teacherFilterDepartmentId = $teacherFilterDepartmentId === false ? null : $teacherFilterDepartmentId;
        if ($teacherFilterDepartmentId !== null && !in_array(
            $teacherFilterDepartmentId,
            array_map(static fn (array $department): int => (int) $department['department_id'], $departmentOptions),
            true
        )) {
            $teacherFilterDepartmentId = null;
        }
        $teacherFilterId = filter_var(
            $_GET['teacher_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $teacherFilterId = $teacherFilterId === false ? null : $teacherFilterId;
        if ($teacherFilterId !== null && !in_array(
            $teacherFilterId,
            array_map(static fn (array $teacher): int => (int) $teacher['teacher_id'], $teacherOptions),
            true
        )) {
            $teacherFilterId = null;
        }
        $teacherFilterDay = $_GET['day'] ?? 'All days';
        if (!is_string($teacherFilterDay) || ($teacherFilterDay !== 'All days' && !in_array($teacherFilterDay, $weekDays, true))) {
            $teacherFilterDay = 'All days';
        }
        $teacherSchedules = array_values(array_filter(
            $scheduleRows,
            static fn (array $schedule): bool =>
                ($teacherFilterDepartmentId === null || (int) $schedule['department_id'] === $teacherFilterDepartmentId)
                && ($teacherFilterId === null || (int) $schedule['teacher_id'] === $teacherFilterId)
                && ($teacherFilterDay === 'All days' || $schedule['day_of_week'] === $teacherFilterDay)
        ));
        $teacherSchedule = [
            'teachers' => $teacherOptions,
            'departments' => $departmentOptions,
            'schedules' => $teacherSchedules,
            'weekDays' => $weekDays,
            'departmentId' => $teacherFilterDepartmentId,
            'teacherId' => $teacherFilterId,
            'day' => $teacherFilterDay,
        ];
    } elseif ($attendanceView === 'student-schedule') {
        $programIds = array_values(array_unique(array_map(
            static fn (array $section): int => (int) ($section['program_id'] ?? 0),
            $sectionOptions
        )));
        $studentFilterProgramId = filter_var(
            $_GET['program_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $studentFilterProgramId = $studentFilterProgramId === false ? null : $studentFilterProgramId;
        if ($studentFilterProgramId !== null && !in_array($studentFilterProgramId, $programIds, true)) {
            $studentFilterProgramId = null;
        }
        $studentFilterYearLevel = $_GET['year_level'] ?? '';
        if (!is_string($studentFilterYearLevel) || !preg_match('/^[1-9][0-9]*$/', $studentFilterYearLevel)) {
            $studentFilterYearLevel = '';
        }
        $studentSections = array_values(array_filter(
            $sectionOptions,
            static fn (array $section): bool =>
                ($studentFilterProgramId === null || (int) $section['program_id'] === $studentFilterProgramId)
                && ($studentFilterYearLevel === '' || (int) $section['year_level'] === (int) $studentFilterYearLevel)
        ));
        $studentFilterSectionId = filter_var(
            $_GET['section_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $studentFilterSectionId = $studentFilterSectionId === false ? null : $studentFilterSectionId;
        if ($studentFilterSectionId !== null && !in_array(
            $studentFilterSectionId,
            array_map(static fn (array $section): int => (int) $section['section_id'], $studentSections),
            true
        )) {
            $studentFilterSectionId = null;
        }
        $studentFilterDay = $_GET['day'] ?? 'All days';
        if (!is_string($studentFilterDay) || ($studentFilterDay !== 'All days' && !in_array(
            $studentFilterDay,
            ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            true
        ))) {
            $studentFilterDay = 'All days';
        }
        $studentSchedules = array_values(array_filter(
            $scheduleRows,
            static fn (array $schedule): bool =>
                $studentFilterSectionId !== null
                && (int) $schedule['section_id'] === $studentFilterSectionId
                && ($studentFilterDay === 'All days' || $schedule['day_of_week'] === $studentFilterDay)
        ));
        $studentSchedule = [
            'sections' => $studentSections,
            'programs' => $connection->query(
                'SELECT program_id, program_code, program_name FROM programs ORDER BY program_name'
            )->fetchAll(),
            'allSections' => $sectionOptions,
            'schedules' => $studentSchedules,
            'programId' => $studentFilterProgramId,
            'yearLevel' => $studentFilterYearLevel,
            'sectionId' => $studentFilterSectionId,
            'day' => $studentFilterDay,
            'weekDays' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        ];
    } elseif ($attendanceView === 'room-monitoring') {
        $buildingOptions = array_values(array_unique(array_filter(array_map(
            static fn (array $room): string => (string) $room['building'],
            $roomOptions
        ))));
        $roomFilterBuilding = $_GET['building'] ?? 'All buildings';
        if (!is_string($roomFilterBuilding) || ($roomFilterBuilding !== 'All buildings' && !in_array(
            $roomFilterBuilding,
            $buildingOptions,
            true
        ))) {
            $roomFilterBuilding = 'All buildings';
        }
        $roomFloorOptions = array_values(array_unique(array_filter(array_map(
            static fn (array $room): string => (string) $room['floor_number'],
            $roomOptions
        ))));
        $roomFilterFloor = $_GET['floor'] ?? 'All floors';
        if (!is_string($roomFilterFloor) || ($roomFilterFloor !== 'All floors' && !in_array(
            $roomFilterFloor,
            $roomFloorOptions,
            true
        ))) {
            $roomFilterFloor = 'All floors';
        }
        $roomSearch = $_GET['q'] ?? '';
        $roomSearch = is_string($roomSearch) ? trim($roomSearch) : '';
        $currentDay = date('l');
        $currentTime = date('H:i:s');
        $currentOccupancy = [];
        foreach ($scheduleRows as $schedule) {
            if (
                $schedule['day_of_week'] === $currentDay
                && (string) $schedule['start_time'] <= $currentTime
                && (string) $schedule['end_time'] > $currentTime
            ) {
                $currentOccupancy[(int) $schedule['room_id']][] = $schedule;
            }
        }
        $roomCards = array_values(array_filter(
            $roomOptions,
            static function (array $room) use (
                $roomFilterBuilding,
                $roomFilterFloor,
                $roomSearch,
                $currentOccupancy
            ): bool {
                $matchesSearch = $roomSearch === ''
                    || stripos((string) $room['room_code'], $roomSearch) !== false
                    || stripos((string) $room['room_name'], $roomSearch) !== false
                    || stripos((string) $room['room_type'], $roomSearch) !== false;
                return ($roomFilterBuilding === 'All buildings' || $room['building'] === $roomFilterBuilding)
                    && ($roomFilterFloor === 'All floors' || (string) $room['floor_number'] === $roomFilterFloor)
                    && $matchesSearch;
            }
        ));
        $roomOverview = [
            'rooms' => $roomCards,
            'buildings' => $buildingOptions,
            'floors' => $roomFloorOptions,
            'occupancy' => $currentOccupancy,
            'building' => $roomFilterBuilding,
            'floor' => $roomFilterFloor,
            'search' => $roomSearch,
            'day' => $currentDay,
            'time' => $currentTime,
            'updatedAt' => date(DATE_ATOM),
        ];
    } elseif ($attendanceView === 'conflict-review') {
        $conflicts = [];
        $schedulesByDay = [];
        foreach ($scheduleRows as $schedule) {
            $schedulesByDay[$schedule['day_of_week']][] = $schedule;
        }
        foreach ($schedulesByDay as $daySchedules) {
            $scheduleCount = count($daySchedules);
            for ($leftIndex = 0; $leftIndex < $scheduleCount; $leftIndex++) {
                $left = $daySchedules[$leftIndex];
                for ($rightIndex = $leftIndex + 1; $rightIndex < $scheduleCount; $rightIndex++) {
                    $right = $daySchedules[$rightIndex];
                    if ((string) $right['start_time'] >= (string) $left['end_time']) {
                        break;
                    }
                    if (
                        (string) $left['start_time'] >= (string) $right['end_time']
                    ) {
                        continue;
                    }
                    $types = [];
                    if ((int) $left['teacher_id'] === (int) $right['teacher_id']) {
                        $types[] = 'Teacher';
                    }
                    if ((int) $left['room_id'] === (int) $right['room_id']) {
                        $types[] = 'Room';
                    }
                    if ((int) $left['section_id'] === (int) $right['section_id']) {
                        $types[] = 'Section';
                    }
                    if ($types !== []) {
                        $conflicts[] = ['types' => $types, 'left' => $left, 'right' => $right];
                    }
                }
            }
        }
        $conflictFilter = $_GET['type'] ?? 'All types';
        if (!is_string($conflictFilter) || !in_array($conflictFilter, ['All types', 'Teacher', 'Room', 'Section'], true)) {
            $conflictFilter = 'All types';
        }
        $filteredConflicts = array_values(array_filter(
            $conflicts,
            static fn (array $conflict): bool => $conflictFilter === 'All types'
                || in_array($conflictFilter, $conflict['types'], true)
        ));
        $conflictOverview = [
            'conflicts' => $filteredConflicts,
            'allConflicts' => $conflicts,
            'schedules' => $scheduleRows,
            'filter' => $conflictFilter,
        ];
    }
}

if ($sectionKey === 'reports') {
    foreach ([
        'students' => 'SELECT COUNT(*) FROM students',
        'teachers' => 'SELECT COUNT(*) FROM teachers',
        'enrollments' => 'SELECT COUNT(*) FROM enrollments',
        'attendance' => 'SELECT COUNT(*) FROM attendance',
        'assessments' => 'SELECT COUNT(*) FROM assessments',
        'payments' => "SELECT COUNT(*) FROM payments WHERE payment_status = 'Completed'",
        'collections' => "SELECT COALESCE(SUM(total_amount), 0) FROM payments WHERE payment_status = 'Completed'",
    ] as $key => $query) {
        $reportData[$key] = $connection->query($query)->fetchColumn();
    }
    $reportData['generatedReports'] = $connection->query(
        'SELECT report_id, report_name, report_type, generated_at FROM generated_reports ORDER BY generated_at DESC LIMIT 100'
    )->fetchAll();
}

$adminNavigationActive = $sectionKey;
$dashboard = [
    'theme' => 'admin',
    'roleLabel' => 'Admin',
    'pageTitle' => $sectionKey === 'academic-management'
        ? $academicSubnav[$academicView]['label']
        : ($sectionKey === 'attendance-scheduling' ? $attendanceSubnav[$attendanceView]['label'] : $sectionDefinition['title']),
    'eyebrow' => $sectionKey === 'academic-management'
        ? ($academicView === 'subjects' ? 'Academic catalog' : 'Academic structure')
        : ($sectionKey === 'attendance-scheduling' ? 'Attendance & scheduling' : $sectionDefinition['eyebrow']),
    'heading' => $sectionKey === 'academic-management'
        ? $academicSubnav[$academicView]['label']
        : ($sectionKey === 'attendance-scheduling' ? $attendanceSubnav[$attendanceView]['label'] : $sectionDefinition['title']),
    'description' => $sectionKey === 'academic-management'
        ? $academicSubnav[$academicView]['description']
        : ($sectionKey === 'attendance-scheduling' ? $attendanceSubnav[$attendanceView]['description'] : $sectionDefinition['description']),
    'metricsHeading' => '',
    'navItems' => adminNavigationItems($adminNavigationActive),
    'academicSubnav' => $sectionKey === 'academic-management'
        ? array_map(
            static fn (string $key, array $item): array => [
                'key' => $key,
                'label' => $item['label'],
                'href' => 'admin-section.php?section=academic-management&academic_view=' . rawurlencode($key),
                'current' => $key === $academicView,
            ],
            array_keys($academicSubnav),
            array_values($academicSubnav)
        )
        : [],
    'attendanceSubnav' => $sectionKey === 'attendance-scheduling'
        ? array_map(
            static fn (string $key, array $item): array => [
                'key' => $key,
                'label' => $item['label'],
                'href' => 'admin-section.php?section=attendance-scheduling&attendance_view=' . rawurlencode($key),
                'current' => $key === $attendanceView,
            ],
            array_keys($attendanceSubnav),
            array_values($attendanceSubnav)
        )
        : [],
    'teacherSubnav' => $sectionKey === 'teachers'
        ? adminTeacherSubnavItems('directory')
        : [],
    'navigationStyle' => 'admin',
    'sidebarTitle' => 'Admin Panel',
    'sidebarCaption' => 'School Operations',
    'homeHref' => 'admin-dashboard.php',
    'sectionContent' => true,
    'sectionContentTemplate' => 'admin_section_content.php',
];

require dirname(__DIR__) . '/app/Shared/dashboard_layout.php';
