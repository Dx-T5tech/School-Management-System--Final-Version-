<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/app/Core/admin.php';

function teacherLifecycleCreateBaseUrl(): string
{
    $configuredBaseUrl = getenv('APP_BASE_URL');
    if ($configuredBaseUrl !== false && trim($configuredBaseUrl) !== '') {
        $baseUrl = trim($configuredBaseUrl);
        $parts = parse_url($baseUrl);
        if (
            filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || !isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            throw new RuntimeException('APP_BASE_URL must be an absolute HTTP or HTTPS base URL without credentials or query parameters.');
        }

        return rtrim($baseUrl, '/');
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!is_string($host) || preg_match('/^[A-Za-z0-9.-]+(?::[0-9]{1,5})?$/', $host) !== 1) {
        throw new RuntimeException('APP_BASE_URL must be configured before creating a teacher setup link.');
    }

    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $scriptDirectory = str_replace(
        '\\',
        '/',
        dirname(is_string($_SERVER['SCRIPT_NAME'] ?? null) ? $_SERVER['SCRIPT_NAME'] : '/')
    );
    $scriptSegments = array_values(array_filter(
        explode('/', trim($scriptDirectory, '/')),
        static fn (string $segment): bool => $segment !== ''
    ));
    $encodedScriptPath = $scriptSegments === []
        ? ''
        : '/' . implode('/', array_map(
            static fn (string $segment): string => rawurlencode(rawurldecode($segment)),
            $scriptSegments
        ));

    return $scheme . '://' . $host . $encodedScriptPath;
}

function teacherLifecycleApplyDueResignations(PDO $connection, int $actorUserId): void
{
    $dueResignations = $connection->query(
        "SELECT resignation_id
         FROM teacher_resignations
         WHERE resignation_status = 'Scheduled'
           AND effective_date <= CURRENT_DATE()
         ORDER BY effective_date, resignation_id"
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($dueResignations as $resignationId) {
        $connection->beginTransaction();
        try {
            $resignationQuery = $connection->prepare(
                "SELECT resignation_id, teacher_id, effective_date
                 FROM teacher_resignations
                 WHERE resignation_id = :resignation_id
                   AND resignation_status = 'Scheduled'
                   AND effective_date <= CURRENT_DATE()
                 FOR UPDATE"
            );
            $resignationQuery->execute(['resignation_id' => (int) $resignationId]);
            $resignation = $resignationQuery->fetch();
            if ($resignation === false) {
                $connection->commit();
                continue;
            }

            $teacherQuery = $connection->prepare(
                'SELECT user_id FROM teachers WHERE teacher_id = :teacher_id FOR UPDATE'
            );
            $teacherQuery->execute(['teacher_id' => (int) $resignation['teacher_id']]);
            $teacher = $teacherQuery->fetch();
            if ($teacher === false) {
                throw new RuntimeException('A scheduled resignation no longer has a teacher profile.');
            }

            $markResigned = $connection->prepare(
                "UPDATE teachers
                 SET employment_status = 'Resigned'
                 WHERE teacher_id = :teacher_id"
            );
            $markResigned->execute(['teacher_id' => (int) $resignation['teacher_id']]);

            if ($teacher['user_id'] !== null) {
                $disableAccount = $connection->prepare(
                    "UPDATE users SET account_status = 'Inactive' WHERE user_id = :user_id"
                );
                $disableAccount->execute(['user_id' => (int) $teacher['user_id']]);
            }

            $endAssignments = $connection->prepare(
                "UPDATE teaching_assignments ta
                 INNER JOIN sections sec ON sec.section_id = ta.section_id
                 INNER JOIN academic_years ay ON ay.academic_year_id = sec.academic_year_id
                 SET ta.assignment_status = 'Ended',
                     ta.ended_at = :effective_date
                 WHERE ta.teacher_id = :teacher_id
                   AND ta.assignment_status = 'Active'
                   AND ay.end_date >= :year_end_date"
            );
            $endAssignments->execute([
                'effective_date' => $resignation['effective_date'],
                'teacher_id' => (int) $resignation['teacher_id'],
                'year_end_date' => $resignation['effective_date'],
            ]);

            $completeResignation = $connection->prepare(
                "UPDATE teacher_resignations
                 SET resignation_status = 'Completed'
                 WHERE resignation_id = :resignation_id"
            );
            $completeResignation->execute(['resignation_id' => (int) $resignation['resignation_id']]);

            adminRecordAudit(
                $connection,
                $actorUserId,
                'Complete resignation',
                'Teacher Management',
                'Applied resignation ' . (int) $resignation['resignation_id']
                    . ' for teacher ' . (int) $resignation['teacher_id']
                    . ' effective ' . $resignation['effective_date']
                    . '; future teaching assignments were ended and linked account access was disabled.'
            );
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }
}

function teacherLifecycleRefreshLeaveStatuses(PDO $connection): void
{
    $onLeave = $connection->prepare(
        "UPDATE teachers t
         SET t.employment_status = 'On Leave'
         WHERE t.employment_status = 'Active'
           AND EXISTS (
               SELECT 1
               FROM teacher_leave_requests lr
               WHERE lr.teacher_id = t.teacher_id
                 AND lr.request_status = 'Approved'
                 AND CURRENT_DATE() BETWEEN lr.start_date AND lr.end_date
           )"
    );
    $onLeave->execute();

    $returnFromLeave = $connection->prepare(
        "UPDATE teachers t
         SET t.employment_status = 'Active'
         WHERE t.employment_status = 'On Leave'
           AND EXISTS (
               SELECT 1
               FROM teacher_leave_requests lr
               WHERE lr.teacher_id = t.teacher_id
                 AND lr.request_status = 'Approved'
                 AND lr.end_date < CURRENT_DATE()
           )
           AND NOT EXISTS (
               SELECT 1
               FROM teacher_leave_requests lr
               WHERE lr.teacher_id = t.teacher_id
                 AND lr.request_status = 'Approved'
                 AND CURRENT_DATE() BETWEEN lr.start_date AND lr.end_date
           )"
    );
    $returnFromLeave->execute();
}
