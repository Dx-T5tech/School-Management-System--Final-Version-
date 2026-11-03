-- Add profile-only demo students so each active section has a sample roster
-- of up to 30 enrolled students, without exceeding its configured capacity.
START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS demo_student_slots;
DROP TEMPORARY TABLE IF EXISTS demo_active_section_counts;
DROP TEMPORARY TABLE IF EXISTS demo_students_to_add;

CREATE TEMPORARY TABLE demo_student_slots (
    slot_number INT NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

INSERT INTO demo_student_slots (slot_number) VALUES
    (1), (2), (3), (4), (5), (6), (7), (8), (9), (10),
    (11), (12), (13), (14), (15), (16), (17), (18), (19), (20),
    (21), (22), (23), (24), (25), (26), (27), (28), (29), (30);

CREATE TEMPORARY TABLE demo_active_section_counts AS
SELECT
    sec.section_id,
    sec.section_name,
    sec.year_level,
    sec.capacity,
    sec.program_id,
    p.program_code,
    ay.academic_year_id,
    ay.year_name,
    ay.start_date AS academic_year_start,
    COALESCE((
        SELECT COUNT(*)
        FROM enrollments e
        INNER JOIN students s ON s.student_id = e.student_id
        WHERE e.section_id = sec.section_id
          AND e.academic_year_id = sec.academic_year_id
          AND e.enrollment_status = 'Enrolled'
    ), 0) AS enrolled_count,
    COALESCE((
        SELECT COUNT(*)
        FROM enrollments e
        INNER JOIN students s ON s.student_id = e.student_id
        WHERE e.section_id = sec.section_id
          AND e.academic_year_id = sec.academic_year_id
          AND e.enrollment_status = 'Enrolled'
          AND s.student_number LIKE 'DEMO-STU-%'
    ), 0) AS demo_enrolled_count
FROM sections sec
INNER JOIN programs p ON p.program_id = sec.program_id
INNER JOIN academic_years ay ON ay.academic_year_id = sec.academic_year_id
WHERE sec.status = 'Active'
  AND ay.status = 'Active';

CREATE TEMPORARY TABLE demo_students_to_add AS
SELECT
    section_counts.section_id,
    section_counts.section_name,
    section_counts.year_level,
    section_counts.program_id,
    section_counts.program_code,
    section_counts.academic_year_id,
    section_counts.year_name,
    section_counts.academic_year_start,
    slots.slot_number,
    CONCAT(
        'DEMO-STU-',
        REPLACE(section_counts.year_name, '-', ''),
        '-',
        section_counts.program_code,
        '-',
        section_counts.section_name,
        '-',
        LPAD(slots.slot_number, 2, '0')
    ) AS student_number
FROM demo_active_section_counts section_counts
INNER JOIN demo_student_slots slots
    ON slots.slot_number > section_counts.demo_enrolled_count
   AND slots.slot_number <= section_counts.demo_enrolled_count
        + GREATEST(
            0,
            LEAST(30, COALESCE(section_counts.capacity, 30))
                - section_counts.enrolled_count
        );

INSERT INTO students (
    user_id,
    desired_program_id,
    student_number,
    first_name,
    last_name,
    admission_date,
    student_status
)
SELECT
    NULL,
    to_add.program_id,
    to_add.student_number,
    'Demo',
    CONCAT('Student ', to_add.section_name, ' ', LPAD(to_add.slot_number, 2, '0')),
    to_add.academic_year_start,
    'Enrolled'
FROM demo_students_to_add to_add
LEFT JOIN students existing ON existing.student_number = to_add.student_number
WHERE existing.student_id IS NULL;

INSERT INTO enrollments (
    student_id,
    academic_year_id,
    program_id,
    section_id,
    year_level,
    enrollment_date,
    enrollment_status,
    remarks
)
SELECT
    s.student_id,
    to_add.academic_year_id,
    to_add.program_id,
    to_add.section_id,
    to_add.year_level,
    to_add.academic_year_start,
    'Enrolled',
    'Fictional demo enrollment for section roster display.'
FROM demo_students_to_add to_add
INNER JOIN students s ON s.student_number = to_add.student_number
LEFT JOIN enrollments existing
    ON existing.student_id = s.student_id
   AND existing.academic_year_id = to_add.academic_year_id
WHERE existing.enrollment_id IS NULL;

DROP TEMPORARY TABLE demo_students_to_add;
DROP TEMPORARY TABLE demo_active_section_counts;
DROP TEMPORARY TABLE demo_student_slots;

COMMIT;
