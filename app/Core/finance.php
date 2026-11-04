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

function financeDefaultFeeSchedule(): array
{
    return [
        ['key' => 'tuition-per-unit', 'name' => 'Tuition/Unit', 'category' => 'Tuition', 'amount' => 300, 'basis' => 'Per unit', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'rotc-cwts', 'name' => 'ROTC / CWTS', 'category' => 'Other', 'amount' => 420, 'basis' => 'Per unit', 'scope' => 'ROTC/CWTS units', 'mode' => 'Manual'],
        ['key' => 'registration', 'name' => 'Registration Fee', 'category' => 'Registration', 'amount' => 220, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'library', 'name' => 'Library', 'category' => 'Library', 'amount' => 250, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'athletic', 'name' => 'Athletic', 'category' => 'Other', 'amount' => 200, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'medical', 'name' => 'Medical', 'category' => 'Other', 'amount' => 200, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'guidance', 'name' => 'Guidance', 'category' => 'Other', 'amount' => 250, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'id', 'name' => 'I.D.', 'category' => 'Other', 'amount' => 15, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'school-organ', 'name' => 'School Organ', 'category' => 'Other', 'amount' => 55, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'activity', 'name' => 'Activity', 'category' => 'Other', 'amount' => 70, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'internet-wifi', 'name' => 'Internet/Wi-Fi', 'category' => 'Other', 'amount' => 350, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'development', 'name' => 'Development Fee', 'category' => 'Other', 'amount' => 500, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'research', 'name' => 'Research', 'category' => 'Other', 'amount' => 100, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'energy', 'name' => 'Energy', 'category' => 'Other', 'amount' => 100, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'insurance', 'name' => 'Insurance', 'category' => 'Other', 'amount' => 100, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'student-instructional-material', 'name' => 'Student Instr. Material and Equip.', 'category' => 'Other', 'amount' => 150, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'security-cctv', 'name' => 'Security CCTV', 'category' => 'Other', 'amount' => 200, 'basis' => 'Fixed', 'scope' => 'All enrolled units', 'mode' => 'Automatic'],
        ['key' => 'science-lab', 'name' => 'Science Lab', 'category' => 'Laboratory', 'amount' => 355, 'basis' => 'Per unit', 'scope' => 'Science Lab units', 'mode' => 'Manual'],
        ['key' => 'major-lab', 'name' => 'Major Lab', 'category' => 'Laboratory', 'amount' => 645, 'basis' => 'Per unit', 'scope' => 'Major Lab units', 'mode' => 'Manual'],
        ['key' => 'computer-lab', 'name' => 'Computer Lab', 'category' => 'Laboratory', 'amount' => 645, 'basis' => 'Per unit', 'scope' => 'Computer Lab units', 'mode' => 'Manual'],
    ];
}

function financeEnsureDefaultFeeSchedule(PDO $connection, int $academicYearId): void
{
    $semesters = $connection->prepare(
        'SELECT semester_id
         FROM semesters
         WHERE academic_year_id = :academic_year_id
         ORDER BY start_date, semester_id'
    );
    $semesters->execute(['academic_year_id' => $academicYearId]);
    $semesterIds = array_map('intval', $semesters->fetchAll(PDO::FETCH_COLUMN));
    if ($semesterIds === []) {
        return;
    }

    $findType = $connection->prepare(
        'SELECT fee_type_id FROM fee_types WHERE fee_name = :fee_name LIMIT 1'
    );
    $createType = $connection->prepare(
        "INSERT INTO fee_types (fee_name, fee_category, description, status)
         VALUES (:fee_name, :fee_category, :description, 'Active')
         ON DUPLICATE KEY UPDATE fee_type_id = LAST_INSERT_ID(fee_type_id)"
    );
    $findStructure = $connection->prepare(
        'SELECT fee_structure_id
         FROM fee_structures
         WHERE default_schedule_key = :default_schedule_key
         LIMIT 1'
    );
    $createStructure = $connection->prepare(
        'INSERT INTO fee_structures
            (academic_year_id, semester_id, fee_type_id, amount, billing_basis,
             unit_scope, assessment_mode, default_schedule_key)
         VALUES
            (:academic_year_id, :semester_id, :fee_type_id, :amount, :billing_basis,
             :unit_scope, :assessment_mode, :default_schedule_key)'
    );

    foreach (financeDefaultFeeSchedule() as $fee) {
        $findType->execute(['fee_name' => $fee['name']]);
        $feeTypeId = $findType->fetchColumn();
        if ($feeTypeId === false) {
            $createType->execute([
                'fee_name' => $fee['name'],
                'fee_category' => $fee['category'],
                'description' => 'Built-in fee schedule rate; billed once per academic semester.',
            ]);
            $feeTypeId = (int) $connection->lastInsertId();
        }

        foreach ($semesterIds as $semesterId) {
            $scheduleKey = $academicYearId . ':' . $semesterId . ':' . $fee['key'];
            $findStructure->execute(['default_schedule_key' => $scheduleKey]);
            if ($findStructure->fetchColumn() !== false) {
                continue;
            }

            $createStructure->execute([
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'fee_type_id' => (int) $feeTypeId,
                'amount' => number_format((float) $fee['amount'], 2, '.', ''),
                'billing_basis' => $fee['basis'],
                'unit_scope' => $fee['scope'],
                'assessment_mode' => $fee['mode'],
                'default_schedule_key' => $scheduleKey,
            ]);
        }
    }

    $createHandbookType = $connection->prepare(
        "INSERT INTO fee_types (fee_name, fee_category, description, status)
         VALUES ('Handbook', 'Other', 'No handbook charge is set in the built-in schedule.', 'Active')
         ON DUPLICATE KEY UPDATE fee_type_id = fee_type_id"
    );
    $createHandbookType->execute();
}

function financeActiveSemesterId(PDO $connection, int $academicYearId): ?int
{
    $statement = $connection->prepare(
        "SELECT semester_id
         FROM semesters
         WHERE academic_year_id = :academic_year_id
           AND status = 'Active'
         ORDER BY start_date DESC, semester_id DESC
         LIMIT 1"
    );
    $statement->execute(['academic_year_id' => $academicYearId]);
    $semesterId = $statement->fetchColumn();

    return $semesterId === false ? null : (int) $semesterId;
}

function financeGenerateStudentFeesForEnrollment(PDO $connection, int $enrollmentId): int
{
    $enrollmentQuery = $connection->prepare(
        "SELECT enrollment_id, academic_year_id, program_id, year_level
         FROM enrollments
         WHERE enrollment_id = :enrollment_id
           AND enrollment_status = 'Enrolled'
         LIMIT 1"
    );
    $enrollmentQuery->execute(['enrollment_id' => $enrollmentId]);
    $enrollment = $enrollmentQuery->fetch();
    if ($enrollment === false) {
        return 0;
    }

    $academicYearId = (int) $enrollment['academic_year_id'];
    financeEnsureDefaultFeeSchedule($connection, $academicYearId);
    $semesterId = financeActiveSemesterId($connection, $academicYearId);
    if ($semesterId === null) {
        return 0;
    }

    $unitsQuery = $connection->prepare(
        "SELECT COALESCE(SUM(COALESCE(c.units_override, s.units, 0)), 0)
         FROM curriculum c
         INNER JOIN subjects s ON s.subject_id = c.subject_id
         WHERE c.program_id = :program_id
           AND c.academic_year_id = :academic_year_id
           AND c.year_level = :year_level
           AND c.semester_number = (
               SELECT COUNT(*) + 1
               FROM semesters earlier
               WHERE earlier.academic_year_id = :semester_year_id
                 AND earlier.start_date < (
                     SELECT current_semester.start_date
                     FROM semesters current_semester
                     WHERE current_semester.semester_id = :semester_id
                 )
           )"
    );
    $unitsQuery->execute([
        'program_id' => (int) $enrollment['program_id'],
        'academic_year_id' => $academicYearId,
        'year_level' => (int) $enrollment['year_level'],
        'semester_year_id' => $academicYearId,
        'semester_id' => $semesterId,
    ]);
    $semesterUnits = (float) $unitsQuery->fetchColumn();

    $structures = $connection->prepare(
        "SELECT fs.fee_structure_id, fs.amount, fs.billing_basis, fs.assessment_mode,
                fs.unit_scope
         FROM fee_structures fs
         INNER JOIN fee_types ft ON ft.fee_type_id = fs.fee_type_id
         WHERE fs.academic_year_id = :academic_year_id
           AND (fs.semester_id = :semester_id OR fs.semester_id IS NULL)
           AND (fs.program_id IS NULL OR fs.program_id = :program_id)
           AND (fs.year_level IS NULL OR fs.year_level = :year_level)
           AND ft.status = 'Active'
           AND fs.assessment_mode = 'Automatic'
         ORDER BY fs.fee_structure_id"
    );
    $structures->execute([
        'academic_year_id' => $academicYearId,
        'semester_id' => $semesterId,
        'program_id' => $enrollment['program_id'] === null ? null : (int) $enrollment['program_id'],
        'year_level' => (int) $enrollment['year_level'],
    ]);
    $insertFee = $connection->prepare(
        "INSERT INTO student_fees
            (enrollment_id, fee_structure_id, amount, discount_amount, due_date, fee_status)
         VALUES
            (:enrollment_id, :fee_structure_id, :amount, 0, NULL, 'Unpaid')
         ON DUPLICATE KEY UPDATE student_fee_id = student_fee_id"
    );
    $generated = 0;

    foreach ($structures->fetchAll() as $structure) {
        $unitCount = match ($structure['unit_scope']) {
            'All enrolled units' => $semesterUnits,
            default => 0.0,
        };
        $amount = $structure['billing_basis'] === 'Per unit'
            ? round((float) $structure['amount'] * $unitCount, 2)
            : round((float) $structure['amount'], 2);
        if ($amount <= 0) {
            continue;
        }

        $insertFee->execute([
            'enrollment_id' => $enrollmentId,
            'fee_structure_id' => (int) $structure['fee_structure_id'],
            'amount' => number_format($amount, 2, '.', ''),
        ]);
        $generated += $insertFee->rowCount() > 0 ? 1 : 0;
    }

    return $generated;
}

function financeGenerateStudentFeesForStructure(PDO $connection, int $feeStructureId): int
{
    $structureQuery = $connection->prepare(
        "SELECT academic_year_id, semester_id, assessment_mode
         FROM fee_structures
         WHERE fee_structure_id = :fee_structure_id
         LIMIT 1"
    );
    $structureQuery->execute(['fee_structure_id' => $feeStructureId]);
    $structure = $structureQuery->fetch();
    if ($structure === false || $structure['assessment_mode'] !== 'Automatic') {
        return 0;
    }

    $activeSemesterId = financeActiveSemesterId($connection, (int) $structure['academic_year_id']);
    if ($activeSemesterId === null
        || ($structure['semester_id'] !== null && (int) $structure['semester_id'] !== $activeSemesterId)) {
        return 0;
    }

    $enrollments = $connection->prepare(
        "SELECT enrollment_id
         FROM enrollments
         WHERE academic_year_id = :academic_year_id
           AND enrollment_status = 'Enrolled'
         ORDER BY enrollment_id"
    );
    $enrollments->execute(['academic_year_id' => (int) $structure['academic_year_id']]);
    $generated = 0;
    foreach ($enrollments->fetchAll(PDO::FETCH_COLUMN) as $enrollmentId) {
        $generated += financeGenerateStudentFeesForEnrollment($connection, (int) $enrollmentId);
    }

    return $generated;
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
                    . dashboardFormatCurrency(max(0, (int) $fee['net_cents'] - (int) $fee['paid_cents']) / 100) . '.',
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
