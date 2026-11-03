ALTER TABLE teaching_assignments
    ADD COLUMN assignment_status ENUM('Active', 'Ended') NOT NULL DEFAULT 'Active',
    ADD COLUMN ended_at DATE NULL;

CREATE TABLE teacher_candidates (
    candidate_id INT NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    department_id INT NULL,
    specialization VARCHAR(255) NULL,
    application_date DATE NOT NULL,
    application_status ENUM('Applied', 'Interview', 'Offer', 'Hired', 'Rejected') NOT NULL DEFAULT 'Applied',
    notes TEXT NULL,
    created_by INT NOT NULL,
    hired_teacher_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (candidate_id),
    UNIQUE KEY uq_teacher_candidates_email (email),
    UNIQUE KEY uq_teacher_candidates_hired_teacher (hired_teacher_id),
    KEY idx_teacher_candidates_status (application_status, application_date),
    CONSTRAINT fk_teacher_candidates_department
        FOREIGN KEY (department_id) REFERENCES departments (department_id) ON DELETE SET NULL,
    CONSTRAINT fk_teacher_candidates_created_by
        FOREIGN KEY (created_by) REFERENCES users (user_id),
    CONSTRAINT fk_teacher_candidates_hired_teacher
        FOREIGN KEY (hired_teacher_id) REFERENCES teachers (teacher_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_account_setup_invites (
    invite_id INT NOT NULL AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (invite_id),
    UNIQUE KEY uq_teacher_setup_invites_teacher (teacher_id),
    UNIQUE KEY uq_teacher_setup_invites_user (user_id),
    UNIQUE KEY uq_teacher_setup_invites_token_hash (token_hash),
    CONSTRAINT fk_teacher_setup_invites_teacher
        FOREIGN KEY (teacher_id) REFERENCES teachers (teacher_id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_setup_invites_user
        FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_performance_reviews (
    review_id INT NOT NULL AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    reviewer_user_id INT NOT NULL,
    review_date DATE NOT NULL,
    review_period VARCHAR(100) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    strengths TEXT NULL,
    improvement_areas TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (review_id),
    KEY idx_teacher_reviews_history (teacher_id, review_date),
    CONSTRAINT fk_teacher_reviews_teacher
        FOREIGN KEY (teacher_id) REFERENCES teachers (teacher_id),
    CONSTRAINT fk_teacher_reviews_reviewer
        FOREIGN KEY (reviewer_user_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_leave_requests (
    leave_request_id INT NOT NULL AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    leave_type ENUM('Sick', 'Personal', 'Vacation', 'Other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    request_status ENUM('Pending', 'Approved', 'Declined', 'Cancelled') NOT NULL DEFAULT 'Pending',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    review_note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (leave_request_id),
    KEY idx_teacher_leave_status_dates (request_status, start_date, end_date),
    KEY idx_teacher_leave_history (teacher_id, requested_at),
    CONSTRAINT fk_teacher_leave_teacher
        FOREIGN KEY (teacher_id) REFERENCES teachers (teacher_id),
    CONSTRAINT fk_teacher_leave_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_resignations (
    resignation_id INT NOT NULL AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    requested_by INT NOT NULL,
    effective_date DATE NOT NULL,
    reason TEXT NULL,
    resignation_status ENUM('Scheduled', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (resignation_id),
    KEY idx_teacher_resignations_due (resignation_status, effective_date),
    KEY idx_teacher_resignations_history (teacher_id, effective_date),
    CONSTRAINT fk_teacher_resignations_teacher
        FOREIGN KEY (teacher_id) REFERENCES teachers (teacher_id),
    CONSTRAINT fk_teacher_resignations_requested_by
        FOREIGN KEY (requested_by) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
