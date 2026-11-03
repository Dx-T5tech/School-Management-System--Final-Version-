ALTER TABLE grade_records
    ADD COLUMN midterm_grade DECIMAL(5,2) DEFAULT NULL AFTER preliminary_grade,
    ADD COLUMN component_exam DECIMAL(5,2) DEFAULT NULL AFTER final_grade,
    ADD COLUMN component_quizzes DECIMAL(5,2) DEFAULT NULL AFTER component_exam,
    ADD COLUMN component_attendance DECIMAL(5,2) DEFAULT NULL AFTER component_quizzes,
    ADD COLUMN component_recitation DECIMAL(5,2) DEFAULT NULL AFTER component_attendance,
    ADD CONSTRAINT chk_grade_component_exam CHECK (component_exam IS NULL OR component_exam BETWEEN 0 AND 100),
    ADD CONSTRAINT chk_grade_component_quizzes CHECK (component_quizzes IS NULL OR component_quizzes BETWEEN 0 AND 100),
    ADD CONSTRAINT chk_grade_component_attendance CHECK (component_attendance IS NULL OR component_attendance BETWEEN 0 AND 100),
    ADD CONSTRAINT chk_grade_component_recitation CHECK (component_recitation IS NULL OR component_recitation BETWEEN 0 AND 100),
    ADD CONSTRAINT chk_grade_midterm_range CHECK (midterm_grade IS NULL OR midterm_grade BETWEEN 0 AND 100);

ALTER TABLE student_notifications
    MODIFY notification_type ENUM('Payment status', 'Fee due', 'Grade posted') NOT NULL;
