ALTER TABLE payments
    DROP CONSTRAINT chk_payments_tuition_term;

ALTER TABLE payments
    ADD CONSTRAINT chk_payments_fee_term
        CHECK (
            (student_fee_id IS NULL AND payment_term IS NULL)
            OR student_fee_id IS NOT NULL
        );

CREATE TABLE student_notifications (
    notification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_key VARCHAR(190) NOT NULL,
    notification_type ENUM('Payment status', 'Fee due') NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    PRIMARY KEY (notification_id),
    UNIQUE KEY uq_student_notifications_user_key (user_id, notification_key),
    KEY idx_student_notifications_unread (user_id, is_active, is_read, created_at),
    CONSTRAINT fk_student_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
