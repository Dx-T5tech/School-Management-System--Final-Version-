ALTER TABLE student_fees
    ADD UNIQUE KEY uq_student_fees_id_enrollment (student_fee_id, enrollment_id);

ALTER TABLE payments
    ADD COLUMN student_fee_id INT NULL AFTER enrollment_id,
    ADD COLUMN payment_term ENUM('Prelim', 'Midterm', 'Final') NULL AFTER student_fee_id,
    ADD KEY idx_payments_tuition_term_status (student_fee_id, payment_term, payment_status),
    ADD CONSTRAINT fk_payments_student_fee_enrollment
        FOREIGN KEY (student_fee_id, enrollment_id)
        REFERENCES student_fees (student_fee_id, enrollment_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    ADD CONSTRAINT chk_payments_tuition_term
        CHECK (
            (student_fee_id IS NULL AND payment_term IS NULL)
            OR (student_fee_id IS NOT NULL AND payment_term IS NOT NULL)
        );
