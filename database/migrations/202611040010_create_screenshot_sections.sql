-- Create the section groups shown in the supplied schedule screenshots for
-- the active 2026-2027 academic year. No teachers or schedules are assigned.
START TRANSACTION;

INSERT INTO sections (
    program_id,
    academic_year_id,
    section_name,
    year_level,
    capacity,
    status
)
SELECT p.program_id,
       ay.academic_year_id,
       requested.section_name,
       requested.year_level,
       40,
       'Active'
FROM (
    SELECT 'BSCS' AS program_code, 'CS1A' AS section_name, 1 AS year_level
    UNION ALL SELECT 'BSCS', 'CS2A', 2
    UNION ALL SELECT 'BSCS', 'CS3A', 3
    UNION ALL SELECT 'BSCS', 'CS4A', 4
    UNION ALL SELECT 'BSIT', 'IT1A', 1
    UNION ALL SELECT 'BSIT', 'IT1B', 1
    UNION ALL SELECT 'BSIT', 'IT1C', 1
    UNION ALL SELECT 'BSIT', 'IT1D', 1
    UNION ALL SELECT 'BSIT', 'IT1E', 1
    UNION ALL SELECT 'BSIT', 'IT1F', 1
    UNION ALL SELECT 'BSIT', 'IT1G', 1
    UNION ALL SELECT 'BSIT', 'IT2A', 2
    UNION ALL SELECT 'BSIT', 'IT2B', 2
    UNION ALL SELECT 'BSIT', 'IT2C', 2
    UNION ALL SELECT 'BSIT', 'IT2D', 2
    UNION ALL SELECT 'BSIT', 'IT2E', 2
    UNION ALL SELECT 'BSIT', 'IT3A', 3
    UNION ALL SELECT 'BSIT', 'IT3B', 3
    UNION ALL SELECT 'BSIT', 'IT3C', 3
    UNION ALL SELECT 'BSIT', 'IT3D', 3
    UNION ALL SELECT 'BSIT', 'IT4A', 4
    UNION ALL SELECT 'BSIT', 'IT4B', 4
    UNION ALL SELECT 'BSELE', 'EN1A', 1
    UNION ALL SELECT 'BSELE', 'EN1B', 1
    UNION ALL SELECT 'BSELE', 'EN2A', 2
    UNION ALL SELECT 'BSELE', 'EN2B', 2
    UNION ALL SELECT 'BSELE', 'EN3A', 3
    UNION ALL SELECT 'BSELE', 'EN4A', 4
    UNION ALL SELECT 'BSCE', 'CE1A', 1
    UNION ALL SELECT 'BSCE', 'CE2A', 2
    UNION ALL SELECT 'BSCE', 'CE3A', 3
    UNION ALL SELECT 'BSCE', 'CE4A', 4
) AS requested
INNER JOIN programs p
    ON p.program_code = requested.program_code
   AND p.status = 'Active'
INNER JOIN academic_years ay
    ON ay.status = 'Active'
WHERE NOT EXISTS (
    SELECT 1
    FROM sections existing
    WHERE existing.academic_year_id = ay.academic_year_id
      AND existing.section_name = requested.section_name
);

COMMIT;
