<?php

declare(strict_types=1);

function academicActiveYear(PDO $connection, bool $forUpdate = false): ?array
{
    $query = 'SELECT academic_year_id, year_name, start_date, end_date
              FROM academic_years
              WHERE status = :status
              ORDER BY start_date DESC, academic_year_id DESC
              LIMIT 1';
    if ($forUpdate) {
        $query .= ' FOR UPDATE';
    }

    $statement = $connection->prepare($query);
    $statement->execute(['status' => 'Active']);
    $academicYear = $statement->fetch();

    return $academicYear === false ? null : $academicYear;
}

function academicActiveYearId(PDO $connection): ?int
{
    $academicYear = academicActiveYear($connection);

    return $academicYear === null ? null : (int) $academicYear['academic_year_id'];
}
