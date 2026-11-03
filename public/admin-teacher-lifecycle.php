<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';
require_once dirname(__DIR__) . '/app/Core/admin.php';
require_once dirname(__DIR__) . '/app/Modules/TeacherManagement/Services/lifecycle.php';
require_once dirname(__DIR__) . '/app/Modules/StudentManagement/Admissions/Services/application_mail.php';

$user = requireDashboardUser('Admin');
$connection = getDatabaseConnection();
$views = [
    'hiring' => ['label' => 'Hiring pipeline', 'description' => 'Manage applicants and create teacher access only after a hiring decision.'],
    'performance' => ['label' => 'Performance reviews', 'description' => 'Record dated staff reviews and preserve a searchable review history.'],
    'leave' => ['label' => 'Leave requests', 'description' => 'Review teacher-submitted leave requests and track approved leave periods.'],
    'resignations' => ['label' => 'Resignations', 'description' => 'Schedule resignations and apply account and assignment changes on the effective date.'],
];
$requestedView = $_GET['view'] ?? 'hiring';
$view = is_string($requestedView) && isset($views[$requestedView]) ? $requestedView : 'hiring';
$csrfToken = appCsrfToken();
$flashMessage = $_SESSION['teacher_lifecycle_flash'] ?? null;
unset($_SESSION['teacher_lifecycle_flash']);
$errorMessage = '';
$fieldErrors = [];
$oldInput = [];
$today = date('Y-m-d');
$isValidDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
$redirect = static function (string $targetView): never {
    header('Location: admin-teacher-lifecycle.php?view=' . rawurlencode($targetView), true, 303);
    exit;
};
$recordAudit = static function (string $action, string $description) use ($connection, $user): void {
    adminRecordAudit($connection, (int) $user['user_id'], $action, 'Teacher Management', $description);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedView = $_POST['view'] ?? null;
    if (is_string($postedView) && isset($views[$postedView])) {
        $view = $postedView;
    }

    foreach ($_POST as $key => $value) {
        if (is_string($key) && is_string($value)) {
            $oldInput[$key] = trim($value);
        }
    }

    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your security token has expired. Reload this page and try again.';
    } else {
        $action = adminPostString('action');
        try {
            if ($action === 'create_candidate') {
                $firstName = adminPostString('first_name');
                $middleName = adminPostString('middle_name');
                $lastName = adminPostString('last_name');
                $email = adminPostString('email');
                $phone = adminPostString('phone');
                $departmentId = adminPostInt('department_id');
                $specialization = adminPostString('specialization');
                $applicationDate = adminPostString('application_date');
                $notes = adminPostString('notes');

                if ($firstName === '' || strlen($firstName) > 100) {
                    $fieldErrors['first_name'] = 'Enter a first name up to 100 characters.';
                }
                if ($middleName !== '' && strlen($middleName) > 100) {
                    $fieldErrors['middle_name'] = 'Use no more than 100 characters.';
                }
                if ($lastName === '' || strlen($lastName) > 100) {
                    $fieldErrors['last_name'] = 'Enter a last name up to 100 characters.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
                    $fieldErrors['email'] = 'Enter a valid email address.';
                }
                if (!$isValidDate($applicationDate) || $applicationDate > $today) {
                    $fieldErrors['application_date'] = 'Enter a valid application date that is not in the future.';
                }
                if ($departmentId !== null) {
                    $departmentCheck = $connection->prepare(
                        'SELECT COUNT(*) FROM departments WHERE department_id = :department_id'
                    );
                    $departmentCheck->execute(['department_id' => $departmentId]);
                    if ((int) $departmentCheck->fetchColumn() !== 1) {
                        $fieldErrors['department_id'] = 'Select a department that still exists.';
                    }
                }

                if ($fieldErrors === []) {
                    $emailInUse = $connection->prepare(
                        'SELECT
                            (SELECT COUNT(*) FROM users WHERE email = :user_email)
                          + (SELECT COUNT(*) FROM teachers WHERE email = :teacher_email)
                          + (SELECT COUNT(*) FROM teacher_candidates WHERE email = :candidate_email)'
                    );
                    $emailInUse->execute([
                        'user_email' => $email,
                        'teacher_email' => $email,
                        'candidate_email' => $email,
                    ]);
                    if ((int) $emailInUse->fetchColumn() > 0) {
                        $fieldErrors['email'] = 'This email address is already associated with a school account or candidate.';
                    }
                }

                if ($fieldErrors === []) {
                    $insertCandidate = $connection->prepare(
                        "INSERT INTO teacher_candidates (
                            first_name, middle_name, last_name, email, phone,
                            department_id, specialization, application_date, notes, created_by
                         ) VALUES (
                            :first_name, :middle_name, :last_name, :email, :phone,
                            :department_id, :specialization, :application_date, :notes, :created_by
                         )"
                    );
                    $insertCandidate->execute([
                        'first_name' => $firstName,
                        'middle_name' => $middleName !== '' ? $middleName : null,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone !== '' ? $phone : null,
                        'department_id' => $departmentId,
                        'specialization' => $specialization !== '' ? $specialization : null,
                        'application_date' => $applicationDate,
                        'notes' => $notes !== '' ? $notes : null,
                        'created_by' => (int) $user['user_id'],
                    ]);
                    $candidateId = (int) $connection->lastInsertId();
                    $recordAudit('Register applicant', 'Registered teacher applicant ' . $candidateId . '.');
                    $_SESSION['teacher_lifecycle_flash'] = 'Applicant added to the hiring pipeline.';
                    $redirect('hiring');
                }
            } elseif ($action === 'update_candidate_status') {
                $candidateId = adminPostInt('candidate_id');
                $status = adminPostString('application_status');
                if ($candidateId === null || !in_array($status, ['Interview', 'Offer', 'Rejected'], true)) {
                    throw new RuntimeException('Choose a valid applicant and hiring stage.');
                }
                $updateCandidate = $connection->prepare(
                    "UPDATE teacher_candidates
                     SET application_status = :application_status
                     WHERE candidate_id = :candidate_id
                       AND application_status NOT IN ('Hired', 'Rejected')"
                );
                $updateCandidate->execute([
                    'application_status' => $status,
                    'candidate_id' => $candidateId,
                ]);
                if ($updateCandidate->rowCount() !== 1) {
                    throw new RuntimeException('That applicant could not be updated. Refresh the hiring pipeline and try again.');
                }
                $recordAudit('Update applicant', 'Moved teacher applicant ' . $candidateId . ' to ' . $status . '.');
                $_SESSION['teacher_lifecycle_flash'] = 'Applicant stage updated.';
                $redirect('hiring');
            } elseif ($action === 'hire_candidate') {
                $candidateId = adminPostInt('candidate_id');
                $employeeNumber = adminPostString('employee_number');
                $hireDate = adminPostString('hire_date');
                if ($candidateId === null) {
                    throw new RuntimeException('Choose an applicant to hire.');
                }
                if ($employeeNumber === '' || strlen($employeeNumber) > 50) {
                    $fieldErrors['employee_number'] = 'Enter an employee number up to 50 characters.';
                }
                if (!$isValidDate($hireDate) || $hireDate > $today) {
                    $fieldErrors['hire_date'] = 'Enter a valid hire date that is not in the future.';
                }

                if ($fieldErrors === []) {
                    $connection->beginTransaction();
                    try {
                        $candidateQuery = $connection->prepare(
                            "SELECT candidate_id, first_name, middle_name, last_name, email,
                                    phone, department_id, specialization
                             FROM teacher_candidates
                             WHERE candidate_id = :candidate_id
                               AND application_status = 'Offer'
                             FOR UPDATE"
                        );
                        $candidateQuery->execute(['candidate_id' => $candidateId]);
                        $candidate = $candidateQuery->fetch();
                        if ($candidate === false) {
                            throw new RuntimeException('Only applicants with an Offer stage can be hired.');
                        }

                        $employeeNumberCheck = $connection->prepare(
                            'SELECT COUNT(*) FROM teachers WHERE employee_number = :employee_number'
                        );
                        $employeeNumberCheck->execute(['employee_number' => $employeeNumber]);
                        if ((int) $employeeNumberCheck->fetchColumn() > 0) {
                            throw new RuntimeException('That employee number is already in use.');
                        }
                        $teacherEmailCheck = $connection->prepare(
                            'SELECT COUNT(*) FROM users WHERE email = :email'
                        );
                        $teacherEmailCheck->execute(['email' => $candidate['email']]);
                        if ((int) $teacherEmailCheck->fetchColumn() > 0) {
                            throw new RuntimeException('This applicant email is already linked to a school account.');
                        }

                        $teacherRoleQuery = $connection->prepare(
                            "SELECT role_id FROM roles WHERE role_name = 'Teacher' LIMIT 1"
                        );
                        $teacherRoleQuery->execute();
                        $teacherRoleId = $teacherRoleQuery->fetchColumn();
                        if ($teacherRoleId === false) {
                            throw new RuntimeException('The Teacher role is not configured.');
                        }

                        $username = 'teacher-' . bin2hex(random_bytes(12));
                        $createAccount = $connection->prepare(
                            "INSERT INTO users (
                                role_id, first_name, middle_name, last_name, email,
                                username, password, phone, account_status
                             ) VALUES (
                                :role_id, :first_name, :middle_name, :last_name, :email,
                                :username, :password, :phone, 'Pending'
                             )"
                        );
                        $createAccount->execute([
                            'role_id' => (int) $teacherRoleId,
                            'first_name' => $candidate['first_name'],
                            'middle_name' => $candidate['middle_name'],
                            'last_name' => $candidate['last_name'],
                            'email' => $candidate['email'],
                            'username' => $username,
                            'password' => appPasswordHash(bin2hex(random_bytes(32))),
                            'phone' => $candidate['phone'],
                        ]);
                        $userId = (int) $connection->lastInsertId();

                        $createTeacher = $connection->prepare(
                            "INSERT INTO teachers (
                                user_id, employee_number, first_name, last_name, email, phone,
                                department_id, specialization, employment_status, hire_date
                             ) VALUES (
                                :user_id, :employee_number, :first_name, :last_name, :email, :phone,
                                :department_id, :specialization, 'Active', :hire_date
                             )"
                        );
                        $createTeacher->execute([
                            'user_id' => $userId,
                            'employee_number' => $employeeNumber,
                            'first_name' => $candidate['first_name'],
                            'last_name' => $candidate['last_name'],
                            'email' => $candidate['email'],
                            'phone' => $candidate['phone'],
                            'department_id' => $candidate['department_id'],
                            'specialization' => $candidate['specialization'],
                            'hire_date' => $hireDate,
                        ]);
                        $teacherId = (int) $connection->lastInsertId();
                        $rawToken = bin2hex(random_bytes(32));
                        $createInvite = $connection->prepare(
                            'INSERT INTO teacher_account_setup_invites (
                                teacher_id, user_id, token_hash, expires_at
                             ) VALUES (
                                :teacher_id, :user_id, :token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 24 HOUR)
                             )'
                        );
                        $createInvite->execute([
                            'teacher_id' => $teacherId,
                            'user_id' => $userId,
                            'token_hash' => hash('sha256', $rawToken),
                        ]);

                        $markHired = $connection->prepare(
                            "UPDATE teacher_candidates
                             SET application_status = 'Hired', hired_teacher_id = :teacher_id
                             WHERE candidate_id = :candidate_id
                               AND application_status = 'Offer'"
                        );
                        $markHired->execute([
                            'teacher_id' => $teacherId,
                            'candidate_id' => $candidateId,
                        ]);
                        if ($markHired->rowCount() !== 1) {
                            throw new RuntimeException('The applicant hiring stage changed before the profile could be created.');
                        }

                        $recordAudit(
                            'Hire teacher',
                            'Hired applicant ' . $candidateId . ' as teacher ' . $teacherId
                                . '; created a pending Teacher account and one-time setup invitation.'
                        );
                        $connection->commit();
                    } catch (Throwable $exception) {
                        if ($connection->inTransaction()) {
                            $connection->rollBack();
                        }
                        throw $exception;
                    }

                    try {
                        $setupUrl = teacherLifecycleCreateBaseUrl()
                            . '/teacher-account-setup.php?token=' . rawurlencode($rawToken);
                        teacherLifecycleSendSetupEmail(
                            (string) $candidate['email'],
                            (string) $candidate['first_name'],
                            $setupUrl
                        );
                        $_SESSION['teacher_lifecycle_flash'] = 'Teacher hired. A one-time account setup link was emailed and expires in 24 hours.';
                        $redirect('hiring');
                    } catch (RuntimeException $mailError) {
                        error_log('Teacher account setup email failed: ' . $mailError->getMessage());
                        http_response_code(503);
                        $errorMessage = 'The teacher was hired, but the setup email could not be sent. Check SMTP and use the resend invitation action below.';
                    }
                }
            } elseif ($action === 'resend_invite') {
                $teacherId = adminPostInt('teacher_id');
                if ($teacherId === null) {
                    throw new RuntimeException('Choose a teacher account to invite.');
                }

                $teacherQuery = $connection->prepare(
                    "SELECT t.teacher_id, t.first_name, t.email, t.user_id, u.account_status
                     FROM teachers t
                     INNER JOIN users u ON u.user_id = t.user_id
                     INNER JOIN roles r ON r.role_id = u.role_id
                     WHERE t.teacher_id = :teacher_id
                       AND r.role_name = 'Teacher'
                       AND t.employment_status <> 'Resigned'
                       AND u.account_status = 'Pending'
                     LIMIT 1"
                );
                $teacherQuery->execute(['teacher_id' => $teacherId]);
                $teacher = $teacherQuery->fetch();
                if ($teacher === false) {
                    throw new RuntimeException('Only pending Teacher accounts can receive a new setup invitation.');
                }
                $rawToken = bin2hex(random_bytes(32));
                $updateInvite = $connection->prepare(
                    'UPDATE teacher_account_setup_invites
                     SET token_hash = :token_hash,
                         expires_at = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 24 HOUR),
                         used_at = NULL,
                         created_at = CURRENT_TIMESTAMP
                     WHERE teacher_id = :teacher_id
                       AND user_id = :user_id'
                );
                $updateInvite->execute([
                    'token_hash' => hash('sha256', $rawToken),
                    'teacher_id' => $teacherId,
                    'user_id' => (int) $teacher['user_id'],
                ]);
                if ($updateInvite->rowCount() !== 1) {
                    throw new RuntimeException('No setup invitation exists for this Teacher account.');
                }
                $recordAudit('Resend setup invitation', 'Rotated the teacher setup invitation for teacher ' . $teacherId . '.');

                try {
                    $setupUrl = teacherLifecycleCreateBaseUrl()
                        . '/teacher-account-setup.php?token=' . rawurlencode($rawToken);
                    teacherLifecycleSendSetupEmail((string) $teacher['email'], (string) $teacher['first_name'], $setupUrl);
                    $_SESSION['teacher_lifecycle_flash'] = 'A fresh one-time account setup link was emailed.';
                    $redirect('hiring');
                } catch (RuntimeException $mailError) {
                    error_log('Teacher account setup email resend failed: ' . $mailError->getMessage());
                    http_response_code(503);
                    $errorMessage = 'The invitation was renewed, but SMTP could not deliver it. Check the email configuration and try again.';
                }
            } elseif ($action === 'create_review') {
                $teacherId = adminPostInt('teacher_id');
                $reviewDate = adminPostString('review_date');
                $reviewPeriod = adminPostString('review_period');
                $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
                $strengths = adminPostString('strengths');
                $improvementAreas = adminPostString('improvement_areas');
                $notes = adminPostString('notes');
                if ($teacherId === null) {
                    $fieldErrors['teacher_id'] = 'Choose a teacher.';
                }
                if (!$isValidDate($reviewDate) || $reviewDate > $today) {
                    $fieldErrors['review_date'] = 'Enter a valid review date that is not in the future.';
                }
                if ($reviewPeriod === '' || strlen($reviewPeriod) > 100) {
                    $fieldErrors['review_period'] = 'Enter a review period up to 100 characters.';
                }
                if ($rating === false || $rating < 1 || $rating > 5) {
                    $fieldErrors['rating'] = 'Choose a rating from 1 to 5.';
                }
                if ($fieldErrors === []) {
                    $insertReview = $connection->prepare(
                        'INSERT INTO teacher_performance_reviews (
                            teacher_id, reviewer_user_id, review_date, review_period, rating,
                            strengths, improvement_areas, notes
                         ) VALUES (
                            :teacher_id, :reviewer_user_id, :review_date, :review_period, :rating,
                            :strengths, :improvement_areas, :notes
                         )'
                    );
                    $insertReview->execute([
                        'teacher_id' => $teacherId,
                        'reviewer_user_id' => (int) $user['user_id'],
                        'review_date' => $reviewDate,
                        'review_period' => $reviewPeriod,
                        'rating' => $rating,
                        'strengths' => $strengths !== '' ? $strengths : null,
                        'improvement_areas' => $improvementAreas !== '' ? $improvementAreas : null,
                        'notes' => $notes !== '' ? $notes : null,
                    ]);
                    $recordAudit('Record performance review', 'Recorded a performance review for teacher ' . $teacherId . '.');
                    $_SESSION['teacher_lifecycle_flash'] = 'Performance review added to the teacher history.';
                    $redirect('performance');
                }
            } elseif ($action === 'review_leave') {
                $requestId = adminPostInt('leave_request_id');
                $decision = adminPostString('decision');
                $reviewNote = adminPostString('review_note');
                if ($requestId === null || !in_array($decision, ['Approved', 'Declined'], true)) {
                    throw new RuntimeException('Choose a pending leave request and a valid decision.');
                }
                $connection->beginTransaction();
                try {
                    $leaveQuery = $connection->prepare(
                        "SELECT leave_request_id, teacher_id, start_date, end_date
                         FROM teacher_leave_requests
                         WHERE leave_request_id = :request_id
                           AND request_status = 'Pending'
                         FOR UPDATE"
                    );
                    $leaveQuery->execute(['request_id' => $requestId]);
                    $leaveRequest = $leaveQuery->fetch();
                    if ($leaveRequest === false) {
                        throw new RuntimeException('That leave request has already been reviewed.');
                    }
                    if ($decision === 'Approved') {
                        $overlapQuery = $connection->prepare(
                            "SELECT COUNT(*)
                             FROM teacher_leave_requests
                             WHERE teacher_id = :teacher_id
                               AND request_status = 'Approved'
                               AND start_date <= :requested_end
                               AND end_date >= :requested_start"
                        );
                        $overlapQuery->execute([
                            'teacher_id' => (int) $leaveRequest['teacher_id'],
                            'requested_end' => $leaveRequest['end_date'],
                            'requested_start' => $leaveRequest['start_date'],
                        ]);
                        if ((int) $overlapQuery->fetchColumn() > 0) {
                            throw new RuntimeException('This request overlaps another approved leave period for the teacher.');
                        }
                    }

                    $reviewLeave = $connection->prepare(
                        'UPDATE teacher_leave_requests
                         SET request_status = :request_status,
                             reviewed_by = :reviewed_by,
                             reviewed_at = CURRENT_TIMESTAMP,
                             review_note = :review_note
                         WHERE leave_request_id = :request_id
                           AND request_status = :pending_status'
                    );
                    $reviewLeave->execute([
                        'request_status' => $decision,
                        'reviewed_by' => (int) $user['user_id'],
                        'review_note' => $reviewNote !== '' ? $reviewNote : null,
                        'request_id' => $requestId,
                        'pending_status' => 'Pending',
                    ]);
                    if ($reviewLeave->rowCount() !== 1) {
                        throw new RuntimeException('The leave request could not be updated.');
                    }
                    $recordAudit(
                        'Review leave request',
                        $decision . ' teacher leave request ' . $requestId
                            . ' for teacher ' . (int) $leaveRequest['teacher_id'] . '.'
                    );
                    $connection->commit();
                    teacherLifecycleRefreshLeaveStatuses($connection);
                    $_SESSION['teacher_lifecycle_flash'] = 'Leave request ' . strtolower($decision) . '.';
                    $redirect('leave');
                } catch (Throwable $exception) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                    throw $exception;
                }
            } elseif ($action === 'schedule_resignation') {
                $teacherId = adminPostInt('teacher_id');
                $effectiveDate = adminPostString('effective_date');
                $reason = adminPostString('reason');
                if ($teacherId === null) {
                    $fieldErrors['teacher_id'] = 'Choose a teacher.';
                }
                if (!$isValidDate($effectiveDate)) {
                    $fieldErrors['effective_date'] = 'Enter a valid effective date.';
                }
                if ($fieldErrors === []) {
                    $connection->beginTransaction();
                    try {
                        $teacherQuery = $connection->prepare(
                            "SELECT employment_status
                             FROM teachers
                             WHERE teacher_id = :teacher_id
                             FOR UPDATE"
                        );
                        $teacherQuery->execute(['teacher_id' => $teacherId]);
                        $employmentStatus = $teacherQuery->fetchColumn();
                        if ($employmentStatus === false || $employmentStatus === 'Resigned') {
                            throw new RuntimeException('Select a teacher who has not already resigned.');
                        }
                        $existingQuery = $connection->prepare(
                            "SELECT COUNT(*)
                             FROM teacher_resignations
                             WHERE teacher_id = :teacher_id
                               AND resignation_status = 'Scheduled'"
                        );
                        $existingQuery->execute(['teacher_id' => $teacherId]);
                        if ((int) $existingQuery->fetchColumn() > 0) {
                            throw new RuntimeException('This teacher already has a scheduled resignation.');
                        }
                        $insertResignation = $connection->prepare(
                            "INSERT INTO teacher_resignations (
                                teacher_id, requested_by, effective_date, reason
                             ) VALUES (
                                :teacher_id, :requested_by, :effective_date, :reason
                             )"
                        );
                        $insertResignation->execute([
                            'teacher_id' => $teacherId,
                            'requested_by' => (int) $user['user_id'],
                            'effective_date' => $effectiveDate,
                            'reason' => $reason !== '' ? $reason : null,
                        ]);
                        $resignationId = (int) $connection->lastInsertId();
                        $recordAudit(
                            'Schedule resignation',
                            'Scheduled resignation ' . $resignationId . ' for teacher ' . $teacherId
                                . ' effective ' . $effectiveDate . '.'
                        );
                        $connection->commit();
                    } catch (Throwable $exception) {
                        if ($connection->inTransaction()) {
                            $connection->rollBack();
                        }
                        throw $exception;
                    }

                    teacherLifecycleApplyDueResignations($connection, (int) $user['user_id']);
                    $_SESSION['teacher_lifecycle_flash'] = $effectiveDate <= $today
                        ? 'Resignation applied. Teacher access was disabled and future assignments were ended.'
                        : 'Resignation scheduled. Access and future assignments will be updated on its effective date.';
                    $redirect('resignations');
                }
            } elseif ($action === 'cancel_resignation') {
                $resignationId = adminPostInt('resignation_id');
                if ($resignationId === null) {
                    throw new RuntimeException('Choose a scheduled resignation.');
                }
                $cancelResignation = $connection->prepare(
                    "UPDATE teacher_resignations
                     SET resignation_status = 'Cancelled'
                     WHERE resignation_id = :resignation_id
                       AND resignation_status = 'Scheduled'
                       AND effective_date > CURRENT_DATE()"
                );
                $cancelResignation->execute(['resignation_id' => $resignationId]);
                if ($cancelResignation->rowCount() !== 1) {
                    throw new RuntimeException('Only future scheduled resignations can be cancelled.');
                }
                $recordAudit('Cancel resignation', 'Cancelled scheduled resignation ' . $resignationId . '.');
                $_SESSION['teacher_lifecycle_flash'] = 'Scheduled resignation cancelled.';
                $redirect('resignations');
            } else {
                throw new RuntimeException('Choose a valid Teacher Management action.');
            }
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
}

$departments = $connection->query(
    'SELECT department_id, department_name FROM departments ORDER BY department_name'
)->fetchAll();
$eligibleTeachers = $connection->query(
    "SELECT teacher_id, employee_number, first_name, last_name, employment_status
     FROM teachers
     WHERE employment_status <> 'Resigned'
     ORDER BY last_name, first_name"
)->fetchAll();
$candidateRows = [];
$reviewRows = [];
$leaveRows = [];
$resignationRows = [];
$pendingInviteRows = [];

if ($view === 'hiring') {
    $candidateRows = $connection->query(
        'SELECT c.candidate_id, c.first_name, c.middle_name, c.last_name, c.email, c.phone,
                c.application_date, c.application_status, c.specialization, c.notes,
                d.department_name, t.teacher_id, t.employee_number, u.account_status,
                invite.expires_at AS invite_expires_at, invite.used_at AS invite_used_at
         FROM teacher_candidates c
         LEFT JOIN departments d ON d.department_id = c.department_id
         LEFT JOIN teachers t ON t.teacher_id = c.hired_teacher_id
         LEFT JOIN users u ON u.user_id = t.user_id
         LEFT JOIN teacher_account_setup_invites invite ON invite.teacher_id = t.teacher_id
         ORDER BY FIELD(c.application_status, "Offer", "Interview", "Applied", "Hired", "Rejected"),
                  c.application_date DESC, c.candidate_id DESC'
    )->fetchAll();
    $pendingInviteRows = $connection->query(
        "SELECT t.teacher_id, t.employee_number, t.first_name, t.last_name, t.email,
                invite.expires_at
         FROM teachers t
         INNER JOIN users u ON u.user_id = t.user_id
         INNER JOIN roles r ON r.role_id = u.role_id
         INNER JOIN teacher_account_setup_invites invite ON invite.teacher_id = t.teacher_id
         WHERE r.role_name = 'Teacher'
           AND t.employment_status <> 'Resigned'
           AND u.account_status = 'Pending'
         ORDER BY invite.expires_at, t.last_name, t.first_name"
    )->fetchAll();
} elseif ($view === 'performance') {
    $reviewRows = $connection->query(
        'SELECT r.review_id, r.review_date, r.review_period, r.rating,
                r.strengths, r.improvement_areas, r.notes,
                t.teacher_id, t.employee_number, t.first_name, t.last_name,
                u.first_name AS reviewer_first_name, u.last_name AS reviewer_last_name
         FROM teacher_performance_reviews r
         INNER JOIN teachers t ON t.teacher_id = r.teacher_id
         INNER JOIN users u ON u.user_id = r.reviewer_user_id
         ORDER BY r.review_date DESC, r.review_id DESC
         LIMIT 200'
    )->fetchAll();
} elseif ($view === 'leave') {
    $leaveRows = $connection->query(
        'SELECT lr.leave_request_id, lr.leave_type, lr.start_date, lr.end_date,
                lr.reason, lr.request_status, lr.requested_at, lr.review_note,
                t.teacher_id, t.employee_number, t.first_name, t.last_name,
                reviewer.first_name AS reviewer_first_name, reviewer.last_name AS reviewer_last_name
         FROM teacher_leave_requests lr
         INNER JOIN teachers t ON t.teacher_id = lr.teacher_id
         LEFT JOIN users reviewer ON reviewer.user_id = lr.reviewed_by
         ORDER BY FIELD(lr.request_status, "Pending", "Approved", "Declined", "Cancelled"),
                  lr.start_date DESC, lr.leave_request_id DESC
         LIMIT 200'
    )->fetchAll();
} elseif ($view === 'resignations') {
    $resignationRows = $connection->query(
        'SELECT r.resignation_id, r.effective_date, r.reason, r.resignation_status,
                r.created_at, t.teacher_id, t.employee_number, t.first_name, t.last_name
         FROM teacher_resignations r
         INNER JOIN teachers t ON t.teacher_id = r.teacher_id
         ORDER BY FIELD(r.resignation_status, "Scheduled", "Completed", "Cancelled"),
                  r.effective_date DESC, r.resignation_id DESC
         LIMIT 200'
    )->fetchAll();
}

$dashboard = [
    'theme' => 'admin',
    'roleLabel' => 'Admin',
    'pageTitle' => $views[$view]['label'],
    'eyebrow' => 'Teacher Management',
    'heading' => $views[$view]['label'],
    'description' => $views[$view]['description'],
    'metricsHeading' => '',
    'navItems' => adminNavigationItems('teachers'),
    'teacherSubnav' => adminTeacherSubnavItems($view),
    'navigationStyle' => 'admin',
    'sidebarTitle' => 'Admin Panel',
    'sidebarCaption' => 'School Operations',
    'homeHref' => 'admin-dashboard.php',
    'sectionContent' => true,
    'sectionContentTemplatePath' => dirname(__DIR__) . '/app/Shared/admin_teacher_lifecycle_content.php',
];

require dirname(__DIR__) . '/app/Shared/dashboard_layout.php';
