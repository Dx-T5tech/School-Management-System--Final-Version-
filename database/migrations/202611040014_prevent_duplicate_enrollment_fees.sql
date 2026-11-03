ALTER TABLE student_fees
    ADD UNIQUE KEY uq_student_fees_enrollment_structure (enrollment_id, fee_structure_id);
