<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/config/database.php';

function demoEnsureRecord(
    PDO $connection,
    string $table,
    string $idColumn,
    array $identity,
    array $values,
    array &$stats
): int {
    foreach ([$table, $idColumn, ...array_keys($identity), ...array_keys($values)] as $identifier) {
        if (!preg_match('/\A[a-z_]+\z/i', $identifier)) {
            throw new InvalidArgumentException('Invalid demo seed database identifier.');
        }
    }

    $conditions = [];
    $parameters = [];
    foreach ($identity as $column => $value) {
        if ($value === null) {
            $conditions[] = sprintf('`%s` IS NULL', $column);
            continue;
        }

        $parameter = 'identity_' . $column;
        $conditions[] = sprintf('`%s` = :%s', $column, $parameter);
        $parameters[$parameter] = $value;
    }

    $lookup = $connection->prepare(sprintf(
        'SELECT `%s` FROM `%s` WHERE %s LIMIT 1',
        $idColumn,
        $table,
        implode(' AND ', $conditions)
    ));
    $lookup->execute($parameters);
    $existingId = $lookup->fetchColumn();
    if ($existingId !== false) {
        $stats['reused'][$table] = ($stats['reused'][$table] ?? 0) + 1;
        return (int) $existingId;
    }

    $columns = array_keys($values);
    $placeholders = array_map(static fn (string $column): string => ':value_' . $column, $columns);
    $insert = $connection->prepare(sprintf(
        'INSERT INTO `%s` (%s) VALUES (%s)',
        $table,
        implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', $columns)),
        implode(', ', $placeholders)
    ));

    $parameters = [];
    foreach ($values as $column => $value) {
        $parameters['value_' . $column] = $value;
    }
    $insert->execute($parameters);

    $stats['inserted'][$table] = ($stats['inserted'][$table] ?? 0) + 1;
    return (int) $connection->lastInsertId();
}

function demoStatusForDates(
    DateTimeImmutable $today,
    DateTimeImmutable $start,
    DateTimeImmutable $end
): string {
    if ($today < $start) {
        return 'Upcoming';
    }

    return $today > $end ? 'Completed' : 'Active';
}

function demoClampDate(
    DateTimeImmutable $date,
    DateTimeImmutable $minimum,
    DateTimeImmutable $maximum
): DateTimeImmutable {
    if ($date < $minimum) {
        return $minimum;
    }

    return $date > $maximum ? $maximum : $date;
}

function demoAccountForRole(PDO $connection, string $roleName): array
{
    $statement = $connection->prepare(
        "SELECT u.user_id, u.first_name, u.last_name, u.email
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE r.role_name = :role_name
           AND u.account_status = 'Active'
         ORDER BY u.user_id
         LIMIT 1"
    );
    $statement->execute(['role_name' => $roleName]);
    $account = $statement->fetch();
    if ($account === false) {
        throw new RuntimeException(sprintf('An active %s demo account is required.', $roleName));
    }

    return $account;
}

$dryRun = in_array('--dry-run', $argv, true);
$unknownArguments = array_values(array_filter(
    array_slice($argv, 1),
    static fn (string $argument): bool => $argument !== '--dry-run'
));
if ($unknownArguments !== []) {
    fwrite(STDERR, "Usage: php database/seeds/demo_data.php [--dry-run]\n");
    exit(2);
}

$connection = getDatabaseConnection();
$stats = ['inserted' => [], 'reused' => []];

try {
    $connection->beginTransaction();

    $adminAccount = demoAccountForRole($connection, 'Admin');
    $teacherAccount = demoAccountForRole($connection, 'Teacher');
    $studentAccount = demoAccountForRole($connection, 'Student');
    $today = new DateTimeImmutable('today');

    $academicStartYear = (int) $today->format('Y');
    if ((int) $today->format('n') < 6) {
        $academicStartYear--;
    }
    $demoYearName = sprintf('%d-%d Demo', $academicStartYear, $academicStartYear + 1);
    $yearLookup = $connection->prepare(
        'SELECT academic_year_id, year_name, start_date, end_date, status
         FROM academic_years
         WHERE year_name = :year_name
         LIMIT 1'
    );
    $yearLookup->execute(['year_name' => $demoYearName]);
    $academicYear = $yearLookup->fetch();

    if ($academicYear === false) {
        $activeYear = $connection->query(
            "SELECT academic_year_id, year_name, start_date, end_date, status
             FROM academic_years
             WHERE status = 'Active'
             ORDER BY academic_year_id
             LIMIT 1"
        )->fetch();

        if ($activeYear !== false) {
            $academicYear = $activeYear;
        } else {
            $start = new DateTimeImmutable(sprintf('%d-08-01', $academicStartYear));
            $end = new DateTimeImmutable(sprintf('%d-05-31', $academicStartYear + 1));
            $academicYearId = demoEnsureRecord(
                $connection,
                'academic_years',
                'academic_year_id',
                ['year_name' => $demoYearName],
                [
                    'year_name' => $demoYearName,
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d'),
                    'status' => 'Active',
                ],
                $stats
            );
            $academicYear = [
                'academic_year_id' => $academicYearId,
                'year_name' => $demoYearName,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'status' => 'Active',
            ];
        }
    }

    $academicYearId = (int) $academicYear['academic_year_id'];
    $yearStart = new DateTimeImmutable($academicYear['start_date']);
    $yearEnd = new DateTimeImmutable($academicYear['end_date']);

    $computingDepartmentId = demoEnsureRecord(
        $connection,
        'departments',
        'department_id',
        ['department_code' => 'DEMO-COMPUTING'],
        [
            'department_code' => 'DEMO-COMPUTING',
            'department_name' => 'Demo Department of Computing',
            'description' => 'Fictional department records for evaluating academic workflows.',
        ],
        $stats
    );
    $educationDepartmentId = demoEnsureRecord(
        $connection,
        'departments',
        'department_id',
        ['department_code' => 'DEMO-EDUCATION'],
        [
            'department_code' => 'DEMO-EDUCATION',
            'department_name' => 'Demo Department of Education',
            'description' => 'Fictional department records for evaluating academic workflows.',
        ],
        $stats
    );

    $programs = [];
    foreach ([
        [
            'code' => 'DEMO-BSIT',
            'name' => 'Demo Bachelor of Science in Information Technology',
            'department_id' => $computingDepartmentId,
            'description' => 'Sample four-year computing program.',
        ],
        [
            'code' => 'DEMO-BSED',
            'name' => 'Demo Bachelor of Secondary Education',
            'department_id' => $educationDepartmentId,
            'description' => 'Sample four-year education program.',
        ],
    ] as $program) {
        $programs[$program['code']] = demoEnsureRecord(
            $connection,
            'programs',
            'program_id',
            ['program_code' => $program['code']],
            [
                'department_id' => $program['department_id'],
                'program_code' => $program['code'],
                'program_name' => $program['name'],
                'description' => $program['description'],
                'duration_years' => 4,
                'status' => 'Active',
            ],
            $stats
        );
    }

    $subjects = [];
    $subjectDefinitions = [
        ['code' => 'DEMO-IT101', 'name' => 'Demo Introduction to Programming', 'program' => 'DEMO-BSIT', 'type' => 'Major'],
        ['code' => 'DEMO-IT102', 'name' => 'Demo Data Management', 'program' => 'DEMO-BSIT', 'type' => 'Major'],
        ['code' => 'DEMO-IT103', 'name' => 'Demo Networking Fundamentals', 'program' => 'DEMO-BSIT', 'type' => 'Major'],
        ['code' => 'DEMO-IT104', 'name' => 'Demo Communication Skills', 'program' => 'DEMO-BSIT', 'type' => 'Core'],
        ['code' => 'DEMO-ED101', 'name' => 'Demo Foundations of Education', 'program' => 'DEMO-BSED', 'type' => 'Major'],
        ['code' => 'DEMO-ED102', 'name' => 'Demo Learner Development', 'program' => 'DEMO-BSED', 'type' => 'Major'],
        ['code' => 'DEMO-ED103', 'name' => 'Demo Assessment Principles', 'program' => 'DEMO-BSED', 'type' => 'Major'],
        ['code' => 'DEMO-ED104', 'name' => 'Demo Teaching Communication', 'program' => 'DEMO-BSED', 'type' => 'Core'],
    ];

    foreach ($subjectDefinitions as $subject) {
        $subjects[$subject['code']] = demoEnsureRecord(
            $connection,
            'subjects',
            'subject_id',
            ['subject_code' => $subject['code']],
            [
                'subject_code' => $subject['code'],
                'subject_name' => $subject['name'],
                'description' => 'Fictional subject used to demonstrate curriculum and grading workflows.',
                'units' => '3.00',
                'subject_type' => $subject['type'],
                'status' => 'Active',
            ],
            $stats
        );

        demoEnsureRecord(
            $connection,
            'curriculum',
            'curriculum_id',
            [
                'program_id' => $programs[$subject['program']],
                'subject_id' => $subjects[$subject['code']],
                'academic_year_id' => $academicYearId,
                'year_level' => 1,
                'semester_number' => 1,
            ],
            [
                'program_id' => $programs[$subject['program']],
                'subject_id' => $subjects[$subject['code']],
                'academic_year_id' => $academicYearId,
                'year_level' => 1,
                'semester_number' => 1,
                'is_required' => 1,
            ],
            $stats
        );
    }

    $firstSemesterEnd = demoClampDate($yearStart->modify('+5 months -1 day'), $yearStart, $yearEnd);
    $secondSemesterStart = $firstSemesterEnd->modify('+1 day');
    $semesters = [];
    foreach ([
        ['name' => '1st Semester', 'start' => $yearStart, 'end' => $firstSemesterEnd],
        ['name' => '2nd Semester', 'start' => $secondSemesterStart, 'end' => $yearEnd],
    ] as $semester) {
        $semesters[$semester['name']] = demoEnsureRecord(
            $connection,
            'semesters',
            'semester_id',
            ['academic_year_id' => $academicYearId, 'semester_name' => $semester['name']],
            [
                'academic_year_id' => $academicYearId,
                'semester_name' => $semester['name'],
                'start_date' => $semester['start']->format('Y-m-d'),
                'end_date' => $semester['end']->format('Y-m-d'),
                'status' => demoStatusForDates($today, $semester['start'], $semester['end']),
            ],
            $stats
        );
    }

    $rooms = [];
    foreach ([
        ['code' => 'DEMO-LAB-01', 'name' => 'Demo Computing Lab', 'building' => 'Demo Learning Center', 'type' => 'Computer Laboratory'],
        ['code' => 'DEMO-ROOM-01', 'name' => 'Demo Classroom 101', 'building' => 'Demo Learning Center', 'type' => 'Classroom'],
    ] as $room) {
        $rooms[$room['code']] = demoEnsureRecord(
            $connection,
            'rooms',
            'room_id',
            ['room_code' => $room['code']],
            [
                'room_code' => $room['code'],
                'room_name' => $room['name'],
                'building' => $room['building'],
                'floor_number' => 1,
                'room_type' => $room['type'],
                'capacity' => 30,
                'status' => 'Available',
            ],
            $stats
        );
    }

    $teacherProfile = $connection->prepare('SELECT teacher_id FROM teachers WHERE user_id = :user_id LIMIT 1');
    $teacherProfile->execute(['user_id' => (int) $teacherAccount['user_id']]);
    $existingTeacherId = $teacherProfile->fetchColumn();
    $teachers = [
        'primary' => $existingTeacherId !== false
            ? (int) $existingTeacherId
            : demoEnsureRecord(
                $connection,
                'teachers',
                'teacher_id',
                ['user_id' => (int) $teacherAccount['user_id']],
                [
                    'user_id' => (int) $teacherAccount['user_id'],
                    'employee_number' => 'DEMO-EMP-001',
                    'first_name' => $teacherAccount['first_name'],
                    'last_name' => $teacherAccount['last_name'],
                    'email' => $teacherAccount['email'],
                    'phone' => null,
                    'department_id' => $computingDepartmentId,
                    'specialization' => 'Software Engineering',
                    'employment_status' => 'Active',
                    'hire_date' => $yearStart->format('Y-m-d'),
                ],
                $stats
            ),
        'secondary' => demoEnsureRecord(
            $connection,
            'teachers',
            'teacher_id',
            ['employee_number' => 'DEMO-EMP-002'],
            [
                'user_id' => null,
                'employee_number' => 'DEMO-EMP-002',
                'first_name' => 'Taylor',
                'last_name' => 'Morgan',
                'email' => 'taylor.morgan@example.test',
                'phone' => null,
                'department_id' => $educationDepartmentId,
                'specialization' => 'Teacher Education',
                'employment_status' => 'Active',
                'hire_date' => $yearStart->format('Y-m-d'),
            ],
            $stats
        ),
    ];

    $sectionDefinitions = [
        ['name' => 'DEMO-BSIT-1A', 'program' => 'DEMO-BSIT'],
        ['name' => 'DEMO-BSED-1A', 'program' => 'DEMO-BSED'],
    ];
    $sections = [];
    foreach ($sectionDefinitions as $section) {
        $sections[$section['name']] = demoEnsureRecord(
            $connection,
            'sections',
            'section_id',
            ['academic_year_id' => $academicYearId, 'section_name' => $section['name']],
            [
                'program_id' => $programs[$section['program']],
                'academic_year_id' => $academicYearId,
                'section_name' => $section['name'],
                'year_level' => 1,
                'capacity' => 30,
                'status' => 'Active',
            ],
            $stats
        );
    }

    $assignmentDefinitions = [
        ['key' => 'programming', 'teacher' => 'primary', 'subject' => 'DEMO-IT101', 'section' => 'DEMO-BSIT-1A', 'day' => 'Monday', 'room' => 'DEMO-LAB-01', 'start' => '08:00:00', 'end' => '09:30:00'],
        ['key' => 'data', 'teacher' => 'primary', 'subject' => 'DEMO-IT102', 'section' => 'DEMO-BSIT-1A', 'day' => 'Tuesday', 'room' => 'DEMO-LAB-01', 'start' => '08:00:00', 'end' => '09:30:00'],
        ['key' => 'networking', 'teacher' => 'primary', 'subject' => 'DEMO-IT103', 'section' => 'DEMO-BSIT-1A', 'day' => 'Thursday', 'room' => 'DEMO-LAB-01', 'start' => '08:00:00', 'end' => '09:30:00'],
        ['key' => 'education', 'teacher' => 'secondary', 'subject' => 'DEMO-ED101', 'section' => 'DEMO-BSED-1A', 'day' => 'Friday', 'room' => 'DEMO-ROOM-01', 'start' => '09:00:00', 'end' => '10:30:00'],
    ];
    $assignments = [];
    foreach ($assignmentDefinitions as $assignment) {
        $assignments[$assignment['key']] = demoEnsureRecord(
            $connection,
            'teaching_assignments',
            'assignment_id',
            [
                'teacher_id' => $teachers[$assignment['teacher']],
                'subject_id' => $subjects[$assignment['subject']],
                'section_id' => $sections[$assignment['section']],
                'semester_id' => $semesters['1st Semester'],
            ],
            [
                'teacher_id' => $teachers[$assignment['teacher']],
                'subject_id' => $subjects[$assignment['subject']],
                'section_id' => $sections[$assignment['section']],
                'semester_id' => $semesters['1st Semester'],
                'assignment_status' => 'Active',
            ],
            $stats
        );

        demoEnsureRecord(
            $connection,
            'class_schedules',
            'schedule_id',
            [
                'assignment_id' => $assignments[$assignment['key']],
                'day_of_week' => $assignment['day'],
                'start_time' => $assignment['start'],
            ],
            [
                'assignment_id' => $assignments[$assignment['key']],
                'room_id' => $rooms[$assignment['room']],
                'day_of_week' => $assignment['day'],
                'start_time' => $assignment['start'],
                'end_time' => $assignment['end'],
                'schedule_status' => 'Active',
            ],
            $stats
        );
    }

    $studentProfile = $connection->prepare('SELECT student_id FROM students WHERE user_id = :user_id LIMIT 1');
    $studentProfile->execute(['user_id' => (int) $studentAccount['user_id']]);
    $existingStudentId = $studentProfile->fetchColumn();
    $studentDefinitions = [
        ['first_name' => $studentAccount['first_name'], 'last_name' => $studentAccount['last_name'], 'email' => $studentAccount['email'], 'user_id' => (int) $studentAccount['user_id']],
        ['first_name' => 'Jordan', 'last_name' => 'Reyes', 'email' => 'jordan.reyes@example.test', 'user_id' => null],
        ['first_name' => 'Maya', 'last_name' => 'Santos', 'email' => 'maya.santos@example.test', 'user_id' => null],
        ['first_name' => 'Eli', 'last_name' => 'Navarro', 'email' => 'eli.navarro@example.test', 'user_id' => null],
        ['first_name' => 'Sofia', 'last_name' => 'Cruz', 'email' => 'sofia.cruz@example.test', 'user_id' => null],
    ];
    $students = [];
    foreach ($studentDefinitions as $index => $student) {
        if ($index === 0 && $existingStudentId !== false) {
            $students[] = (int) $existingStudentId;
            $stats['reused']['students'] = ($stats['reused']['students'] ?? 0) + 1;
            continue;
        }

        $studentNumber = sprintf('DEMO-STU-%d-%03d', $academicStartYear, $index + 1);
        $studentId = demoEnsureRecord(
            $connection,
            'students',
            'student_id',
            $student['user_id'] === null
                ? ['student_number' => $studentNumber]
                : ['user_id' => $student['user_id']],
            [
                'user_id' => $student['user_id'],
                'desired_program_id' => $programs['DEMO-BSIT'],
                'student_number' => $studentNumber,
                'first_name' => $student['first_name'],
                'middle_name' => null,
                'last_name' => $student['last_name'],
                'birth_date' => sprintf('%d-05-%02d', $academicStartYear - 19, 10 + $index),
                'gender' => ['Other', 'Male', 'Female', 'Male', 'Female'][$index],
                'civil_status' => 'Single',
                'email' => $student['email'],
                'phone' => null,
                'alternate_phone' => null,
                'address' => 'Demo address, fictional sample record',
                'mailing_address' => null,
                'city' => 'Demo City',
                'province' => 'Demo Province',
                'profile_image' => null,
                'admission_date' => $yearStart->format('Y-m-d'),
                'student_status' => 'Enrolled',
            ],
            $stats
        );
        $students[] = $studentId;
    }

    $guardianNames = [
        ['first_name' => 'Alex', 'last_name' => 'Reyes'],
        ['first_name' => 'Jamie', 'last_name' => 'Santos'],
        ['first_name' => 'Robin', 'last_name' => 'Navarro'],
        ['first_name' => 'Casey', 'last_name' => 'Cruz'],
        ['first_name' => 'Sam', 'last_name' => 'Demo'],
    ];
    $guardians = [];
    foreach ($guardianNames as $index => $guardian) {
        $email = sprintf('demo.guardian.%d@example.test', $index + 1);
        $guardians[$index] = demoEnsureRecord(
            $connection,
            'guardians',
            'guardian_id',
            ['email' => $email],
            [
                'first_name' => $guardian['first_name'],
                'last_name' => $guardian['last_name'],
                'email' => $email,
                'phone' => '000-000-0000',
                'occupation' => 'Demo contact',
                'address' => 'Demo address, fictional sample record',
            ],
            $stats
        );

        demoEnsureRecord(
            $connection,
            'student_guardians',
            'student_guardian_id',
            ['student_id' => $students[$index], 'guardian_id' => $guardians[$index]],
            [
                'student_id' => $students[$index],
                'guardian_id' => $guardians[$index],
                'relationship' => 'Guardian',
                'is_primary' => 1,
                'emergency_contact' => 1,
            ],
            $stats
        );
    }

    $enrollmentDate = demoClampDate($today, $yearStart, $yearEnd)->format('Y-m-d');
    $enrollments = [];
    foreach ($students as $studentId) {
        $enrollments[] = demoEnsureRecord(
            $connection,
            'enrollments',
            'enrollment_id',
            ['student_id' => $studentId, 'academic_year_id' => $academicYearId],
            [
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'program_id' => $programs['DEMO-BSIT'],
                'section_id' => $sections['DEMO-BSIT-1A'],
                'year_level' => 1,
                'enrollment_date' => $enrollmentDate,
                'enrollment_status' => 'Enrolled',
                'remarks' => 'Fictional demo enrollment for workflow review.',
            ],
            $stats
        );
    }

    $periodBoundaries = [
        ['name' => 'Demo Prelim Period', 'start' => $yearStart, 'end' => demoClampDate($yearStart->modify('+2 months -1 day'), $yearStart, $yearEnd)],
    ];
    $periodBoundaries[] = [
        'name' => 'Demo Midterm Period',
        'start' => $periodBoundaries[0]['end']->modify('+1 day'),
        'end' => demoClampDate($periodBoundaries[0]['end']->modify('+4 months'), $yearStart, $yearEnd),
    ];
    $periodBoundaries[] = [
        'name' => 'Demo Final Period',
        'start' => $periodBoundaries[1]['end']->modify('+1 day'),
        'end' => $yearEnd,
    ];

    $gradingPeriods = [];
    foreach ($periodBoundaries as $period) {
        if ($period['start'] > $yearEnd) {
            continue;
        }
        $gradingPeriods[$period['name']] = demoEnsureRecord(
            $connection,
            'grading_periods',
            'grading_period_id',
            [
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesters['1st Semester'],
                'period_name' => $period['name'],
            ],
            [
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesters['1st Semester'],
                'period_name' => $period['name'],
                'start_date' => $period['start']->format('Y-m-d'),
                'end_date' => $period['end']->format('Y-m-d'),
                'status' => demoStatusForDates($today, $period['start'], $period['end']),
            ],
            $stats
        );
    }

    $activePeriodName = null;
    foreach ($periodBoundaries as $period) {
        if ($period['start'] <= $today && $period['end'] >= $today && isset($gradingPeriods[$period['name']])) {
            $activePeriodName = $period['name'];
            break;
        }
    }
    if ($activePeriodName === null) {
        $activePeriodName = $today < $yearStart
            ? array_key_first($gradingPeriods)
            : array_key_last($gradingPeriods);
    }
    $gradingPeriodId = $gradingPeriods[$activePeriodName];
    $selectedPeriod = array_values(array_filter(
        $periodBoundaries,
        static fn (array $period): bool => $period['name'] === $activePeriodName
    ))[0];

    $categories = [];
    foreach (['Quiz', 'Exam', 'Project'] as $categoryName) {
        $categories[$categoryName] = demoEnsureRecord(
            $connection,
            'assessment_categories',
            'category_id',
            ['category_name' => 'Demo ' . $categoryName],
            [
                'category_name' => 'Demo ' . $categoryName,
                'description' => 'Fictional assessment category for demo records.',
                'default_weight' => '100.00',
            ],
            $stats
        );
    }

    $scoreBases = [
        'Quiz' => [18, 15, 19, 14, 17],
        'Exam' => [44, 39, 47, 36, 42],
        'Project' => [92, 84, 96, 78, 88],
    ];
    $pointTotals = ['Quiz' => 20, 'Exam' => 50, 'Project' => 100];
    $assignmentGrades = [];
    $assessmentDate = demoClampDate($today->modify('-7 days'), $selectedPeriod['start'], $selectedPeriod['end']);

    foreach (['programming', 'data', 'networking'] as $assignmentIndex => $assignmentKey) {
        $assignmentId = $assignments[$assignmentKey];
        $percentageByStudent = array_fill(0, count($students), []);
        foreach (['Quiz', 'Exam', 'Project'] as $assessmentType) {
            $title = sprintf('DEMO %s %s', ucfirst($assignmentKey), $assessmentType);
            $assessmentId = demoEnsureRecord(
                $connection,
                'assessments',
                'assessment_id',
                [
                    'assignment_id' => $assignmentId,
                    'grading_period_id' => $gradingPeriodId,
                    'assessment_title' => $title,
                ],
                [
                    'assignment_id' => $assignmentId,
                    'grading_period_id' => $gradingPeriodId,
                    'category_id' => $categories[$assessmentType],
                    'assessment_title' => $title,
                    'assessment_type' => $assessmentType,
                    'description' => 'Fictional completed assessment for testing grade entry and summaries.',
                    'total_points' => (string) $pointTotals[$assessmentType],
                    'assessment_date' => $assessmentDate->format('Y-m-d'),
                    'due_date' => $assessmentDate->format('Y-m-d'),
                    'status' => 'Completed',
                ],
                $stats
            );

            foreach ($students as $studentIndex => $studentId) {
                $variation = ($assignmentIndex + $studentIndex) % 3;
                $score = min(
                    $pointTotals[$assessmentType],
                    $scoreBases[$assessmentType][$studentIndex] + $variation
                );
                $percentageByStudent[$studentIndex][] = $score / $pointTotals[$assessmentType] * 100;
                demoEnsureRecord(
                    $connection,
                    'student_scores',
                    'score_id',
                    ['assessment_id' => $assessmentId, 'student_id' => $studentId],
                    [
                        'assessment_id' => $assessmentId,
                        'student_id' => $studentId,
                        'score' => (string) $score,
                        'remarks' => $score / $pointTotals[$assessmentType] >= 0.9 ? 'Strong demo result.' : 'Recorded demo result.',
                        'recorded_by' => (int) $teacherAccount['user_id'],
                    ],
                    $stats
                );
            }
        }

        foreach ($students as $studentIndex => $studentId) {
            $grade = array_sum($percentageByStudent[$studentIndex]) / count($percentageByStudent[$studentIndex]);
            $assignmentGrades[$studentIndex][] = $grade;
            demoEnsureRecord(
                $connection,
                'grade_records',
                'grade_id',
                [
                    'enrollment_id' => $enrollments[$studentIndex],
                    'assignment_id' => $assignmentId,
                    'grading_period_id' => $gradingPeriodId,
                ],
                [
                    'enrollment_id' => $enrollments[$studentIndex],
                    'assignment_id' => $assignmentId,
                    'grading_period_id' => $gradingPeriodId,
                    'preliminary_grade' => number_format($grade, 2, '.', ''),
                    'final_grade' => number_format($grade, 2, '.', ''),
                    'remarks' => $grade >= 75 ? 'Passed' : 'Failed',
                    'encoded_by' => (int) $teacherAccount['user_id'],
                ],
                $stats
            );
        }
    }

    foreach ($students as $studentIndex => $studentId) {
        $average = array_sum($assignmentGrades[$studentIndex]) / count($assignmentGrades[$studentIndex]);
        demoEnsureRecord(
            $connection,
            'report_cards',
            'report_card_id',
            ['enrollment_id' => $enrollments[$studentIndex], 'grading_period_id' => $gradingPeriodId],
            [
                'enrollment_id' => $enrollments[$studentIndex],
                'grading_period_id' => $gradingPeriodId,
                'general_average' => number_format($average, 2, '.', ''),
                'attendance_summary' => 'Fictional demo attendance is recorded for recent class dates.',
                'teacher_remarks' => 'Sample report card for workflow review.',
                'generated_by' => (int) $teacherAccount['user_id'],
                'status' => 'Published',
            ],
            $stats
        );
    }

    $attendanceDates = [];
    for ($date = $today; $date >= $yearStart && count($attendanceDates) < 2; $date = $date->modify('-1 day')) {
        if ((int) $date->format('N') <= 5 && $date <= $yearEnd) {
            $attendanceDates[] = $date;
        }
    }
    if ($attendanceDates === []) {
        $attendanceDates[] = $yearStart;
    }
    $attendanceStatuses = [
        ['Present', 'Present', 'Late', 'Absent', 'Present'],
        ['Present', 'Excused', 'Present', 'Present', 'Late'],
    ];
    foreach ($attendanceDates as $dateIndex => $date) {
        foreach ($students as $studentIndex => $studentId) {
            $status = $attendanceStatuses[$dateIndex % count($attendanceStatuses)][$studentIndex];
            demoEnsureRecord(
                $connection,
                'attendance',
                'attendance_id',
                [
                    'student_id' => $studentId,
                    'assignment_id' => $assignments['programming'],
                    'attendance_date' => $date->format('Y-m-d'),
                ],
                [
                    'student_id' => $studentId,
                    'enrollment_id' => $enrollments[$studentIndex],
                    'assignment_id' => $assignments['programming'],
                    'attendance_date' => $date->format('Y-m-d'),
                    'time_in' => in_array($status, ['Present', 'Late'], true) ? ($status === 'Late' ? '08:12:00' : '07:55:00') : null,
                    'time_out' => in_array($status, ['Present', 'Late'], true) ? '09:30:00' : null,
                    'attendance_status' => $status,
                    'remarks' => 'Fictional sample attendance record.',
                    'recorded_by' => (int) $adminAccount['user_id'],
                ],
                $stats
            );
        }
    }

    demoEnsureRecord(
        $connection,
        'teacher_attendance',
        'teacher_attendance_id',
        ['teacher_id' => $teachers['primary'], 'attendance_date' => $today->format('Y-m-d')],
        [
            'teacher_id' => $teachers['primary'],
            'attendance_date' => $today->format('Y-m-d'),
            'time_in' => '07:45:00',
            'time_out' => '16:00:00',
            'attendance_status' => 'Present',
            'remarks' => 'Fictional demo attendance record.',
        ],
        $stats
    );
    demoEnsureRecord(
        $connection,
        'teacher_performance_reviews',
        'review_id',
        [
            'teacher_id' => $teachers['primary'],
            'review_period' => $academicYear['year_name'],
        ],
        [
            'teacher_id' => $teachers['primary'],
            'reviewer_user_id' => (int) $adminAccount['user_id'],
            'review_date' => $today->modify('-14 days')->format('Y-m-d'),
            'review_period' => $academicYear['year_name'],
            'rating' => 4,
            'strengths' => 'Clear lesson structure and supportive classroom practice.',
            'improvement_areas' => 'Continue building opportunities for student-led projects.',
            'notes' => 'Fictional performance review for workflow testing.',
        ],
        $stats
    );
    demoEnsureRecord(
        $connection,
        'teacher_leave_requests',
        'leave_request_id',
        [
            'teacher_id' => $teachers['primary'],
            'start_date' => $today->modify('+14 days')->format('Y-m-d'),
            'end_date' => $today->modify('+15 days')->format('Y-m-d'),
        ],
        [
            'teacher_id' => $teachers['primary'],
            'leave_type' => 'Personal',
            'start_date' => $today->modify('+14 days')->format('Y-m-d'),
            'end_date' => $today->modify('+15 days')->format('Y-m-d'),
            'reason' => 'Fictional request to demonstrate the leave review workflow.',
            'request_status' => 'Pending',
        ],
        $stats
    );
    demoEnsureRecord(
        $connection,
        'teacher_candidates',
        'candidate_id',
        ['email' => 'demo.candidate@example.test'],
        [
            'first_name' => 'Avery',
            'middle_name' => null,
            'last_name' => 'Chen',
            'email' => 'demo.candidate@example.test',
            'phone' => null,
            'department_id' => $computingDepartmentId,
            'specialization' => 'Information Systems',
            'application_date' => $today->modify('-10 days')->format('Y-m-d'),
            'application_status' => 'Interview',
            'notes' => 'Fictional teacher candidate for workflow testing.',
            'created_by' => (int) $adminAccount['user_id'],
            'hired_teacher_id' => null,
        ],
        $stats
    );

    $feeTypes = [];
    foreach ([
        ['name' => 'Demo Tuition', 'category' => 'Tuition', 'amount' => '20000.00'],
        ['name' => 'Demo Laboratory Fee', 'category' => 'Laboratory', 'amount' => '3000.00'],
        ['name' => 'Demo Student Services Fee', 'category' => 'Miscellaneous', 'amount' => '1500.00'],
    ] as $fee) {
        $feeTypes[$fee['name']] = demoEnsureRecord(
            $connection,
            'fee_types',
            'fee_type_id',
            ['fee_name' => $fee['name']],
            [
                'fee_name' => $fee['name'],
                'fee_category' => $fee['category'],
                'description' => 'Fictional fee for finance workflow testing.',
                'status' => 'Active',
            ],
            $stats
        );
        $fee['fee_type_id'] = $feeTypes[$fee['name']];
        $feeStructures[$fee['name']] = demoEnsureRecord(
            $connection,
            'fee_structures',
            'fee_structure_id',
            [
                'academic_year_id' => $academicYearId,
                'program_id' => $programs['DEMO-BSIT'],
                'fee_type_id' => $fee['fee_type_id'],
                'year_level' => 1,
            ],
            [
                'academic_year_id' => $academicYearId,
                'program_id' => $programs['DEMO-BSIT'],
                'fee_type_id' => $fee['fee_type_id'],
                'amount' => $fee['amount'],
                'year_level' => 1,
                'due_date' => $today->modify('+30 days')->format('Y-m-d'),
            ],
            $stats
        );
    }

    $scholarshipId = demoEnsureRecord(
        $connection,
        'scholarships',
        'scholarship_id',
        ['scholarship_name' => 'Demo Merit Scholarship'],
        [
            'scholarship_name' => 'Demo Merit Scholarship',
            'description' => 'Fictional 10 percent tuition discount for finance workflow testing.',
            'scholarship_type' => 'Percentage',
            'scholarship_value' => '10.00',
            'status' => 'Active',
        ],
        $stats
    );

    $studentFeeIds = [];
    foreach ($students as $studentIndex => $studentId) {
        foreach ([
            'Demo Tuition' => ['amount' => '20000.00', 'discount' => $studentIndex === 0 ? '2000.00' : '0.00'],
            'Demo Laboratory Fee' => ['amount' => '3000.00', 'discount' => '0.00'],
            'Demo Student Services Fee' => ['amount' => '1500.00', 'discount' => '0.00'],
        ] as $feeName => $amounts) {
            $status = 'Unpaid';
            if ($studentIndex === 0 && $feeName === 'Demo Tuition') {
                $status = 'Paid';
            } elseif ($studentIndex === 1 && $feeName === 'Demo Tuition') {
                $status = 'Partially Paid';
            } elseif ($studentIndex === 2 && $feeName === 'Demo Laboratory Fee') {
                $status = 'Paid';
            }

            $studentFeeIds[$studentIndex][$feeName] = demoEnsureRecord(
                $connection,
                'student_fees',
                'student_fee_id',
                [
                    'enrollment_id' => $enrollments[$studentIndex],
                    'fee_structure_id' => $feeStructures[$feeName],
                ],
                [
                    'enrollment_id' => $enrollments[$studentIndex],
                    'fee_structure_id' => $feeStructures[$feeName],
                    'scholarship_id' => $studentIndex === 0 && $feeName === 'Demo Tuition' ? $scholarshipId : null,
                    'amount' => $amounts['amount'],
                    'discount_amount' => $amounts['discount'],
                    'due_date' => $today->modify('+30 days')->format('Y-m-d'),
                    'fee_status' => $status,
                ],
                $stats
            );
        }
    }

    $payments = [
        ['student_index' => 0, 'fee_name' => 'Demo Tuition', 'amount' => '18000.00', 'method' => 'GCash', 'days_ago' => 5],
        ['student_index' => 1, 'fee_name' => 'Demo Tuition', 'amount' => '7000.00', 'method' => 'Cash', 'days_ago' => 3],
        ['student_index' => 2, 'fee_name' => 'Demo Laboratory Fee', 'amount' => '3000.00', 'method' => 'Bank Transfer', 'days_ago' => 1],
    ];
    foreach ($payments as $paymentIndex => $payment) {
        $studentIndex = $payment['student_index'];
        $studentFeeId = $studentFeeIds[$studentIndex][$payment['fee_name']];
        $receiptNumber = sprintf('DEMO-RCPT-%d-%03d', $academicStartYear, $paymentIndex + 1);
        $paymentId = demoEnsureRecord(
            $connection,
            'payments',
            'payment_id',
            ['receipt_number' => $receiptNumber],
            [
                'student_id' => $students[$studentIndex],
                'enrollment_id' => $enrollments[$studentIndex],
                'student_fee_id' => $studentFeeId,
                'payment_term' => null,
                'receipt_number' => $receiptNumber,
                'payment_date' => $today->modify('-' . $payment['days_ago'] . ' days')->format('Y-m-d H:i:s'),
                'total_amount' => $payment['amount'],
                'payment_method' => $payment['method'],
                'reference_number' => 'DEMO-REF-' . ($paymentIndex + 1),
                'payment_status' => 'Completed',
                'received_by' => (int) $adminAccount['user_id'],
                'remarks' => 'Fictional payment for finance workflow testing.',
            ],
            $stats
        );

        demoEnsureRecord(
            $connection,
            'payment_allocations',
            'allocation_id',
            ['payment_id' => $paymentId, 'student_fee_id' => $studentFeeId],
            [
                'payment_id' => $paymentId,
                'student_fee_id' => $studentFeeId,
                'allocated_amount' => $payment['amount'],
            ],
            $stats
        );
    }

    $paidFeeId = $studentFeeIds[0]['Demo Tuition'];
    demoEnsureRecord(
        $connection,
        'student_notifications',
        'notification_id',
        [
            'user_id' => (int) $studentAccount['user_id'],
            'notification_key' => 'DEMO-FEE-PAID-' . $paidFeeId,
        ],
        [
            'user_id' => (int) $studentAccount['user_id'],
            'notification_key' => 'DEMO-FEE-PAID-' . $paidFeeId,
            'notification_type' => 'Payment status',
            'title' => 'Demo tuition payment recorded',
            'message' => 'A fictional tuition payment has been added for workflow review.',
            'is_read' => 0,
            'is_active' => 1,
        ],
        $stats
    );

    foreach ([
        [
            'title' => 'DEMO: Welcome to the new academic year',
            'message' => 'This fictional announcement is included to demonstrate school-wide communication.',
            'status' => 'Published',
        ],
        [
            'title' => 'DEMO: Faculty planning reminder',
            'message' => 'This fictional draft demonstrates how an unpublished announcement is represented.',
            'status' => 'Draft',
        ],
    ] as $announcement) {
        demoEnsureRecord(
            $connection,
            'announcements',
            'announcement_id',
            ['created_by' => (int) $adminAccount['user_id'], 'title' => $announcement['title']],
            [
                'created_by' => (int) $adminAccount['user_id'],
                'title' => $announcement['title'],
                'message' => $announcement['message'],
                'status' => $announcement['status'],
            ],
            $stats
        );
    }

    $eventDate = demoClampDate($today, $yearStart, $yearEnd);
    foreach ([
        [
            'title' => 'DEMO: Enrollment support day',
            'description' => 'Fictional calendar event for enrollment workflow review.',
            'type' => 'Enrollment',
            'start' => $eventDate,
            'end' => $eventDate,
        ],
        [
            'title' => 'DEMO: Assessment week',
            'description' => 'Fictional calendar event for academic planning workflow review.',
            'type' => 'Examination',
            'start' => $assessmentDate,
            'end' => $assessmentDate->modify('+2 days') > $yearEnd ? $yearEnd : $assessmentDate->modify('+2 days'),
        ],
    ] as $event) {
        demoEnsureRecord(
            $connection,
            'school_calendar',
            'calendar_id',
            [
                'academic_year_id' => $academicYearId,
                'event_title' => $event['title'],
                'start_date' => $event['start']->format('Y-m-d'),
            ],
            [
                'academic_year_id' => $academicYearId,
                'event_title' => $event['title'],
                'event_description' => $event['description'],
                'event_type' => $event['type'],
                'start_date' => $event['start']->format('Y-m-d'),
                'end_date' => $event['end']->format('Y-m-d'),
            ],
            $stats
        );
    }

    if ($dryRun) {
        $connection->rollBack();
        fwrite(STDOUT, "Dry run complete; all inserts were rolled back.\n");
    } else {
        $connection->commit();
        fwrite(STDOUT, "Demo data seeded in isms_database.\n");
    }

    fwrite(STDOUT, 'Academic year used: ' . $academicYear['year_name'] . "\n");
    fwrite(STDOUT, 'Inserted: ' . json_encode($stats['inserted'], JSON_UNESCAPED_SLASHES) . "\n");
    fwrite(STDOUT, 'Reused: ' . json_encode($stats['reused'], JSON_UNESCAPED_SLASHES) . "\n");
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    fwrite(STDERR, 'Demo data seeding failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
