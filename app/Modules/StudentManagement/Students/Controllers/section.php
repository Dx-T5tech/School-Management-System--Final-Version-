<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/app/Core/dashboard.php';
require_once dirname(__DIR__, 5) . '/app/Core/finance.php';
require_once dirname(__DIR__, 2) . '/Students/Services/student.php';

$user = requireDashboardUser('Student');
$connection = getDatabaseConnection();
$activeAcademicYear = academicActiveYear($connection);
$activeAcademicYearId = $activeAcademicYear === null
    ? null
    : (int) $activeAcademicYear['academic_year_id'];
if ($activeAcademicYearId === null || !studentHasActiveEnrollment($connection, (int) $user['user_id'])) {
    header('Location: student-enrollment.php', true, 303);
    exit;
}
$sectionDefinitions = [
    'profile' => [
        'title' => 'My Profile',
        'eyebrow' => 'Student account',
        'description' => 'Review the contact and enrollment details linked to your account.',
    ],
    'enrollment' => [
        'title' => 'My Enrollment',
        'eyebrow' => 'Academic records',
        'description' => 'Review your enrollment history and current program information.',
    ],
    'schedule' => [
        'title' => 'My Schedule',
        'eyebrow' => 'Class timetable',
        'description' => 'View the active class schedule for your current enrollment.',
    ],
    'attendance' => [
        'title' => 'My Attendance',
        'eyebrow' => 'Attendance records',
        'description' => 'Review attendance recorded for your student profile.',
    ],
    'grades' => [
        'title' => 'My Grades',
        'eyebrow' => 'Academic progress',
        'description' => 'Review published assessment scores and recorded term grades.',
    ],
    'report-card' => [
        'title' => 'My Report Card',
        'eyebrow' => 'Published results',
        'description' => 'View report cards released by the school.',
    ],
    'payments' => [
        'title' => 'Balance Fees',
        'eyebrow' => 'Student account',
        'description' => 'Review your active-year fee assessment, payments received, remaining balance, and tuition terms.',
    ],
    'payment' => [
        'title' => 'Payment',
        'eyebrow' => 'Payment requests',
        'description' => 'Submit an already-made payment for school verification and review your payment history.',
    ],
    'financial-report' => [
        'title' => 'Financial Report',
        'eyebrow' => 'Student account',
        'description' => 'Review and print your active-year fee assessment and payment history.',
    ],
    'notifications' => [
        'title' => 'Notifications',
        'eyebrow' => 'Student updates',
        'description' => 'Review fee reminders and payment status updates from the school.',
    ],
    'announcements' => [
        'title' => 'Announcements',
        'eyebrow' => 'School updates',
        'description' => 'Read announcements published for the school community.',
    ],
];

$sectionKey = $_GET['section'] ?? '';
if (!is_string($sectionKey) || !isset($sectionDefinitions[$sectionKey])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Student section not found.');
}

$paymentMethods = ['Cash', 'Bank Transfer', 'GCash', 'Maya', 'Card', 'Other'];
$paymentTerms = ['Prelim', 'Midterm', 'Final'];
$paymentValues = ['fee_id' => '', 'payment_term' => '', 'total_amount' => '', 'payment_method' => '', 'reference_number' => ''];
$paymentFieldErrors = [];
$paymentError = '';
$notificationFlash = $_SESSION['student_notification_flash'] ?? '';
unset($_SESSION['student_notification_flash']);

$profileStatement = $connection->prepare(
    'SELECT s.student_id, s.student_number, s.first_name, s.middle_name, s.last_name,
            s.birth_date, s.gender, s.email, s.phone, s.address, s.city, s.province,
            s.admission_date, s.student_status, s.desired_program_id,
            p.program_name AS desired_program_name,
            u.email AS account_email, u.phone AS account_phone
     FROM users u
     LEFT JOIN students s ON s.user_id = u.user_id
     LEFT JOIN programs p ON p.program_id = s.desired_program_id
     WHERE u.user_id = :user_id
     LIMIT 1'
);
$profileStatement->execute(['user_id' => (int) $user['user_id']]);
$profileRow = $profileStatement->fetch();
$studentProfile = $profileRow !== false && $profileRow['student_id'] !== null ? $profileRow : null;
$studentId = $studentProfile === null ? null : (int) $studentProfile['student_id'];
$profileValues = [
    'phone' => (string) ($studentProfile['phone'] ?? ''),
    'address' => (string) ($studentProfile['address'] ?? ''),
    'city' => (string) ($studentProfile['city'] ?? ''),
    'province' => (string) ($studentProfile['province'] ?? ''),
];
$profileFieldErrors = [];
$profileError = '';
$profileNotice = $_SESSION['student_profile_flash'] ?? '';
unset($_SESSION['student_profile_flash']);
$profileEditMode = ($_GET['edit'] ?? null) === '1';
if ($studentId !== null) {
    financeSyncStudentFeeNotifications(
        $connection,
        (int) $user['user_id'],
        $studentId,
        $activeAcademicYearId
    );
}
$unreadNotifications = financeStudentUnreadNotificationCount($connection, (int) $user['user_id']);
$profileFields = [];
$tables = [];

$fetchRows = static function (string $sql, array $parameters = []) use ($connection): array {
    $statement = $connection->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
};

$table = static fn (string $title, string $caption, array $columns, array $rows, string $empty): array => [
    'title' => $title,
    'caption' => $caption,
    'columns' => $columns,
    'rows' => $rows,
    'empty' => $empty,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sectionKey === 'profile') {
    $profileEditMode = true;
    foreach ($profileValues as $fieldName => $_) {
        $postedValue = $_POST[$fieldName] ?? '';
        $profileValues[$fieldName] = is_string($postedValue) ? trim($postedValue) : '';
    }

    if (!is_string($_POST['action'] ?? null) || $_POST['action'] !== 'update-profile') {
        $profileError = 'Choose a supported profile action.';
    } elseif (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $profileError = 'Your security token has expired. Reload this page and try again.';
    } elseif ($studentId === null) {
        $profileError = 'A linked student profile is required to update your contact information.';
    } else {
        if ($profileValues['phone'] !== '') {
            $phoneDigits = preg_replace('/\D+/', '', $profileValues['phone']);
            if (
                preg_match('/^\+?[0-9(). -]{7,30}$/D', $profileValues['phone']) !== 1
                || !is_string($phoneDigits)
                || strlen($phoneDigits) < 7
                || strlen($phoneDigits) > 15
            ) {
                $profileFieldErrors['phone'] = 'Enter a phone number with 7 to 15 digits.';
            }
        }

        foreach ([
            'phone' => 30,
            'address' => 2000,
            'city' => 100,
            'province' => 100,
        ] as $fieldName => $maxLength) {
            if (mb_strlen($profileValues[$fieldName], 'UTF-8') > $maxLength) {
                $profileFieldErrors[$fieldName] = sprintf(
                    'Use no more than %d characters.',
                    $maxLength
                );
            }
        }

        if ($profileFieldErrors === []) {
            try {
                $connection->beginTransaction();
                $updateStudentProfile = $connection->prepare(
                    'UPDATE students
                     SET phone = :phone,
                         address = :address,
                         city = :city,
                         province = :province
                     WHERE student_id = :student_id
                       AND user_id = :user_id'
                );
                $updateStudentProfile->execute([
                    'phone' => $profileValues['phone'] !== '' ? $profileValues['phone'] : null,
                    'address' => $profileValues['address'] !== '' ? $profileValues['address'] : null,
                    'city' => $profileValues['city'] !== '' ? $profileValues['city'] : null,
                    'province' => $profileValues['province'] !== '' ? $profileValues['province'] : null,
                    'student_id' => $studentId,
                    'user_id' => (int) $user['user_id'],
                ]);
                $updateUserPhone = $connection->prepare(
                    'UPDATE users
                     SET phone = :phone
                     WHERE user_id = :user_id'
                );
                $updateUserPhone->execute([
                    'phone' => $profileValues['phone'] !== '' ? $profileValues['phone'] : null,
                    'user_id' => (int) $user['user_id'],
                ]);
                $connection->commit();
                $_SESSION['student_profile_flash'] = 'Your contact information has been updated.';
                header('Location: student-section.php?section=profile', true, 303);
                exit;
            } catch (PDOException $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                throw $exception;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sectionKey === 'notifications') {
    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $notificationFlash = 'Your security token has expired. Reload this page and try again.';
    } elseif ($studentId !== null && ($_POST['action'] ?? '') === 'mark-read') {
        $notificationId = filter_var($_POST['notification_id'] ?? null, FILTER_VALIDATE_INT);
        if ($notificationId !== false && $notificationId !== null) {
            $markRead = $connection->prepare(
                'UPDATE student_notifications
                 SET is_read = 1, read_at = CURRENT_TIMESTAMP
                 WHERE notification_id = :notification_id
                   AND user_id = :user_id
                   AND is_active = 1'
            );
            $markRead->execute([
                'notification_id' => $notificationId,
                'user_id' => (int) $user['user_id'],
            ]);
            $_SESSION['student_notification_flash'] = 'Notification marked as read.';
            header('Location: student-section.php?section=notifications', true, 303);
            exit;
        }
        $notificationFlash = 'Select a valid notification and try again.';
    } elseif ($studentId !== null && ($_POST['action'] ?? '') === 'mark-all-read') {
        $markAllRead = $connection->prepare(
            'UPDATE student_notifications
             SET is_read = 1, read_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id
               AND is_active = 1
               AND is_read = 0'
        );
        $markAllRead->execute(['user_id' => (int) $user['user_id']]);
        $_SESSION['student_notification_flash'] = 'All notifications marked as read.';
        header('Location: student-section.php?section=notifications', true, 303);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sectionKey === 'payment') {
    foreach ($paymentValues as $fieldName => $_) {
        $postedValue = $_POST[$fieldName] ?? '';
        $paymentValues[$fieldName] = is_string($postedValue) ? trim($postedValue) : '';
    }
    if (!is_string($_POST['action'] ?? null) || $_POST['action'] !== 'submit-payment') {
        $paymentError = 'Choose a supported payment action.';
    } elseif (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $paymentError = 'Your security token has expired. Reload this page and try again.';
    } elseif ($studentId === null) {
        $paymentError = 'A linked student profile is required to submit a payment request.';
    } else {
        $feeId = filter_var($paymentValues['fee_id'], FILTER_VALIDATE_INT);
        if ($feeId === false || $feeId < 1) {
            $paymentFieldErrors['fee_id'] = 'Choose a fee to pay.';
        }
        if (!in_array($paymentValues['payment_method'], $paymentMethods, true)) {
            $paymentFieldErrors['payment_method'] = 'Choose an available payment method.';
        }
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $paymentValues['total_amount'])) {
            $paymentFieldErrors['total_amount'] = 'Enter an amount with no more than two decimal places.';
        } elseif ((int) round((float) $paymentValues['total_amount'] * 100) < 1) {
            $paymentFieldErrors['total_amount'] = 'Enter an amount greater than zero.';
        }
        if ($paymentValues['payment_term'] !== ''
            && !in_array($paymentValues['payment_term'], $paymentTerms, true)) {
            $paymentFieldErrors['payment_term'] = 'Choose an available tuition term.';
        }
        if (strlen($paymentValues['reference_number']) > 150) {
            $paymentFieldErrors['reference_number'] = 'Reference numbers must be 150 characters or fewer.';
        }

        if ($paymentFieldErrors === []) {
            $feeAmountCents = (int) round((float) $paymentValues['total_amount'] * 100);
            try {
                $connection->beginTransaction();
                $feeCheck = $connection->prepare(
                    "SELECT sf.student_fee_id, sf.enrollment_id,
                            ROUND(GREATEST(sf.amount - COALESCE(sf.discount_amount, 0), 0) * 100) AS net_cents,
                            sf.fee_status, ft.fee_category, ft.fee_name
                     FROM student_fees sf
                     INNER JOIN enrollments e ON e.enrollment_id = sf.enrollment_id
                     INNER JOIN fee_structures fs ON fs.fee_structure_id = sf.fee_structure_id
                     INNER JOIN fee_types ft ON ft.fee_type_id = fs.fee_type_id
                     WHERE sf.student_fee_id = :student_fee_id
                       AND e.student_id = :student_id
                       AND e.academic_year_id = :academic_year_id
                       AND e.enrollment_status = 'Enrolled'
                       AND fs.academic_year_id = :fee_year_id
                     FOR UPDATE"
                );
                $feeCheck->execute([
                    'student_fee_id' => $feeId,
                    'student_id' => $studentId,
                    'academic_year_id' => $activeAcademicYearId,
                    'fee_year_id' => $activeAcademicYearId,
                ]);
                $selectedFee = $feeCheck->fetch();
                if ($selectedFee === false || $selectedFee['fee_status'] === 'Waived'
                    || (int) $selectedFee['net_cents'] <= 0) {
                    throw new RuntimeException('Choose an active, unpaid fee linked to your current enrollment.');
                }
                if ($selectedFee['fee_category'] === 'Tuition' && $paymentValues['payment_term'] === '') {
                    $paymentFieldErrors['payment_term'] = 'Choose the tuition term this payment is for.';
                    throw new RuntimeException($paymentFieldErrors['payment_term']);
                }
                if ($selectedFee['fee_category'] !== 'Tuition' && $paymentValues['payment_term'] !== '') {
                    $paymentFieldErrors['payment_term'] = 'Choose a tuition term only for tuition fees.';
                    throw new RuntimeException($paymentFieldErrors['payment_term']);
                }

                $pendingCheck = $connection->prepare(
                    'SELECT COUNT(*)
                     FROM payments
                     WHERE student_fee_id = :student_fee_id
                       AND payment_term <=> :payment_term
                       AND payment_status = :payment_status'
                );
                $pendingCheck->execute([
                    'student_fee_id' => $feeId,
                    'payment_term' => $selectedFee['fee_category'] === 'Tuition' ? $paymentValues['payment_term'] : null,
                    'payment_status' => 'Pending',
                ]);
                if ((int) $pendingCheck->fetchColumn() > 0) {
                    throw new RuntimeException('A payment request for this fee and term is already awaiting school verification.');
                }

                $paidCheck = $connection->prepare(
                    'SELECT COALESCE(SUM(ROUND(total_amount * 100)), 0)
                     FROM payments
                     WHERE student_fee_id = :student_fee_id
                       AND payment_status = :payment_status'
                     . ($selectedFee['fee_category'] === 'Tuition' ? ' AND payment_term = :payment_term' : '')
                );
                $paidParameters = [
                    'student_fee_id' => $feeId,
                    'payment_status' => 'Completed',
                ];
                if ($selectedFee['fee_category'] === 'Tuition') {
                    $paidParameters['payment_term'] = $paymentValues['payment_term'];
                }
                $paidCheck->execute($paidParameters);
                $paidCents = (int) $paidCheck->fetchColumn();
                $termBalanceCents = $selectedFee['fee_category'] === 'Tuition'
                    ? financeTuitionInstallmentsInCents((int) $selectedFee['net_cents'])[$paymentValues['payment_term']]
                    : (int) $selectedFee['net_cents'];
                if ($paidCents + $feeAmountCents > $termBalanceCents) {
                    $remainingCents = max(0, $termBalanceCents - $paidCents);
                    $paymentFieldErrors['total_amount'] = 'This fee or term has '
                        . dashboardFormatCurrency($remainingCents / 100)
                        . ' remaining. Enter no more than this amount.';
                    throw new RuntimeException($paymentFieldErrors['total_amount']);
                }

                $receiptNumber = 'REQ-' . $studentId . '-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));
                $saveRequest = $connection->prepare(
                    "INSERT INTO payments
                        (student_id, enrollment_id, student_fee_id, payment_term, receipt_number,
                         total_amount, payment_method, reference_number, payment_status, remarks)
                     VALUES
                        (:student_id, :enrollment_id, :student_fee_id, :payment_term, :receipt_number,
                         :total_amount, :payment_method, :reference_number, 'Pending', :remarks)"
                );
                $saveRequest->execute([
                    'student_id' => $studentId,
                    'enrollment_id' => (int) $selectedFee['enrollment_id'],
                    'student_fee_id' => $feeId,
                    'payment_term' => $selectedFee['fee_category'] === 'Tuition' ? $paymentValues['payment_term'] : null,
                    'receipt_number' => $receiptNumber,
                    'total_amount' => number_format($feeAmountCents / 100, 2, '.', ''),
                    'payment_method' => $paymentValues['payment_method'],
                    'reference_number' => $paymentValues['reference_number'] !== '' ? $paymentValues['reference_number'] : null,
                    'remarks' => 'Student-submitted payment request; awaiting school verification.',
                ]);
                $connection->commit();
                $_SESSION['student_payment_flash'] = 'Payment request submitted. It will appear as Pending until the school verifies it.';
                header('Location: student-section.php?section=payment', true, 303);
                exit;
            } catch (RuntimeException $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                $paymentError = $exception->getMessage();
            } catch (Throwable $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                throw $exception;
            }
        }
    }
}

$paymentFlash = $_SESSION['student_payment_flash'] ?? '';
unset($_SESSION['student_payment_flash']);
$studentNotifications = [];
if ($sectionKey === 'notifications' && $studentId !== null) {
    $studentNotifications = $fetchRows(
        'SELECT notification_id, notification_type, title, message, is_read, created_at
         FROM student_notifications
         WHERE user_id = :user_id
           AND is_active = 1
         ORDER BY is_read ASC, created_at DESC, notification_id DESC',
        ['user_id' => (int) $user['user_id']]
    );
}

if ($sectionKey === 'profile') {
    if ($studentProfile !== null) {
        $profileFields = [
            ['label' => 'Student number', 'value' => $studentProfile['student_number']],
            ['label' => 'Name', 'value' => trim(implode(' ', array_filter([
                $studentProfile['first_name'],
                $studentProfile['middle_name'],
                $studentProfile['last_name'],
            ], static fn (mixed $value): bool => is_string($value) && $value !== '')))],
            ['label' => 'Student status', 'value' => $studentProfile['student_status']],
            ['label' => 'Program of interest', 'value' => $studentProfile['desired_program_name'] ?? '—'],
            ['label' => 'Date of birth', 'value' => $studentProfile['birth_date'] ?? '—'],
            ['label' => 'Gender', 'value' => $studentProfile['gender'] ?? '—'],
            ['label' => 'Email', 'value' => $studentProfile['email'] ?: ($studentProfile['account_email'] ?? '—')],
            ['label' => 'Phone', 'value' => $studentProfile['phone'] ?: ($studentProfile['account_phone'] ?? '—')],
            ['label' => 'Address', 'value' => trim(implode(', ', array_filter([
                $studentProfile['address'],
                $studentProfile['city'],
                $studentProfile['province'],
            ], static fn (mixed $value): bool => is_string($value) && $value !== ''))) ?: '—'],
            ['label' => 'Admission date', 'value' => $studentProfile['admission_date'] ?? '—'],
        ];
    }
} elseif ($sectionKey === 'enrollment' && $studentId !== null) {
    $rows = $fetchRows(
        'SELECT e.enrollment_date, e.enrollment_status, e.year_level, e.remarks,
                ay.year_name, p.program_name, sec.section_name
         FROM enrollments e
         LEFT JOIN academic_years ay ON ay.academic_year_id = e.academic_year_id
         LEFT JOIN programs p ON p.program_id = e.program_id
         LEFT JOIN sections sec ON sec.section_id = e.section_id
         WHERE e.student_id = :student_id
         ORDER BY e.created_at DESC, e.enrollment_id DESC',
        ['student_id' => $studentId]
    );
    $tables[] = $table(
        'Enrollment history',
        'Enrollment records linked to your student profile.',
        ['Academic year', 'Program', 'Section', 'Year level', 'Enrollment date', 'Status', 'Notes'],
        array_map(static fn (array $row): array => [
            $row['year_name'] ?? '—',
            $row['program_name'] ?? '—',
            $row['section_name'] ?? '—',
            $row['year_level'] ?? '—',
            $row['enrollment_date'] ?? '—',
            $row['enrollment_status'],
            $row['remarks'] ?: '—',
        ], $rows),
        'No enrollment records are linked to your profile yet.'
    );
} elseif ($sectionKey === 'schedule' && $studentId !== null) {
    $rows = $fetchRows(
        "SELECT su.subject_code, su.subject_name, sec.section_name, sem.semester_name,
                cs.day_of_week, cs.start_time, cs.end_time,
                CONCAT_WS(' - ', r.room_code, r.room_name) AS room,
                cs.schedule_status
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
           AND cs.schedule_status IN ('Active', 'Rescheduled')
         ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                  cs.start_time, su.subject_name",
        [
            'student_id' => $studentId,
            'academic_year_id' => $activeAcademicYearId,
        ]
    );
    $tables[] = $table(
        'Current class schedule',
        'Schedules for active semesters and your enrolled class sections.',
        ['Day', 'Time', 'Subject', 'Section', 'Semester', 'Room', 'Status'],
        array_map(static fn (array $row): array => [
            $row['day_of_week'],
            substr((string) $row['start_time'], 0, 5) . '–' . substr((string) $row['end_time'], 0, 5),
            $row['subject_code'] . ' - ' . $row['subject_name'],
            $row['section_name'],
            $row['semester_name'],
            $row['room'] ?: '—',
            $row['schedule_status'],
        ], $rows),
        'No active class schedules are linked to your current enrollment.'
    );
} elseif ($sectionKey === 'attendance' && $studentId !== null) {
    $rows = $fetchRows(
        'SELECT a.attendance_date, su.subject_code, su.subject_name,
                a.attendance_status, a.time_in, a.time_out, a.remarks
         FROM attendance a
         LEFT JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         LEFT JOIN subjects su ON su.subject_id = ta.subject_id
         WHERE a.student_id = :student_id
         ORDER BY a.attendance_date DESC, a.attendance_id DESC
         LIMIT 100',
        ['student_id' => $studentId]
    );
    $tables[] = $table(
        'Recent attendance',
        'The latest 100 attendance records for your profile.',
        ['Date', 'Subject', 'Status', 'Time in', 'Time out', 'Remarks'],
        array_map(static fn (array $row): array => [
            $row['attendance_date'],
            $row['subject_code'] === null ? '—' : $row['subject_code'] . ' - ' . $row['subject_name'],
            $row['attendance_status'],
            $row['time_in'] === null ? '—' : substr((string) $row['time_in'], 0, 5),
            $row['time_out'] === null ? '—' : substr((string) $row['time_out'], 0, 5),
            $row['remarks'] ?: '—',
        ], $rows),
        'No attendance records are linked to your profile yet.'
    );
} elseif ($sectionKey === 'grades' && $studentId !== null) {
    $scoreRows = $fetchRows(
        "SELECT a.assessment_title, a.assessment_type, su.subject_code, gp.period_name,
                ss.score, a.total_points, ss.remarks, ss.recorded_at
         FROM student_scores ss
         INNER JOIN assessments a ON a.assessment_id = ss.assessment_id
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         LEFT JOIN grading_periods gp ON gp.grading_period_id = a.grading_period_id
         WHERE ss.student_id = :student_id
           AND a.status IN ('Published', 'Completed')
         ORDER BY a.assessment_date DESC, ss.recorded_at DESC
         LIMIT 100",
        ['student_id' => $studentId]
    );
    $termRows = $fetchRows(
        'SELECT su.subject_code, sec.section_name, gp.period_name,
                gr.preliminary_grade, gr.final_grade, gr.remarks, gr.date_encoded
         FROM grade_records gr
         INNER JOIN enrollments e ON e.enrollment_id = gr.enrollment_id
         INNER JOIN teaching_assignments ta
           ON ta.assignment_id = gr.assignment_id
          AND ta.section_id = e.section_id
         INNER JOIN subjects su ON su.subject_id = ta.subject_id
         INNER JOIN sections sec ON sec.section_id = e.section_id
         INNER JOIN grading_periods gp ON gp.grading_period_id = gr.grading_period_id
         WHERE e.student_id = :student_id
         ORDER BY gp.start_date DESC, gr.date_encoded DESC',
        ['student_id' => $studentId]
    );
    $tables[] = $table(
        'Published assessment scores',
        'The latest 100 scores from published or completed assessments.',
        ['Assessment', 'Type', 'Subject', 'Grading period', 'Score', 'Points possible', 'Feedback', 'Recorded'],
        array_map(static fn (array $row): array => [
            $row['assessment_title'], $row['assessment_type'], $row['subject_code'],
            $row['period_name'] ?? '—', $row['score'], $row['total_points'],
            $row['remarks'] ?: '—', $row['recorded_at'],
        ], $scoreRows),
        'No published assessment scores are available yet.'
    );
    $tables[] = $table(
        'Term grades',
        'Term grades recorded for your enrolled class sections.',
        ['Subject', 'Section', 'Grading period', 'Preliminary grade', 'Final grade', 'Result', 'Recorded'],
        array_map(static fn (array $row): array => [
            $row['subject_code'], $row['section_name'], $row['period_name'],
            $row['preliminary_grade'] ?? '—', $row['final_grade'] ?? '—',
            $row['remarks'], $row['date_encoded'],
        ], $termRows),
        'No term grades are available for your enrollment records yet.'
    );
} elseif ($sectionKey === 'report-card' && $studentId !== null) {
    $rows = $fetchRows(
        "SELECT ay.year_name, gp.period_name, rc.general_average,
                rc.attendance_summary, rc.teacher_remarks, rc.generated_at
         FROM report_cards rc
         INNER JOIN enrollments e ON e.enrollment_id = rc.enrollment_id
         INNER JOIN grading_periods gp ON gp.grading_period_id = rc.grading_period_id
         LEFT JOIN academic_years ay ON ay.academic_year_id = gp.academic_year_id
         WHERE e.student_id = :student_id
           AND rc.status = 'Published'
         ORDER BY rc.generated_at DESC",
        ['student_id' => $studentId]
    );
    $tables[] = $table(
        'Published report cards',
        'Report cards released by the school for your enrollment records.',
        ['Academic year', 'Grading period', 'General average', 'Attendance summary', 'Teacher remarks', 'Published'],
        array_map(static fn (array $row): array => [
            $row['year_name'] ?? '—',
            $row['period_name'],
            $row['general_average'] ?? '—',
            $row['attendance_summary'] ?: '—',
            $row['teacher_remarks'] ?: '—',
            $row['generated_at'],
        ], $rows),
        'No published report cards are available yet.'
    );
} elseif (in_array($sectionKey, ['payments', 'payment', 'financial-report'], true)) {
    $feeRows = [];
    $paymentHistory = [];
    $termBalances = [
        'Prelim' => ['billed_cents' => 0, 'paid_cents' => 0],
        'Midterm' => ['billed_cents' => 0, 'paid_cents' => 0],
        'Final' => ['billed_cents' => 0, 'paid_cents' => 0],
    ];
    $financeTotals = ['billed_cents' => 0, 'paid_cents' => 0, 'outstanding_cents' => 0];
    if ($studentId !== null) {
        $feeRecords = $fetchRows(
            "SELECT sf.student_fee_id, e.enrollment_id, ft.fee_name, ft.fee_category,
                    ay.year_name, sf.amount, sf.discount_amount, sf.due_date, sf.fee_status,
                    ROUND(sf.amount * 100) AS amount_cents,
                    ROUND(COALESCE(sf.discount_amount, 0) * 100) AS discount_cents,
                    COALESCE(p.paid_cents, 0) AS paid_cents,
                    COALESCE(p.prelim_cents, 0) AS prelim_cents,
                    COALESCE(p.midterm_cents, 0) AS midterm_cents,
                    COALESCE(p.final_cents, 0) AS final_cents
             FROM student_fees sf
             INNER JOIN enrollments e ON e.enrollment_id = sf.enrollment_id
             INNER JOIN fee_structures fs ON fs.fee_structure_id = sf.fee_structure_id
             INNER JOIN fee_types ft ON ft.fee_type_id = fs.fee_type_id
             LEFT JOIN academic_years ay ON ay.academic_year_id = fs.academic_year_id
             LEFT JOIN (
                SELECT student_fee_id,
                       SUM(CASE WHEN payment_status = 'Completed' THEN ROUND(total_amount * 100) ELSE 0 END) AS paid_cents,
                       SUM(CASE WHEN payment_status = 'Completed' AND payment_term = 'Prelim' THEN ROUND(total_amount * 100) ELSE 0 END) AS prelim_cents,
                       SUM(CASE WHEN payment_status = 'Completed' AND payment_term = 'Midterm' THEN ROUND(total_amount * 100) ELSE 0 END) AS midterm_cents,
                       SUM(CASE WHEN payment_status = 'Completed' AND payment_term = 'Final' THEN ROUND(total_amount * 100) ELSE 0 END) AS final_cents
                FROM payments
                WHERE student_fee_id IS NOT NULL
                GROUP BY student_fee_id
             ) p ON p.student_fee_id = sf.student_fee_id
             WHERE e.student_id = :student_id
               AND e.academic_year_id = :academic_year_id
               AND e.enrollment_status = 'Enrolled'
             ORDER BY sf.due_date IS NULL, sf.due_date, ft.fee_name",
            [
                'student_id' => $studentId,
                'academic_year_id' => $activeAcademicYearId,
            ]
        );
        foreach ($feeRecords as $feeRecord) {
            $netCents = $feeRecord['fee_status'] === 'Waived'
                ? 0
                : max(0, (int) $feeRecord['amount_cents'] - (int) $feeRecord['discount_cents']);
            $paidCents = (int) $feeRecord['paid_cents'];
            $feeRecord['net_cents'] = $netCents;
            $feeRecord['outstanding_cents'] = max(0, $netCents - $paidCents);
            $feeRecord['installments'] = $feeRecord['fee_category'] === 'Tuition'
                ? financeTuitionInstallmentsInCents($netCents)
                : [];
            $feeRecord['term_paid_cents'] = [
                'Prelim' => (int) $feeRecord['prelim_cents'],
                'Midterm' => (int) $feeRecord['midterm_cents'],
                'Final' => (int) $feeRecord['final_cents'],
            ];
            $feeRecord['display_status'] = $feeRecord['fee_status'] === 'Waived'
                ? 'Waived'
                : ($feeRecord['outstanding_cents'] === 0
                    ? 'Paid'
                    : ($paidCents > 0 ? 'Partially paid' : $feeRecord['fee_status']));
            $financeTotals['billed_cents'] += $netCents;
            $financeTotals['paid_cents'] += $paidCents;
            $financeTotals['outstanding_cents'] += $feeRecord['outstanding_cents'];

            if ($feeRecord['fee_category'] === 'Tuition') {
                foreach ($termBalances as $termName => &$termBalance) {
                    $termBalance['billed_cents'] += $feeRecord['installments'][$termName];
                    $termBalance['paid_cents'] += $feeRecord['term_paid_cents'][$termName];
                }
                unset($termBalance);
            }
            $feeRows[] = $feeRecord;
        }

        $paymentHistory = $fetchRows(
            'SELECT p.payment_date, p.receipt_number, p.total_amount, p.payment_method,
                    p.payment_status, p.payment_term, p.reference_number, p.remarks,
                    ft.fee_name, ay.year_name
             FROM payments p
             INNER JOIN enrollments e ON e.enrollment_id = p.enrollment_id
             LEFT JOIN student_fees sf ON sf.student_fee_id = p.student_fee_id
             LEFT JOIN fee_structures fs ON fs.fee_structure_id = sf.fee_structure_id
             LEFT JOIN fee_types ft ON ft.fee_type_id = fs.fee_type_id
             LEFT JOIN academic_years ay ON ay.academic_year_id = e.academic_year_id
             WHERE p.student_id = :student_id
             ORDER BY p.payment_date DESC, p.payment_id DESC
             LIMIT 100',
            ['student_id' => $studentId]
        );
    }

    $financeTotals['progress'] = $financeTotals['billed_cents'] > 0
        ? min(100, (int) floor($financeTotals['paid_cents'] * 100 / $financeTotals['billed_cents']))
        : 0;
    $unlinkedPaymentTotals = ['count' => 0, 'amount_cents' => 0];
    if ($studentId !== null) {
        $unlinkedPaymentStatement = $connection->prepare(
            "SELECT COUNT(*) AS payment_count,
                    COALESCE(SUM(ROUND(p.total_amount * 100)), 0) AS amount_cents
             FROM payments p
             INNER JOIN enrollments e ON e.enrollment_id = p.enrollment_id
             WHERE p.student_id = :student_id
               AND e.academic_year_id = :academic_year_id
               AND e.enrollment_status = 'Enrolled'
               AND p.payment_status = 'Completed'
               AND p.student_fee_id IS NULL"
        );
        $unlinkedPaymentStatement->execute([
            'student_id' => $studentId,
            'academic_year_id' => $activeAcademicYearId,
        ]);
        $unlinkedRow = $unlinkedPaymentStatement->fetch();
        $unlinkedPaymentTotals = [
            'count' => (int) $unlinkedRow['payment_count'],
            'amount_cents' => (int) $unlinkedRow['amount_cents'],
        ];
    }
} elseif ($sectionKey === 'announcements') {
    $rows = $fetchRows(
        "SELECT title, message, created_at
         FROM announcements
         WHERE status = 'Published'
         ORDER BY created_at DESC
         LIMIT 100"
    );
    $tables[] = $table(
        'School announcements',
        'The latest 100 announcements published for the school community.',
        ['Announcement', 'Message', 'Published'],
        array_map(static fn (array $row): array => [
            $row['title'], $row['message'], $row['created_at'],
        ], $rows),
        'There are no published announcements right now.'
    );
}

$sectionDefinition = $sectionDefinitions[$sectionKey];
$dashboard = [
    'theme' => 'executive',
    'roleLabel' => 'Student',
    'pageTitle' => $sectionDefinition['title'],
    'eyebrow' => $sectionDefinition['eyebrow'],
    'heading' => $sectionDefinition['title'],
    'description' => $sectionDefinition['description'],
    'navigationStyle' => 'student',
    'navItems' => studentNavigationItems($sectionKey, $unreadNotifications),
    'sidebarTitle' => 'Student Workspace',
    'sidebarCaption' => 'Your School Records',
    'homeHref' => 'student-dashboard.php',
    'sectionContent' => true,
    'sectionContentTemplatePath' => dirname(__DIR__) . '/Views/section_content.php',
    'hideAnnouncementFeed' => $sectionKey === 'announcements',
    'bodyClass' => $sectionKey === 'financial-report' ? 'student-report-page' : '',
];

require dirname(__DIR__, 5) . '/app/Shared/dashboard_layout.php';
