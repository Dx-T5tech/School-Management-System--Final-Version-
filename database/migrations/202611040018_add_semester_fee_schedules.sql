ALTER TABLE fee_structures
    ADD COLUMN semester_id INT NULL AFTER academic_year_id,
    ADD COLUMN billing_basis ENUM('Fixed', 'Per unit') NOT NULL DEFAULT 'Fixed' AFTER amount,
    ADD COLUMN unit_scope ENUM(
        'All enrolled units',
        'ROTC/CWTS units',
        'Science Lab units',
        'Major Lab units',
        'Computer Lab units'
    ) NOT NULL DEFAULT 'All enrolled units' AFTER billing_basis,
    ADD COLUMN assessment_mode ENUM('Automatic', 'Manual') NOT NULL DEFAULT 'Automatic' AFTER unit_scope,
    ADD COLUMN default_schedule_key VARCHAR(120) NULL UNIQUE AFTER assessment_mode,
    ADD KEY idx_fee_structures_semester_scope (academic_year_id, semester_id, program_id, year_level, fee_type_id),
    ADD CONSTRAINT fk_fee_structures_semester
        FOREIGN KEY (semester_id)
        REFERENCES semesters (semester_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;
