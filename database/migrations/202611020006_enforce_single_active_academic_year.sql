ALTER TABLE academic_years
    ADD COLUMN active_year_guard TINYINT
        GENERATED ALWAYS AS (
            CASE WHEN status = 'Active' THEN 1 ELSE NULL END
        ) STORED,
    ADD UNIQUE KEY uq_academic_year_single_active (active_year_guard);
