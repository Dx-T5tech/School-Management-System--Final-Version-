-- Add profile-only demo teachers and distribute screenshot schedule
-- assignments so each teacher has no overlapping active schedule slots.
CREATE TEMPORARY TABLE demo_teacher_assignment_map (
    subject_code VARCHAR(50) NOT NULL,
    section_name VARCHAR(100) NOT NULL,
    program_code VARCHAR(50) NOT NULL,
    year_level INT NOT NULL,
    academic_year_name VARCHAR(50) NOT NULL,
    semester_name VARCHAR(100) NOT NULL,
    employee_number VARCHAR(50) NOT NULL,
    PRIMARY KEY (
        subject_code,
        section_name,
        program_code,
        year_level,
        academic_year_name,
        semester_name
    )
) ENGINE=InnoDB;

INSERT INTO demo_teacher_assignment_map (
    subject_code,
    section_name,
    program_code,
    year_level,
    academic_year_name,
    semester_name,
    employee_number
) VALUES
    ('CCFUND','IT1C','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-005'),
    ('CPROG1','IT1C','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-011'),
    ('CWTS11','IT1C','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-007'),
    ('ENGPLU1','IT1C','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-008'),
    ('FCMATH','IT1C','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-013'),
    ('GENED5','IT1C','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-003'),
    ('CCFUND','IT1A','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-002'),
    ('CPROG1','IT1A','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-011'),
    ('CWTS11','IT1A','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-014'),
    ('ENGPLU1','IT1A','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-009'),
    ('FCMATH','IT1A','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-012'),
    ('GENED5','IT1A','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-012'),
    ('GENED6','IT1A','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-010'),
    ('GENED7','IT1A','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-013'),
    ('GENEL1','IT1A','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-010'),
    ('CCFUND','IT1B','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-008'),
    ('CPROG1','IT1B','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-003'),
    ('CWTS11','IT1B','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-012'),
    ('ENGPLU1','IT1B','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-003'),
    ('FCMATH','IT1B','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-008'),
    ('GENED5','IT1B','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-014'),
    ('GENED6','IT1B','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-009'),
    ('GENED7','IT1B','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-012'),
    ('GENEL1','IT1B','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-013'),
    ('CSO500','CS4A','BSCS',4,'2026-2027','1st Semester','DEMO-EMP-001'),
    ('CSO501','CS4A','BSCS',4,'2026-2027','1st Semester','DEMO-EMP-014'),
    ('ECELE1','EN4A','BSELE',4,'2026-2027','1st Semester','DEMO-EMP-002'),
    ('GENEL3','EN4A','BSELE',4,'2026-2027','1st Semester','DEMO-EMP-003'),
    ('COMORG','CE4A','BSCE',4,'2026-2027','1st Semester','DEMO-EMP-013'),
    ('ECONOM','EN2B','BSELE',2,'2026-2027','1st Semester','DEMO-EMP-002'),
    ('GENEL1','EN2B','BSELE',2,'2026-2027','1st Semester','DEMO-EMP-006'),
    ('CADRAF','EN2A','BSELE',2,'2026-2027','1st Semester','DEMO-EMP-002'),
    ('ECONOM','EN2A','BSELE',2,'2026-2027','1st Semester','DEMO-EMP-011'),
    ('GENEL1','EN2A','BSELE',2,'2026-2027','1st Semester','DEMO-EMP-014'),
    ('CADRAF','EN2B','BSELE',2,'2026-2027','1st Semester','DEMO-EMP-006'),
    ('COELE3','CE4A','BSCE',4,'2026-2027','1st Semester','DEMO-EMP-005'),
    ('EMTECH','CE4A','BSCE',4,'2026-2027','1st Semester','DEMO-EMP-011'),
    ('GENED6','IT1C','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-006'),
    ('GENED7','IT1C','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-001'),
    ('GENEL1','IT1C','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-005'),
    ('CCFUND','IT1D','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-010'),
    ('CPROG1','IT1D','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-005'),
    ('CWTS11','IT1D','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-009'),
    ('ENGPLU1','IT1D','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-007'),
    ('FCMATH','IT1D','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-004'),
    ('GENED5','IT1D','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-004'),
    ('GENED6','IT1D','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-010'),
    ('GENED7','IT1D','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-008'),
    ('GENEL1','IT1D','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-013'),
    ('DSALGO','IT2A','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-013'),
    ('CCFUND','IT1G','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-010'),
    ('CPROG1','IT1G','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-001'),
    ('CWTS11','IT1G','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-007'),
    ('ENGPLU1','IT1G','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-010'),
    ('FCMATH','IT1G','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-012'),
    ('GENED5','IT1G','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-013'),
    ('GENED6','IT1G','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-002'),
    ('GENED7','IT1G','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-009'),
    ('GENEL1','IT1G','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-011'),
    ('CCFUND','IT1E','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-009'),
    ('CPROG1','IT1E','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-014'),
    ('CWTS11','IT1E','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-005'),
    ('ENGPLU1','IT1E','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-003'),
    ('FCMATH','IT1E','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-010'),
    ('GENED5','IT1E','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-005'),
    ('GENED6','IT1E','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-007'),
    ('GENED7','IT1E','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-003'),
    ('GENEL1','IT1E','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-006'),
    ('CCFUND','IT1F','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-005'),
    ('CPROG1','IT1F','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-004'),
    ('CWTS11','IT1F','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-011'),
    ('ENGPLU1','IT1F','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-007'),
    ('FCMATH','IT1F','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-005'),
    ('GENED5','IT1F','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-004'),
    ('GENED6','IT1F','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-002'),
    ('GENED7','IT1F','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-014'),
    ('GENEL1','IT1F','BSIT',1,'2026-2027','1st Semester','DEMO-EMP-001'),
    ('GENEL2','IT2A','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-008'),
    ('GENEL3','IT2A','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-014'),
    ('OOPROG','IT2A','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-009'),
    ('PLTECH','IT2A','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-001'),
    ('DSALGO','IT2B','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-004'),
    ('GENEL2','IT2B','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-012'),
    ('GENEL3','IT2B','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-008'),
    ('OOPROG','IT2B','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-008'),
    ('PLTECH','IT2B','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-012'),
    ('DSALGO','IT2E','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-012'),
    ('GENEL2','IT2E','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-012'),
    ('GENEL3','IT2E','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-009'),
    ('OOPROG','IT2E','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-010'),
    ('PLTECH','IT2E','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-003'),
    ('DSALGO','IT2C','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-002'),
    ('GENEL2','IT2C','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-002'),
    ('GENEL3','IT2C','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-011'),
    ('PLTECH','IT2C','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-006'),
    ('DSALGO','IT2D','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-006'),
    ('GENEL2','IT2D','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-014'),
    ('GENEL3','IT2D','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-003'),
    ('OOPROG','IT2D','BSIT',2,'2026-2027','1st Semester','DEMO-EMP-001'),
    ('IASEC2','IT4A','BSIT',4,'2026-2027','1st Semester','DEMO-EMP-006'),
    ('THESI1','IT4A','BSIT',4,'2026-2027','1st Semester','DEMO-EMP-007'),
    ('THESI1','IT4B','BSIT',4,'2026-2027','1st Semester','DEMO-EMP-004'),
    ('NETWO2','IT3B','BSIT',3,'2026-2027','1st Semester','DEMO-EMP-004'),
    ('ADETEC','IT3B','BSIT',3,'2026-2027','2nd Semester','DEMO-EMP-008'),
    ('NETWO2','IT3C','BSIT',3,'2026-2027','1st Semester','DEMO-EMP-007'),
    ('ADETEC','IT3C','BSIT',3,'2026-2027','2nd Semester','DEMO-EMP-007'),
    ('NETWO2','IT3A','BSIT',3,'2026-2027','1st Semester','DEMO-EMP-010'),
    ('ADETEC','IT3A','BSIT',3,'2026-2027','2nd Semester','DEMO-EMP-001'),
    ('ADETEC','IT3D','BSIT',3,'2026-2027','2nd Semester','DEMO-EMP-009');

START TRANSACTION;

INSERT INTO teachers (
    user_id,
    employee_number,
    first_name,
    last_name,
    employment_status
)
SELECT NULL, requested.employee_number, 'Demo', requested.last_name, 'Active'
FROM (
    SELECT 'DEMO-EMP-002' AS employee_number, 'Teacher 02' AS last_name
    UNION ALL SELECT 'DEMO-EMP-003', 'Teacher 03'
    UNION ALL SELECT 'DEMO-EMP-004', 'Teacher 04'
    UNION ALL SELECT 'DEMO-EMP-005', 'Teacher 05'
    UNION ALL SELECT 'DEMO-EMP-006', 'Teacher 06'
    UNION ALL SELECT 'DEMO-EMP-007', 'Teacher 07'
    UNION ALL SELECT 'DEMO-EMP-008', 'Teacher 08'
    UNION ALL SELECT 'DEMO-EMP-009', 'Teacher 09'
    UNION ALL SELECT 'DEMO-EMP-010', 'Teacher 10'
    UNION ALL SELECT 'DEMO-EMP-011', 'Teacher 11'
    UNION ALL SELECT 'DEMO-EMP-012', 'Teacher 12'
    UNION ALL SELECT 'DEMO-EMP-013', 'Teacher 13'
    UNION ALL SELECT 'DEMO-EMP-014', 'Teacher 14'
) AS requested
WHERE NOT EXISTS (
    SELECT 1
    FROM teachers existing
    WHERE existing.employee_number = requested.employee_number
);

UPDATE teaching_assignments AS assignment
INNER JOIN sections AS section
    ON section.section_id = assignment.section_id
INNER JOIN programs AS program
    ON program.program_id = section.program_id
INNER JOIN academic_years AS academic_year
    ON academic_year.academic_year_id = section.academic_year_id
INNER JOIN subjects AS subject
    ON subject.subject_id = assignment.subject_id
INNER JOIN semesters AS semester
    ON semester.semester_id = assignment.semester_id
INNER JOIN demo_teacher_assignment_map AS assignment_map
    ON assignment_map.subject_code = subject.subject_code
   AND assignment_map.section_name = section.section_name
   AND assignment_map.program_code = program.program_code
   AND assignment_map.year_level = section.year_level
   AND assignment_map.academic_year_name = academic_year.year_name
   AND assignment_map.semester_name = semester.semester_name
INNER JOIN teachers AS teacher
    ON teacher.employee_number = assignment_map.employee_number
SET assignment.teacher_id = teacher.teacher_id
WHERE assignment.assignment_status = 'Active'
  AND EXISTS (
      SELECT 1
      FROM class_schedules AS schedule
      WHERE schedule.assignment_id = assignment.assignment_id
        AND schedule.schedule_status = 'Active'
  );

COMMIT;

DROP TEMPORARY TABLE demo_teacher_assignment_map;
