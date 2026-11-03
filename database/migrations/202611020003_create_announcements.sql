CREATE TABLE IF NOT EXISTS announcements (
    announcement_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_by INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Draft', 'Published') NOT NULL DEFAULT 'Draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (announcement_id),
    KEY idx_announcements_status_created (status, created_at),
    CONSTRAINT fk_announcements_created_by
        FOREIGN KEY (created_by) REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
