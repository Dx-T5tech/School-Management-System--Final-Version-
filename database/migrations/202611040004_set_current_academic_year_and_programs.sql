START TRANSACTION;

UPDATE academic_years
SET year_name = '2026-2027'
WHERE year_name = '2026-2027 Demo'
  AND start_date = '2026-08-01'
  AND end_date = '2027-05-31'
  AND NOT EXISTS (
      SELECT 1
      FROM academic_years AS current_year
      WHERE current_year.year_name = '2026-2027'
  );

UPDATE departments
SET department_code = 'COMPUTING',
    department_name = 'Department of Computing',
    description = 'Computing and information technology programs.'
WHERE department_code = 'DEMO-COMPUTING'
  AND NOT EXISTS (
      SELECT 1
      FROM departments AS current_department
      WHERE current_department.department_code = 'COMPUTING'
  );

INSERT INTO departments (department_code, department_name, description)
SELECT 'COMPUTING', 'Department of Computing', 'Computing and information technology programs.'
WHERE NOT EXISTS (
    SELECT 1 FROM departments WHERE department_code = 'COMPUTING'
);

INSERT INTO departments (department_code, department_name, description)
SELECT 'ENGINEERING', 'Department of Engineering', 'Engineering degree programs.'
WHERE NOT EXISTS (
    SELECT 1 FROM departments WHERE department_code = 'ENGINEERING'
);

UPDATE programs
SET program_code = 'BSIT'
WHERE program_code = 'DEMO-BSIT'
  AND NOT EXISTS (
      SELECT 1
      FROM programs AS current_program
      WHERE current_program.program_code = 'BSIT'
  );

UPDATE programs
SET department_id = (SELECT department_id FROM departments WHERE department_code = 'COMPUTING'),
    program_name = 'Bachelor of Science in Information Technology',
    description = 'Undergraduate degree in information technology.',
    duration_years = 4,
    status = 'Active'
WHERE program_code = 'BSIT';

UPDATE programs
SET department_id = (SELECT department_id FROM departments WHERE department_code = 'ENGINEERING'),
    program_name = 'Bachelor of Science in Computer Engineering',
    description = 'Undergraduate degree in computer engineering.',
    duration_years = 4,
    status = 'Active'
WHERE program_code = 'BSCE';

INSERT INTO programs (
    department_id,
    program_code,
    program_name,
    description,
    duration_years,
    status
)
SELECT d.department_id,
       program.program_code,
       program.program_name,
       program.description,
       4,
       'Active'
FROM (
    SELECT 'BSCS',
           'Bachelor of Science in Computer Science',
           'Undergraduate degree in computer science.',
           'COMPUTING'
    UNION ALL
    SELECT 'BSELE',
           'Bachelor of Science in Electrical Engineering',
           'Undergraduate degree in electrical engineering.',
           'ENGINEERING'
) AS program
INNER JOIN departments AS d ON d.department_code = program.department_code
WHERE NOT EXISTS (
    SELECT 1
    FROM programs AS existing
    WHERE existing.program_code = program.program_code
);

COMMIT;
