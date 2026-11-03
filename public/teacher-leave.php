<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';
require_once dirname(__DIR__) . '/app/Core/teacher.php';

$user = requireDashboardUser('Teacher');
$connection = getDatabaseConnection();
$teacherQuery = $connection->prepare(
    'SELECT teacher_id, employment_status FROM teachers WHERE user_id = :user_id LIMIT 1'
);
$teacherQuery->execute(['user_id' => (int) $user['user_id']]);
$teacher = $teacherQuery->fetch();
$errorMessage = '';
$fieldErrors = [];
$oldInput = [];
$csrfToken = appCsrfToken();
$flashMessage = $_SESSION['teacher_flash'] ?? null;
unset($_SESSION['teacher_flash']);
$isValidDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (is_string($key) && is_string($value)) {
            $oldInput[$key] = trim($value);
        }
    }

    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your security token has expired. Reload this page and try again.';
    } elseif ($teacher === false) {
        $errorMessage = 'A teacher profile is not linked to your account. Contact the school administrator.';
    } elseif ($_POST['action'] === 'submit_leave') {
        $leaveType = is_string($_POST['leave_type'] ?? null) ? trim($_POST['leave_type']) : '';
        $startDate = is_string($_POST['start_date'] ?? null) ? trim($_POST['start_date']) : '';
        $endDate = is_string($_POST['end_date'] ?? null) ? trim($_POST['end_date']) : '';
        $reason = is_string($_POST['reason'] ?? null) ? trim($_POST['reason']) : '';

        if (!in_array($leaveType, ['Sick', 'Personal', 'Vacation', 'Other'], true)) {
            $fieldErrors['leave_type'] = 'Choose an available leave type.';
        }
        if (!$isValidDate($startDate) || $startDate < date('Y-m-d')) {
            $fieldErrors['start_date'] = 'Choose today or a future date.';
        }
        if (!$isValidDate($endDate) || $endDate < $startDate) {
            $fieldErrors['end_date'] = 'Choose an end date on or after the start date.';
        }
        if ($reason === '' || strlen($reason) > 4000) {
            $fieldErrors['reason'] = 'Provide a reason up to 4,000 characters.';
        }

        if ($fieldErrors === []) {
            $overlapQuery = $connection->prepare(
                "SELECT COUNT(*)
                 FROM teacher_leave_requests
                 WHERE teacher_id = :teacher_id
                   AND request_status IN ('Pending', 'Approved')
                   AND start_date <= :requested_end
                   AND end_date >= :requested_start"
            );
            $overlapQuery->execute([
                'teacher_id' => (int) $teacher['teacher_id'],
                'requested_end' => $endDate,
                'requested_start' => $startDate,
            ]);
            if ((int) $overlapQuery->fetchColumn() > 0) {
                $fieldErrors['start_date'] = 'These dates overlap another pending or approved leave request.';
            } else {
                $connection->beginTransaction();
                try {
                    $insertLeave = $connection->prepare(
                        "INSERT INTO teacher_leave_requests (
                            teacher_id, leave_type, start_date, end_date, reason
                         ) VALUES (
                            :teacher_id, :leave_type, :start_date, :end_date, :reason
                         )"
                    );
                    $insertLeave->execute([
                        'teacher_id' => (int) $teacher['teacher_id'],
                        'leave_type' => $leaveType,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'reason' => $reason,
                    ]);
                    teacherRecordAudit(
                        $connection,
                        (int) $user['user_id'],
                        'Submit leave request',
                        'Teacher Management',
                        'Teacher ' . (int) $teacher['teacher_id'] . ' submitted a ' . $leaveType
                            . ' leave request from ' . $startDate . ' to ' . $endDate . '.'
                    );
                    $connection->commit();
                    $_SESSION['teacher_flash'] = 'Your leave request was sent to Admin for review.';
                    header('Location: teacher-leave.php', true, 303);
                    exit;
                } catch (Throwable $exception) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                    throw $exception;
                }
            }
        }
    } elseif (($_POST['action'] ?? null) === 'cancel_leave') {
        $requestId = filter_var(
            $_POST['leave_request_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($requestId === false) {
            $errorMessage = 'Choose a valid leave request.';
        } else {
            $cancelLeave = $connection->prepare(
                "UPDATE teacher_leave_requests
                 SET request_status = 'Cancelled'
                 WHERE leave_request_id = :request_id
                   AND teacher_id = :teacher_id
                   AND request_status = 'Pending'"
            );
            $cancelLeave->execute([
                'request_id' => $requestId,
                'teacher_id' => (int) $teacher['teacher_id'],
            ]);
            if ($cancelLeave->rowCount() !== 1) {
                $errorMessage = 'Only your pending leave requests can be cancelled.';
            } else {
                teacherRecordAudit(
                    $connection,
                    (int) $user['user_id'],
                    'Cancel leave request',
                    'Teacher Management',
                    'Teacher ' . (int) $teacher['teacher_id'] . ' cancelled leave request ' . $requestId . '.'
                );
                $_SESSION['teacher_flash'] = 'Pending leave request cancelled.';
                header('Location: teacher-leave.php', true, 303);
                exit;
            }
        }
    } else {
        $errorMessage = 'Choose a valid leave request action.';
    }
}

$leaveRows = [];
if ($teacher !== false) {
    $leaveQuery = $connection->prepare(
        'SELECT leave_request_id, leave_type, start_date, end_date, reason,
                request_status, requested_at, reviewed_at, review_note
         FROM teacher_leave_requests
         WHERE teacher_id = :teacher_id
         ORDER BY requested_at DESC, leave_request_id DESC'
    );
    $leaveQuery->execute(['teacher_id' => (int) $teacher['teacher_id']]);
    $leaveRows = $leaveQuery->fetchAll();
}

$dashboard = [
    'theme' => 'teacher',
    'roleLabel' => 'Teacher',
    'pageTitle' => 'My Leave Requests',
    'eyebrow' => 'Staff services',
    'heading' => 'My Leave Requests',
    'description' => 'Submit leave for Admin approval and track decisions and notes.',
    'metricsHeading' => '',
    'navItems' => teacherNavigationItems('leave'),
    'navigationStyle' => 'teacher',
    'sidebarTitle' => 'Teacher Portal',
    'sidebarCaption' => 'Teaching Workspace',
    'homeHref' => 'teacher-dashboard.php',
    'sectionContent' => true,
    'sectionContentTemplatePath' => dirname(__DIR__) . '/app/Shared/teacher_leave_content.php',
];

require dirname(__DIR__) . '/app/Shared/dashboard_layout.php';
