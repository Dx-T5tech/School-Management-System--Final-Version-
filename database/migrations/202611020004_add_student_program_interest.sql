ALTER TABLE students
    ADD COLUMN desired_program_id INT NULL AFTER user_id,
    ADD INDEX idx_students_desired_program_id (desired_program_id),
    ADD CONSTRAINT fk_students_desired_program
        FOREIGN KEY (desired_program_id)
        REFERENCES programs (program_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
