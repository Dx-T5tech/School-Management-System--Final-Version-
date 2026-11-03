START TRANSACTION;

INSERT INTO assessment_categories (category_name, description, default_weight)
SELECT category.category_name, category.description, NULL
FROM (
    SELECT 'Written Work' AS category_name, 'Written exercises and coursework.' AS description
    UNION ALL
    SELECT 'Performance Task', 'Practical demonstrations and applied projects.'
    UNION ALL
    SELECT 'Major Exam', 'Major examinations and comprehensive assessments.'
) AS category
WHERE NOT EXISTS (
    SELECT 1
    FROM assessment_categories existing_category
    WHERE existing_category.category_name = category.category_name
);

INSERT INTO grading_periods (
    academic_year_id,
    semester_id,
    period_name,
    start_date,
    end_date,
    status
)
SELECT
    ay.academic_year_id,
    NULL,
    period.period_name,
    CASE period.period_order
        WHEN 1 THEN ay.start_date
        WHEN 2 THEN DATE_ADD(
            ay.start_date,
            INTERVAL FLOOR((DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) DAY
        )
        ELSE DATE_ADD(
            ay.start_date,
            INTERVAL FLOOR(2 * (DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) DAY
        )
    END,
    CASE period.period_order
        WHEN 1 THEN DATE_ADD(
            ay.start_date,
            INTERVAL FLOOR((DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) - 1 DAY
        )
        WHEN 2 THEN DATE_ADD(
            ay.start_date,
            INTERVAL FLOOR(2 * (DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) - 1 DAY
        )
        ELSE ay.end_date
    END,
    CASE
        WHEN CURRENT_DATE() BETWEEN
            CASE period.period_order
                WHEN 1 THEN ay.start_date
                WHEN 2 THEN DATE_ADD(
                    ay.start_date,
                    INTERVAL FLOOR((DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) DAY
                )
                ELSE DATE_ADD(
                    ay.start_date,
                    INTERVAL FLOOR(2 * (DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) DAY
                )
            END
            AND CASE period.period_order
                WHEN 1 THEN DATE_ADD(
                    ay.start_date,
                    INTERVAL FLOOR((DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) - 1 DAY
                )
                WHEN 2 THEN DATE_ADD(
                    ay.start_date,
                    INTERVAL FLOOR(2 * (DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) - 1 DAY
                )
                ELSE ay.end_date
            END
            THEN 'Active'
        WHEN CURRENT_DATE() > CASE period.period_order
            WHEN 1 THEN DATE_ADD(
                ay.start_date,
                INTERVAL FLOOR((DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) - 1 DAY
            )
            WHEN 2 THEN DATE_ADD(
                ay.start_date,
                INTERVAL FLOOR(2 * (DATEDIFF(ay.end_date, ay.start_date) + 1) / 3) - 1 DAY
            )
            ELSE ay.end_date
        END THEN 'Completed'
        ELSE 'Upcoming'
    END
FROM academic_years ay
CROSS JOIN (
    SELECT 'Prelim' AS period_name, 1 AS period_order
    UNION ALL
    SELECT 'Midterm', 2
    UNION ALL
    SELECT 'Final', 3
) AS period
WHERE ay.status IN ('Active', 'Upcoming')
  AND ay.end_date >= ay.start_date
  AND NOT EXISTS (
      SELECT 1
      FROM grading_periods existing_period
      WHERE existing_period.academic_year_id = ay.academic_year_id
        AND existing_period.semester_id IS NULL
        AND existing_period.period_name = period.period_name
  );

COMMIT;
