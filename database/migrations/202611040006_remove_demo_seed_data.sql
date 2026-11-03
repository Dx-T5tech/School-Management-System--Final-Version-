START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS demo_cleanup_student_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_teacher_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_section_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_assignment_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_enrollment_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_assessment_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_period_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_fee_type_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_fee_structure_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_student_fee_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_payment_ids;
DROP TEMPORARY TABLE IF EXISTS demo_cleanup_guardian_ids;

CREATE TEMPORARY TABLE demo_cleanup_student_ids AS
SELECT student_id
FROM students
WHERE student_number LIKE 'DEMO-STU-%';

CREATE TEMPORARY TABLE demo_cleanup_teacher_ids AS
SELECT teacher_id
FROM teachers
WHERE employee_number LIKE 'DEMO-EMP-%';

CREATE TEMPORARY TABLE demo_cleanup_section_ids AS
SELECT section_id
FROM sections
WHERE section_name LIKE 'DEMO-%';

CREATE TEMPORARY TABLE demo_cleanup_assignment_ids AS
SELECT ta.assignment_id
FROM teaching_assignments ta
LEFT JOIN teachers t ON t.teacher_id = ta.teacher_id
LEFT JOIN subjects su ON su.subject_id = ta.subject_id
LEFT JOIN sections sec ON sec.section_id = ta.section_id
WHERE t.employee_number LIKE 'DEMO-EMP-%'
   OR su.subject_code LIKE 'DEMO-%'
   OR sec.section_name LIKE 'DEMO-%';

CREATE TEMPORARY TABLE demo_cleanup_enrollment_ids AS
SELECT e.enrollment_id
FROM enrollments e
LEFT JOIN demo_cleanup_student_ids ds ON ds.student_id = e.student_id
LEFT JOIN demo_cleanup_section_ids dsec ON dsec.section_id = e.section_id
WHERE ds.student_id IS NOT NULL
   OR dsec.section_id IS NOT NULL
   OR e.remarks LIKE 'Fictional demo enrollment%';

CREATE TEMPORARY TABLE demo_cleanup_assessment_ids AS
SELECT a.assessment_id
FROM assessments a
LEFT JOIN demo_cleanup_assignment_ids da ON da.assignment_id = a.assignment_id
WHERE da.assignment_id IS NOT NULL
   OR a.assessment_title LIKE 'DEMO %'
   OR a.description LIKE 'Fictional completed assessment%';

CREATE TEMPORARY TABLE demo_cleanup_period_ids AS
SELECT grading_period_id
FROM grading_periods
WHERE period_name LIKE 'Demo %';

CREATE TEMPORARY TABLE demo_cleanup_fee_type_ids AS
SELECT fee_type_id
FROM fee_types
WHERE fee_name LIKE 'Demo %';

CREATE TEMPORARY TABLE demo_cleanup_fee_structure_ids AS
SELECT fs.fee_structure_id
FROM fee_structures fs
INNER JOIN demo_cleanup_fee_type_ids dft ON dft.fee_type_id = fs.fee_type_id;

CREATE TEMPORARY TABLE demo_cleanup_student_fee_ids AS
SELECT sf.student_fee_id
FROM student_fees sf
LEFT JOIN demo_cleanup_enrollment_ids de ON de.enrollment_id = sf.enrollment_id
LEFT JOIN demo_cleanup_fee_structure_ids dfs ON dfs.fee_structure_id = sf.fee_structure_id
WHERE de.enrollment_id IS NOT NULL
   OR dfs.fee_structure_id IS NOT NULL;

CREATE TEMPORARY TABLE demo_cleanup_payment_ids AS
SELECT p.payment_id
FROM payments p
LEFT JOIN demo_cleanup_enrollment_ids de ON de.enrollment_id = p.enrollment_id
LEFT JOIN demo_cleanup_student_fee_ids dsf ON dsf.student_fee_id = p.student_fee_id
WHERE p.receipt_number LIKE 'DEMO-RCPT-%'
   OR p.reference_number LIKE 'DEMO-REF-%'
   OR p.remarks LIKE 'Fictional payment for finance workflow testing%'
   OR de.enrollment_id IS NOT NULL
   OR dsf.student_fee_id IS NOT NULL;

CREATE TEMPORARY TABLE demo_cleanup_guardian_ids AS
SELECT guardian_id
FROM guardians
WHERE email LIKE 'demo.guardian.%@example.test';

DELETE pa
FROM payment_allocations pa
LEFT JOIN demo_cleanup_payment_ids dp ON dp.payment_id = pa.payment_id
LEFT JOIN demo_cleanup_student_fee_ids dsf ON dsf.student_fee_id = pa.student_fee_id
WHERE dp.payment_id IS NOT NULL OR dsf.student_fee_id IS NOT NULL;

DELETE p
FROM payments p
INNER JOIN demo_cleanup_payment_ids dp ON dp.payment_id = p.payment_id;

DELETE sf
FROM student_fees sf
INNER JOIN demo_cleanup_student_fee_ids dsf ON dsf.student_fee_id = sf.student_fee_id;

DELETE FROM scholarships
WHERE scholarship_name = 'Demo Merit Scholarship'
  AND NOT EXISTS (
      SELECT 1
      FROM student_fees
      WHERE student_fees.scholarship_id = scholarships.scholarship_id
  );

DELETE FROM student_notifications
WHERE notification_key LIKE 'DEMO-%'
   OR title = 'Demo tuition payment recorded';

DELETE a
FROM attendance a
LEFT JOIN demo_cleanup_student_ids ds ON ds.student_id = a.student_id
LEFT JOIN demo_cleanup_enrollment_ids de ON de.enrollment_id = a.enrollment_id
LEFT JOIN demo_cleanup_assignment_ids da ON da.assignment_id = a.assignment_id
WHERE ds.student_id IS NOT NULL
   OR de.enrollment_id IS NOT NULL
   OR da.assignment_id IS NOT NULL
   OR a.remarks LIKE 'Fictional sample attendance record%';

DELETE ss
FROM student_scores ss
LEFT JOIN demo_cleanup_student_ids ds ON ds.student_id = ss.student_id
LEFT JOIN demo_cleanup_assessment_ids da ON da.assessment_id = ss.assessment_id
WHERE ds.student_id IS NOT NULL OR da.assessment_id IS NOT NULL;

DELETE gr
FROM grade_records gr
LEFT JOIN demo_cleanup_enrollment_ids de ON de.enrollment_id = gr.enrollment_id
LEFT JOIN demo_cleanup_assignment_ids da ON da.assignment_id = gr.assignment_id
LEFT JOIN demo_cleanup_period_ids dp ON dp.grading_period_id = gr.grading_period_id
WHERE de.enrollment_id IS NOT NULL
   OR da.assignment_id IS NOT NULL
   OR dp.grading_period_id IS NOT NULL;

DELETE rc
FROM report_cards rc
LEFT JOIN demo_cleanup_enrollment_ids de ON de.enrollment_id = rc.enrollment_id
LEFT JOIN demo_cleanup_period_ids dp ON dp.grading_period_id = rc.grading_period_id
WHERE de.enrollment_id IS NOT NULL OR dp.grading_period_id IS NOT NULL;

DELETE FROM announcements
WHERE title LIKE 'DEMO:%'
   OR message LIKE 'This fictional announcement is included to demonstrate%'
   OR message LIKE 'This fictional draft demonstrates how an unpublished announcement%';

DELETE FROM school_calendar
WHERE event_title LIKE 'DEMO:%'
   OR event_description LIKE 'Fictional calendar event%';

DELETE ta
FROM teacher_attendance ta
INNER JOIN demo_cleanup_teacher_ids dt ON dt.teacher_id = ta.teacher_id
WHERE ta.remarks = 'Fictional demo attendance record.';

DELETE pr
FROM teacher_performance_reviews pr
INNER JOIN demo_cleanup_teacher_ids dt ON dt.teacher_id = pr.teacher_id
WHERE pr.notes = 'Fictional performance review for workflow testing.';

DELETE lr
FROM teacher_leave_requests lr
INNER JOIN demo_cleanup_teacher_ids dt ON dt.teacher_id = lr.teacher_id
WHERE lr.reason = 'Fictional request to demonstrate the leave review workflow.';

DELETE FROM teacher_candidates
WHERE email = 'demo.candidate@example.test';

DELETE sg
FROM student_guardians sg
LEFT JOIN demo_cleanup_student_ids ds ON ds.student_id = sg.student_id
LEFT JOIN demo_cleanup_guardian_ids dg ON dg.guardian_id = sg.guardian_id
WHERE ds.student_id IS NOT NULL OR dg.guardian_id IS NOT NULL;

DELETE g
FROM guardians g
INNER JOIN demo_cleanup_guardian_ids dg ON dg.guardian_id = g.guardian_id
WHERE NOT EXISTS (
    SELECT 1
    FROM student_guardians sg
    WHERE sg.guardian_id = g.guardian_id
);

DELETE a
FROM assessments a
INNER JOIN demo_cleanup_assessment_ids da ON da.assessment_id = a.assessment_id;

DELETE cs
FROM class_schedules cs
INNER JOIN demo_cleanup_assignment_ids da ON da.assignment_id = cs.assignment_id;

DELETE ta
FROM teaching_assignments ta
INNER JOIN demo_cleanup_assignment_ids da ON da.assignment_id = ta.assignment_id;

DELETE c
FROM curriculum c
LEFT JOIN subjects su ON su.subject_id = c.subject_id
LEFT JOIN programs p ON p.program_id = c.program_id
WHERE su.subject_code LIKE 'DEMO-%'
   OR p.program_code LIKE 'DEMO-%';

DELETE e
FROM enrollments e
INNER JOIN demo_cleanup_enrollment_ids de ON de.enrollment_id = e.enrollment_id;

DELETE sec
FROM sections sec
INNER JOIN demo_cleanup_section_ids dsec ON dsec.section_id = sec.section_id;

DELETE FROM grading_periods
WHERE period_name LIKE 'Demo %';

DELETE FROM assessment_categories
WHERE category_name LIKE 'Demo %'
  AND NOT EXISTS (
      SELECT 1
      FROM assessments
      WHERE assessments.category_id = assessment_categories.category_id
  );

DELETE fs
FROM fee_structures fs
INNER JOIN demo_cleanup_fee_structure_ids dfs ON dfs.fee_structure_id = fs.fee_structure_id;

DELETE FROM fee_types
WHERE fee_name LIKE 'Demo %'
  AND NOT EXISTS (
      SELECT 1
      FROM fee_structures
      WHERE fee_structures.fee_type_id = fee_types.fee_type_id
  );

DELETE FROM subjects
WHERE subject_code LIKE 'DEMO-%'
  AND NOT EXISTS (
      SELECT 1
      FROM teaching_assignments
      WHERE teaching_assignments.subject_id = subjects.subject_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM curriculum
      WHERE curriculum.subject_id = subjects.subject_id
  );

DELETE FROM students
WHERE student_number LIKE 'DEMO-STU-%'
  AND user_id IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM enrollments
      WHERE enrollments.student_id = students.student_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM student_applications
      WHERE student_applications.student_id = students.student_id
  );

DELETE FROM teachers
WHERE employee_number LIKE 'DEMO-EMP-%'
  AND user_id IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM teaching_assignments
      WHERE teaching_assignments.teacher_id = teachers.teacher_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM teacher_account_setup_invites
      WHERE teacher_account_setup_invites.teacher_id = teachers.teacher_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM teacher_resignations
      WHERE teacher_resignations.teacher_id = teachers.teacher_id
  );

DELETE FROM programs
WHERE program_code LIKE 'DEMO-%'
  AND NOT EXISTS (
      SELECT 1
      FROM students
      WHERE students.desired_program_id = programs.program_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM enrollments
      WHERE enrollments.program_id = programs.program_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM fee_structures
      WHERE fee_structures.program_id = programs.program_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM curriculum
      WHERE curriculum.program_id = programs.program_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM sections
      WHERE sections.program_id = programs.program_id
  );

DELETE FROM departments
WHERE department_code LIKE 'DEMO-%'
  AND NOT EXISTS (
      SELECT 1
      FROM programs
      WHERE programs.department_id = departments.department_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM teachers
      WHERE teachers.department_id = departments.department_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM teacher_candidates
      WHERE teacher_candidates.department_id = departments.department_id
  );

DELETE FROM room_monitor_layout
WHERE room_id IN (
    SELECT r.room_id
    FROM rooms r
    WHERE r.room_code IN ('A-1-LAB-1', 'A-2-ROOM-101')
      AND NOT EXISTS (
          SELECT 1
          FROM class_schedules cs
          WHERE cs.room_id = r.room_id
      )
);

DELETE FROM rooms
WHERE room_code IN ('A-1-LAB-1', 'A-2-ROOM-101')
  AND NOT EXISTS (
      SELECT 1
      FROM class_schedules
      WHERE class_schedules.room_id = rooms.room_id
  );

COMMIT;
