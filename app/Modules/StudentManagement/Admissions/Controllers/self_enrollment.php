<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/app/Core/dashboard.php';
require_once dirname(__DIR__, 2) . '/Students/Services/student.php';

startAppSession();
$connection = getDatabaseConnection();
$publicApplication = false;
$user = null;
$userId = 0;
$admissionApplicationId = filter_var(
    $_SESSION['admission_application_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (isset($_SESSION['user_id'])) {
    $user = requireDashboardUser('Student');
    $userId = (int) $user['user_id'];
} elseif ($admissionApplicationId !== false) {
    $applicationStatement = $connection->prepare(
        "SELECT a.*
         FROM student_applications a
         WHERE a.application_id = :application_id
           AND a.email_verified_at IS NOT NULL
           AND a.application_status = 'InProgress'
           AND EXISTS (
               SELECT 1
               FROM student_application_exams e
               WHERE e.application_id = a.application_id
                 AND e.submitted_at IS NOT NULL
           )
           AND (
               SELECT COUNT(*)
               FROM student_application_documents d
               WHERE d.application_id = a.application_id
           ) = 4
         LIMIT 1"
    );
    $applicationStatement->execute(['application_id' => (int) $admissionApplicationId]);
    $publicApplication = $applicationStatement->fetch();
    if ($publicApplication === false) {
        unset($_SESSION['admission_application_id']);
        header('Location: enrollment-instructions.php', true, 303);
        exit;
    }
} else {
    header('Location: enrollment-instructions.php', true, 303);
    exit;
}

$activeAcademicYear = academicActiveYear($connection);
$activeAcademicYearId = $activeAcademicYear === null
    ? null
    : (int) $activeAcademicYear['academic_year_id'];

if ($userId > 0 && $activeAcademicYearId !== null && studentHasActiveEnrollment($connection, $userId)) {
    header('Location: student-dashboard.php', true, 303);
    exit;
}

$studentId = $userId > 0 ? studentFindProfileId($connection, $userId) : null;
$desiredProgramId = is_array($publicApplication)
    ? (int) $publicApplication['desired_program_id']
    : studentFindDesiredProgramId($connection, $userId);
$desiredProgramName = null;
if ($desiredProgramId !== null) {
    $programNameStatement = $connection->prepare(
        'SELECT program_name FROM programs WHERE program_id = :program_id LIMIT 1'
    );
    $programNameStatement->execute(['program_id' => $desiredProgramId]);
    $programName = $programNameStatement->fetchColumn();
    $desiredProgramName = $programName === false ? null : (string) $programName;
}
$errorMessage = '';
$fieldErrors = [];
$selectedSectionId = '';
$passwordMaxBytes = appPasswordMaxBytes();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_array($publicApplication)) {
    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your security token has expired. Reload this page and try again.';
    } else {
        $sectionValue = filter_var(
            $_POST['section_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($sectionValue !== false) {
            $selectedSectionId = (string) $sectionValue;
        } else {
            $fieldErrors['section_id'] = 'Choose an available class section.';
        }

        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $passwordConfirmation = is_string($_POST['password_confirmation'] ?? null)
            ? $_POST['password_confirmation']
            : '';
        $passwordCharacterCount = preg_match_all('/./us', $password);
        if (
            $passwordCharacterCount === false
            || $passwordCharacterCount < 12
            || strlen($password) > $passwordMaxBytes
        ) {
            $fieldErrors['password'] = sprintf(
                'Use at least 12 characters and no more than %d UTF-8 bytes.',
                $passwordMaxBytes
            );
        }
        if ($password !== $passwordConfirmation) {
            $fieldErrors['password_confirmation'] = 'The passwords do not match.';
        }

        if ($fieldErrors === []) {
            $connection->beginTransaction();
            $lockedApplication = $connection->prepare(
                "SELECT a.*
                 FROM student_applications a
                 WHERE a.application_id = :application_id
                   AND a.email_verified_at IS NOT NULL
                   AND a.application_status = 'InProgress'
                   AND EXISTS (
                       SELECT 1
                       FROM student_application_exams e
                       WHERE e.application_id = a.application_id
                         AND e.submitted_at IS NOT NULL
                   )
                   AND (
                       SELECT COUNT(*)
                       FROM student_application_documents d
                       WHERE d.application_id = a.application_id
                   ) = 4
                 FOR UPDATE"
            );
            $lockedApplication->execute(['application_id' => (int) $admissionApplicationId]);
            $application = $lockedApplication->fetch();
            $lockedAcademicYear = academicActiveYear($connection, true);

            if ($application === false) {
                $errorMessage = 'This application is no longer ready for enrollment. Contact the school office for help.';
            } elseif (
                $lockedAcademicYear === null
                || (int) $lockedAcademicYear['academic_year_id'] !== $activeAcademicYearId
            ) {
                $errorMessage = 'Enrollment is currently closed because the active academic year changed. Reload this page and try again.';
            } else {
                $sectionStatement = $connection->prepare(
                    "SELECT sec.section_id, sec.academic_year_id, sec.program_id,
                            sec.section_name, sec.year_level, sec.capacity,
                            ay.year_name, p.program_name
                     FROM sections sec
                     INNER JOIN academic_years ay
                       ON ay.academic_year_id = sec.academic_year_id
                      AND ay.status = 'Active'
                     INNER JOIN programs p
                       ON p.program_id = sec.program_id
                      AND p.status = 'Active'
                     WHERE sec.section_id = :section_id
                       AND sec.academic_year_id = :academic_year_id
                       AND sec.program_id = :program_id
                       AND sec.status = 'Active'
                     FOR UPDATE"
                );
                $sectionStatement->execute([
                    'section_id' => (int) $selectedSectionId,
                    'academic_year_id' => $activeAcademicYearId,
                    'program_id' => (int) $application['desired_program_id'],
                ]);
                $section = $sectionStatement->fetch();

                if ($section === false) {
                    $fieldErrors['section_id'] = 'That section is no longer available. Choose an active section and try again.';
                } else {
                    $capacityStatement = $connection->prepare(
                        "SELECT COUNT(*)
                         FROM enrollments
                         WHERE section_id = :section_id
                           AND enrollment_status = 'Enrolled'"
                    );
                    $capacityStatement->execute(['section_id' => (int) $section['section_id']]);
                    $enrolledCount = (int) $capacityStatement->fetchColumn();
                    if ($section['capacity'] !== null && $enrolledCount >= (int) $section['capacity']) {
                        $fieldErrors['section_id'] = 'That section has reached capacity. Choose another available section.';
                    } else {
                        $emailCheck = $connection->prepare(
                            'SELECT
                                EXISTS(SELECT 1 FROM users WHERE email = :user_email)
                                OR EXISTS(SELECT 1 FROM students WHERE email = :student_email)'
                        );
                        $emailCheck->execute([
                            'user_email' => $application['email'],
                            'student_email' => $application['email'],
                        ]);
                        if ((bool) $emailCheck->fetchColumn()) {
                            $errorMessage = 'An account now uses this email address. Contact the school office to complete enrollment.';
                        } else {
                            $roleStatement = $connection->prepare(
                                "SELECT role_id FROM roles WHERE role_name = 'Student' LIMIT 1"
                            );
                            $roleStatement->execute();
                            $studentRoleId = $roleStatement->fetchColumn();
                            if ($studentRoleId === false) {
                                $connection->rollBack();
                                throw new RuntimeException('The Student role is not configured.');
                            }

                            $username = 'student-' . bin2hex(random_bytes(8));
                            $studentNumber = sprintf('APP-%s-%s', date('Y'), strtoupper(bin2hex(random_bytes(5))));
                            $createUser = $connection->prepare(
                                "INSERT INTO users (
                                    role_id, first_name, middle_name, last_name, email,
                                    username, password, phone, account_status
                                 ) VALUES (
                                    :role_id, :first_name, :middle_name, :last_name, :email,
                                    :username, :password, :phone, 'Active'
                                 )"
                            );
                            $createUser->execute([
                                'role_id' => (int) $studentRoleId,
                                'first_name' => $application['first_name'],
                                'middle_name' => $application['middle_name'],
                                'last_name' => $application['last_name'],
                                'email' => $application['email'],
                                'username' => $username,
                                'password' => appPasswordHash($password),
                                'phone' => $application['phone'],
                            ]);
                            $userId = (int) $connection->lastInsertId();

                            $createStudent = $connection->prepare(
                                "INSERT INTO students (
                                    user_id, desired_program_id, student_number,
                                    first_name, middle_name, last_name, birth_date, gender,
                                    email, phone, alternate_phone, address, mailing_address,
                                    city, province, admission_date, student_status
                                 ) VALUES (
                                    :user_id, :program_id, :student_number,
                                    :first_name, :middle_name, :last_name, :birth_date, :gender,
                                    :email, :phone, :alternate_phone, :address, :mailing_address,
                                    :city, :province, CURRENT_DATE(), 'Enrolled'
                                 )"
                            );
                            $createStudent->execute([
                                'user_id' => $userId,
                                'program_id' => (int) $application['desired_program_id'],
                                'student_number' => $studentNumber,
                                'first_name' => $application['first_name'],
                                'middle_name' => $application['middle_name'],
                                'last_name' => $application['last_name'],
                                'birth_date' => $application['birth_date'],
                                'gender' => $application['gender'],
                                'email' => $application['email'],
                                'phone' => $application['phone'],
                                'alternate_phone' => $application['alternate_phone'],
                                'address' => $application['residential_address'],
                                'mailing_address' => $application['mailing_address'],
                                'city' => $application['city'],
                                'province' => $application['province'],
                            ]);
                            $studentId = (int) $connection->lastInsertId();

                            $createEnrollment = $connection->prepare(
                                "INSERT INTO enrollments (
                                    student_id, academic_year_id, program_id, section_id,
                                    year_level, enrollment_date, enrollment_status
                                 ) VALUES (
                                    :student_id, :academic_year_id, :program_id, :section_id,
                                    :year_level, CURRENT_DATE(), 'Enrolled'
                                 )"
                            );
                            $createEnrollment->execute([
                                'student_id' => $studentId,
                                'academic_year_id' => (int) $section['academic_year_id'],
                                'program_id' => (int) $section['program_id'],
                                'section_id' => (int) $section['section_id'],
                                'year_level' => (int) $section['year_level'],
                            ]);

                            $linkApplication = $connection->prepare(
                                "UPDATE student_applications
                                 SET student_id = :student_id,
                                     application_status = 'Submitted'
                                 WHERE application_id = :application_id
                                   AND application_status = 'InProgress'"
                            );
                            $linkApplication->execute([
                                'student_id' => $studentId,
                                'application_id' => (int) $application['application_id'],
                            ]);
                            if ($linkApplication->rowCount() !== 1) {
                                throw new RuntimeException('The completed application could not be linked to the student account.');
                            }

                            $auditLog = $connection->prepare(
                                'INSERT INTO audit_logs (user_id, action, module_name, description, ip_address)
                                 VALUES (:user_id, :action, :module_name, :description, :ip_address)'
                            );
                            $auditLog->execute([
                                'user_id' => $userId,
                                'action' => 'Public student enrollment completed',
                                'module_name' => 'Enrollment',
                                'description' => sprintf(
                                    'Student completed enrollment in %s, %s, year level %d for %s.',
                                    $section['program_name'],
                                    $section['section_name'],
                                    (int) $section['year_level'],
                                    $section['year_name']
                                ),
                                'ip_address' => loginIpAddress(),
                            ]);
                            $connection->commit();
                            session_regenerate_id(true);
                            $_SESSION = [
                                'user_id' => $userId,
                                'csrf_token' => bin2hex(random_bytes(32)),
                            ];
                            header('Location: student-dashboard.php', true, 303);
                            exit;
                        }
                    }
                }
            }

            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your security token has expired. Reload this page and try again.';
    } elseif ($studentId === null) {
        $errorMessage = 'A student profile is not linked to your account. Contact the school administrator.';
    } else {
        $sectionValue = filter_var(
            $_POST['section_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($sectionValue !== false) {
            $selectedSectionId = (string) $sectionValue;
        } else {
            $fieldErrors['section_id'] = 'Choose an available class section.';
        }

        if ($fieldErrors === []) {
            $connection->beginTransaction();
            $lockedAcademicYear = academicActiveYear($connection, true);
            if (
                $lockedAcademicYear === null
                || (int) $lockedAcademicYear['academic_year_id'] !== $activeAcademicYearId
            ) {
                $errorMessage = 'Enrollment is currently closed because the active academic year changed. Reload this page and try again.';
            }

            $existingEnrollment = false;
            if ($errorMessage === '') {
                $studentStatement = $connection->prepare(
                    'SELECT student_id, student_status
                     FROM students
                     WHERE user_id = :user_id
                     FOR UPDATE'
                );
                $studentStatement->execute(['user_id' => $userId]);
                $student = $studentStatement->fetch();

                if ($student === false || (int) $student['student_id'] !== $studentId) {
                    $errorMessage = 'A student profile is not linked to your account. Contact the school administrator.';
                } elseif (!in_array($student['student_status'], ['Applicant', 'Enrolled'], true)) {
                    $errorMessage = 'Your student record is not eligible for self-enrollment. Contact the school administrator.';
                } else {
                    $existingStatement = $connection->prepare(
                        'SELECT enrollment_id, enrollment_status
                         FROM enrollments
                         WHERE student_id = :student_id
                           AND academic_year_id = :academic_year_id
                         FOR UPDATE'
                    );
                    $existingStatement->execute([
                        'student_id' => $studentId,
                        'academic_year_id' => $activeAcademicYearId,
                    ]);
                    $existingEnrollment = $existingStatement->fetch();

                    if ($existingEnrollment !== false && $existingEnrollment['enrollment_status'] === 'Enrolled') {
                        $connection->commit();
                        header('Location: student-dashboard.php', true, 303);
                        exit;
                    }

                    if (
                        $existingEnrollment !== false
                        && $existingEnrollment['enrollment_status'] !== 'Pending'
                    ) {
                        $errorMessage = 'An existing enrollment record needs review before you can enroll again. Contact the school administrator.';
                    } elseif ($existingEnrollment !== false) {
                        $enrollmentId = (int) $existingEnrollment['enrollment_id'];
                        $dependentRecords = $connection->prepare(
                            'SELECT
                                (SELECT COUNT(*) FROM attendance WHERE enrollment_id = :attendance_id)
                                + (SELECT COUNT(*) FROM grade_records WHERE enrollment_id = :grade_id)
                                + (SELECT COUNT(*) FROM report_cards WHERE enrollment_id = :report_card_id)
                                + (SELECT COUNT(*) FROM student_fees WHERE enrollment_id = :fee_id)
                                + (SELECT COUNT(*) FROM payments WHERE enrollment_id = :payment_id)'
                        );
                        $dependentRecords->execute([
                            'attendance_id' => $enrollmentId,
                            'grade_id' => $enrollmentId,
                            'report_card_id' => $enrollmentId,
                            'fee_id' => $enrollmentId,
                            'payment_id' => $enrollmentId,
                        ]);

                        if ((int) $dependentRecords->fetchColumn() > 0) {
                            $errorMessage = 'Your existing pending enrollment has related school records and must be reviewed by the school administrator.';
                        }
                    }
                }
            }

            if ($errorMessage === '') {
                $sectionStatement = $connection->prepare(
                    "SELECT sec.section_id, sec.academic_year_id, sec.program_id,
                            sec.section_name, sec.year_level, sec.capacity,
                            ay.year_name, p.program_name
                     FROM sections sec
                     INNER JOIN academic_years ay
                       ON ay.academic_year_id = sec.academic_year_id
                      AND ay.status = 'Active'
                     LEFT JOIN programs p ON p.program_id = sec.program_id
                     WHERE sec.section_id = :section_id
                       AND sec.academic_year_id = :academic_year_id
                       AND sec.status = 'Active'
                       AND (
                              :program_filter IS NULL
                              OR sec.program_id = :program_id
                         )
                         AND (
                              sec.program_id IS NULL
                            OR (p.program_id IS NOT NULL AND p.status = 'Active')
                       )
                     FOR UPDATE"
                );
                $sectionStatement->execute([
                    'section_id' => (int) $selectedSectionId,
                    'academic_year_id' => $activeAcademicYearId,
                    'program_filter' => $desiredProgramId,
                    'program_id' => $desiredProgramId,
                ]);
                $section = $sectionStatement->fetch();

                if ($section === false) {
                    $fieldErrors['section_id'] = 'That section is no longer available. Choose an active section and try again.';
                } else {
                    $capacityStatement = $connection->prepare(
                        "SELECT COUNT(*)
                         FROM enrollments
                         WHERE section_id = :section_id
                           AND enrollment_status = 'Enrolled'"
                    );
                    $capacityStatement->execute(['section_id' => (int) $section['section_id']]);
                    $enrolledCount = (int) $capacityStatement->fetchColumn();
                    $capacity = $section['capacity'] === null ? null : (int) $section['capacity'];

                    if ($capacity !== null && $enrolledCount >= $capacity) {
                        $fieldErrors['section_id'] = 'That section has reached capacity. Choose another available section.';
                    } else {
                        if ($existingEnrollment !== false) {
                            $saveEnrollment = $connection->prepare(
                                "UPDATE enrollments
                                 SET academic_year_id = :academic_year_id,
                                     program_id = :program_id,
                                     section_id = :section_id,
                                     year_level = :year_level,
                                     enrollment_date = CURRENT_DATE(),
                                     enrollment_status = 'Enrolled'
                                 WHERE enrollment_id = :enrollment_id"
                            );
                            $saveEnrollment->execute([
                                'academic_year_id' => (int) $section['academic_year_id'],
                                'program_id' => $section['program_id'] === null ? null : (int) $section['program_id'],
                                'section_id' => (int) $section['section_id'],
                                'year_level' => (int) $section['year_level'],
                                'enrollment_id' => (int) $existingEnrollment['enrollment_id'],
                            ]);
                        } else {
                            $saveEnrollment = $connection->prepare(
                                "INSERT INTO enrollments (
                                    student_id, academic_year_id, program_id, section_id,
                                    year_level, enrollment_date, enrollment_status
                                 ) VALUES (
                                    :student_id, :academic_year_id, :program_id, :section_id,
                                    :year_level, CURRENT_DATE(), 'Enrolled'
                                 )"
                            );
                            $saveEnrollment->execute([
                                'student_id' => $studentId,
                                'academic_year_id' => (int) $section['academic_year_id'],
                                'program_id' => $section['program_id'] === null ? null : (int) $section['program_id'],
                                'section_id' => (int) $section['section_id'],
                                'year_level' => (int) $section['year_level'],
                            ]);
                        }

                        $updateStudent = $connection->prepare(
                            "UPDATE students
                             SET student_status = 'Enrolled'
                             WHERE student_id = :student_id
                               AND student_status = 'Applicant'"
                        );
                        $updateStudent->execute(['student_id' => $studentId]);

                        $auditLog = $connection->prepare(
                            'INSERT INTO audit_logs (user_id, action, module_name, description, ip_address)
                             VALUES (:user_id, :action, :module_name, :description, :ip_address)'
                        );
                        $auditLog->execute([
                            'user_id' => $userId,
                            'action' => 'Student self-enrollment',
                            'module_name' => 'Enrollment',
                            'description' => sprintf(
                                'Student enrolled in %s, %s, year level %d for %s.',
                                $section['program_name'] ?? 'school program',
                                $section['section_name'],
                                (int) $section['year_level'],
                                $section['year_name']
                            ),
                            'ip_address' => loginIpAddress(),
                        ]);
                    }
                }
            }

            if ($errorMessage !== '' || $fieldErrors !== []) {
                $connection->rollBack();
            } else {
                $connection->commit();
                header('Location: student-dashboard.php', true, 303);
                exit;
            }
        }
    }
}

$sectionChoiceStatement = $connection->prepare(
    "SELECT sec.section_id, sec.section_name, sec.year_level, sec.capacity,
            ay.year_name, p.program_name,
            (
                SELECT COUNT(*)
                FROM enrollments e
                WHERE e.section_id = sec.section_id
                  AND e.enrollment_status = 'Enrolled'
            ) AS enrolled_count
     FROM sections sec
     INNER JOIN academic_years ay
       ON ay.academic_year_id = sec.academic_year_id
      AND ay.status = 'Active'
     LEFT JOIN programs p ON p.program_id = sec.program_id
     WHERE sec.status = 'Active'
       AND sec.academic_year_id = :academic_year_id
       AND (
            :program_filter IS NULL
            OR sec.program_id = :program_id
       )
       AND (
            sec.program_id IS NULL
            OR (p.program_id IS NOT NULL AND p.status = 'Active')
       )
       AND (
            sec.capacity IS NULL
            OR (
                SELECT COUNT(*)
                FROM enrollments e
                WHERE e.section_id = sec.section_id
                  AND e.enrollment_status = 'Enrolled'
            ) < sec.capacity
       )
     ORDER BY p.program_name, sec.year_level, sec.section_name"
);
$sectionChoiceStatement->execute([
    'academic_year_id' => $activeAcademicYearId,
    'program_filter' => $desiredProgramId,
    'program_id' => $desiredProgramId,
]);
$sectionChoices = $sectionChoiceStatement->fetchAll();

if (is_array($publicApplication)) {
    $pageTitle = 'Complete Student Enrollment - Fullbright College';
    $csrfToken = appCsrfToken();
    require dirname(__DIR__) . '/Views/public_enrollment.php';
    exit;
}

$dashboard = [
    'theme' => 'executive',
    'roleLabel' => 'Student',
    'pageTitle' => 'Student enrollment',
    'eyebrow' => 'Complete enrollment',
    'heading' => 'Choose your class section.',
    'description' => $activeAcademicYear === null
        ? 'Student enrollment opens when the school activates an academic year.'
        : 'Select a section in ' . $activeAcademicYear['year_name'] . ' to finish enrollment and access your Student dashboard.',
    'navigationStyle' => 'student',
    'navItems' => studentNavigationItems('enrollment'),
    'sidebarTitle' => 'Student Workspace',
    'sidebarCaption' => 'Your School Records',
    'homeHref' => 'student-enrollment.php',
    'welcomeLabel' => 'Enrollment required',
    'welcomeCaption' => 'Choose a section to get started.',
    'sectionContent' => true,
    'sectionContentTemplatePath' => dirname(__DIR__) . '/Views/self_enrollment_content.php',
    'hideAnnouncementFeed' => true,
];

require dirname(__DIR__, 5) . '/app/Shared/dashboard_layout.php';
