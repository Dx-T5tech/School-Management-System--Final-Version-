CREATE TABLE IF NOT EXISTS schedule_conflict_acknowledgements (
    schedule_id_one INT NOT NULL,
    schedule_id_two INT NOT NULL,
    acknowledged_by INT NULL,
    acknowledged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (schedule_id_one, schedule_id_two),
    CONSTRAINT fk_schedule_conflict_ack_one
        FOREIGN KEY (schedule_id_one) REFERENCES class_schedules (schedule_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_schedule_conflict_ack_two
        FOREIGN KEY (schedule_id_two) REFERENCES class_schedules (schedule_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_schedule_conflict_ack_user
        FOREIGN KEY (acknowledged_by) REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS manual_schedule_conflict_reports (
    report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    schedule_id_one INT NULL,
    schedule_id_two INT NULL,
    schedule_one_snapshot VARCHAR(500) NOT NULL,
    schedule_two_snapshot VARCHAR(500) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Open', 'Resolved') NOT NULL DEFAULT 'Open',
    reported_by INT NULL,
    reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (report_id),
    KEY idx_manual_schedule_conflicts_status (status, reported_at),
    CONSTRAINT fk_manual_schedule_conflict_one
        FOREIGN KEY (schedule_id_one) REFERENCES class_schedules (schedule_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_manual_schedule_conflict_two
        FOREIGN KEY (schedule_id_two) REFERENCES class_schedules (schedule_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_manual_schedule_conflict_user
        FOREIGN KEY (reported_by) REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
