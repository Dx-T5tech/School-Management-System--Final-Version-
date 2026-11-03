<?php

declare(strict_types=1);

function financeTuitionInstallmentsInCents(int $netTuitionCents): array
{
    $netTuitionCents = max(0, $netTuitionCents);
    $baseInstallment = intdiv($netTuitionCents, 3);
    $remainder = $netTuitionCents % 3;

    return [
        'Prelim' => $baseInstallment + ($remainder > 0 ? 1 : 0),
        'Midterm' => $baseInstallment + ($remainder > 1 ? 1 : 0),
        'Final' => $baseInstallment,
    ];
}

function financeCreateStudentNotification(
    PDO $connection,
    int $userId,
    string $notificationKey,
    string $type,
    string $title,
    string $message
): void {
    $statement = $connection->prepare(
        'INSERT INTO student_notifications
            (user_id, notification_key, notification_type, title, message)
         VALUES
            (:user_id, :notification_key, :notification_type, :title, :message)
         ON DUPLICATE KEY UPDATE notification_id = notification_id'
    );
    $statement->execute([
        'user_id' => $userId,
        'notification_key' => $notificationKey,
        'notification_type' => $type,
        'title' => $title,
        'message' => $message,
    ]);
}

function financeSyncStudentFeeNotifications(
    PDO $connection,
    int $userId,
    int $studentId,
    int $academicYearId
): void {
    $connection->beginTransaction();
    try {
        $resolve = $connection->prepare(
            "UPDATE student_notifications
             SET is_active = 0
             WHERE user_id = :user_id
               AND notification_key LIKE 'fee-due-%'
               AND is_active = 1"
        );
        $resolve->execute(['user_id' => $userId]);

        $fees = $connection->prepare(
            "SELECT sf.student_fee_id, ft.fee_name, sf.due_date,
                    ROUND(GREATEST(sf.amount - COALESCE(sf.discount_amount, 0), 0) * 100) AS net_cents,
                    COALESCE(p.paid_cents, 0) AS paid_cents,
                    DATEDIFF(sf.due_date, CURRENT_DATE()) AS days_until_due
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
               AND sf.fee_status <> 'Waived'
               AND sf.due_date IS NOT NULL
               AND sf.due_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
               AND GREATEST(sf.amount - COALESCE(sf.discount_amount, 0), 0) * 100 > COALESCE(p.paid_cents, 0)"
        );
        $fees->execute([
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
        ]);
        $save = $connection->prepare(
            "INSERT INTO student_notifications
                (user_id, notification_key, notification_type, title, message)
             VALUES
                (:user_id, :notification_key, 'Fee due', :title, :message)
             ON DUPLICATE KEY UPDATE
                is_read = IF(is_active = 0, 0, is_read),
                read_at = IF(is_active = 0, NULL, read_at),
                created_at = IF(is_active = 0, CURRENT_TIMESTAMP, created_at),
                title = VALUES(title),
                message = VALUES(message),
                is_active = 1"
        );

        foreach ($fees->fetchAll() as $fee) {
            $overdue = (int) $fee['days_until_due'] < 0;
            $dueDescription = $overdue
                ? 'was due ' . abs((int) $fee['days_until_due']) . ' day(s) ago'
                : ((int) $fee['days_until_due'] === 0
                    ? 'is due today'
                    : 'is due in ' . (int) $fee['days_until_due'] . ' day(s)');
            $save->execute([
                'user_id' => $userId,
                'notification_key' => 'fee-due-' . (int) $fee['student_fee_id'] . '-' . ($overdue ? 'overdue' : 'upcoming'),
                'title' => $overdue ? 'Fee payment overdue' : 'Fee payment due soon',
                'message' => $fee['fee_name'] . ' ' . $dueDescription . '. Remaining balance: '
                    . number_format(max(0, (int) $fee['net_cents'] - (int) $fee['paid_cents']) / 100, 2) . '.',
            ]);
        }

        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
}

function financeStudentUnreadNotificationCount(PDO $connection, int $userId): int
{
    $statement = $connection->prepare(
        'SELECT COUNT(*)
         FROM student_notifications
         WHERE user_id = :user_id
           AND is_active = 1
           AND is_read = 0'
    );
    $statement->execute(['user_id' => $userId]);

    return (int) $statement->fetchColumn();
}
