<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/Core/academic.php';

function studentNavigationItems(string $activeSection = 'dashboard', int $unreadNotifications = 0): array
{
    $items = [
        ['group' => 'Overview', 'key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'student-dashboard.php'],
        ['group' => 'My records', 'key' => 'profile', 'label' => 'My Profile', 'href' => 'student-section.php?section=profile'],
        ['group' => 'My records', 'key' => 'enrollment', 'label' => 'My Enrollment', 'href' => 'student-section.php?section=enrollment'],
        ['group' => 'School day', 'key' => 'schedule', 'label' => 'My Schedule', 'href' => 'student-section.php?section=schedule'],
        ['group' => 'School day', 'key' => 'attendance', 'label' => 'My Attendance', 'href' => 'student-section.php?section=attendance'],
        ['group' => 'Learning', 'key' => 'grades', 'label' => 'My Grades', 'href' => 'student-section.php?section=grades'],
        ['group' => 'Learning', 'key' => 'report-card', 'label' => 'My Report Card', 'href' => 'student-section.php?section=report-card'],
        ['group' => 'Finance', 'key' => 'payments', 'label' => 'Balance Fees', 'href' => 'student-section.php?section=payments'],
        ['group' => 'Finance', 'key' => 'payment', 'label' => 'Payment', 'href' => 'student-section.php?section=payment'],
        ['group' => 'Finance', 'key' => 'financial-report', 'label' => 'Financial Report', 'href' => 'student-section.php?section=financial-report'],
        ['group' => 'Updates', 'key' => 'notifications', 'label' => 'Notifications', 'href' => 'student-section.php?section=notifications', 'badge' => $unreadNotifications],
        ['group' => 'Updates', 'key' => 'announcements', 'label' => 'Announcements', 'href' => 'student-section.php?section=announcements'],
    ];

    foreach ($items as &$item) {
        $item['current'] = $item['key'] === $activeSection;
    }
    unset($item);

    return $items;
}

function studentHasActiveEnrollment(PDO $connection, int $userId): bool
{
    $statement = $connection->prepare(
        "SELECT EXISTS(
            SELECT 1
            FROM students s
            INNER JOIN enrollments e ON e.student_id = s.student_id
            INNER JOIN academic_years ay
              ON ay.academic_year_id = e.academic_year_id
             AND ay.status = 'Active'
            WHERE s.user_id = :user_id
              AND e.enrollment_status = 'Enrolled'
        )"
    );
    $statement->execute(['user_id' => $userId]);

    return (bool) $statement->fetchColumn();
}

function studentFindProfileId(PDO $connection, int $userId): ?int
{
    $statement = $connection->prepare(
        'SELECT student_id FROM students WHERE user_id = :user_id LIMIT 1'
    );
    $statement->execute(['user_id' => $userId]);
    $studentId = $statement->fetchColumn();

    return $studentId === false ? null : (int) $studentId;
}

function studentFindDesiredProgramId(PDO $connection, int $userId): ?int
{
    $statement = $connection->prepare(
        'SELECT desired_program_id FROM students WHERE user_id = :user_id LIMIT 1'
    );
    $statement->execute(['user_id' => $userId]);
    $programId = $statement->fetchColumn();

    return $programId === false || $programId === null ? null : (int) $programId;
}

function studentAvailablePrograms(PDO $connection): array
{
    $activeAcademicYearId = academicActiveYearId($connection);
    if ($activeAcademicYearId === null) {
        return [];
    }

    $statement = $connection->prepare(
        "SELECT p.program_id, p.program_code, p.program_name, p.description,
                p.department_id, d.department_name,
                (
                    SELECT COUNT(*)
                    FROM sections sec
                    INNER JOIN academic_years ay
                      ON ay.academic_year_id = sec.academic_year_id
                     AND ay.status = 'Active'
                    WHERE sec.program_id = p.program_id
                      AND sec.academic_year_id = :academic_year_id
                      AND sec.status = 'Active'
                      AND (
                            sec.capacity IS NULL
                            OR (
                                SELECT COUNT(*)
                                FROM enrollments e
                                WHERE e.section_id = sec.section_id
                                  AND e.enrollment_status = 'Enrolled'
                            ) < sec.capacity
                      )
                ) AS available_sections
         FROM programs p
         LEFT JOIN departments d ON d.department_id = p.department_id
         WHERE p.status = 'Active'
         ORDER BY d.department_name, p.program_name"
    );
    $statement->execute(['academic_year_id' => $activeAcademicYearId]);

    return $statement->fetchAll();
}

function studentSignInDestination(PDO $connection, int $userId): string
{
    return studentHasActiveEnrollment($connection, $userId)
        ? 'student-dashboard.php'
        : 'student-enrollment.php';
}
