ALTER TABLE students
    ADD COLUMN mailing_address TEXT NULL AFTER address,
    ADD COLUMN alternate_phone VARCHAR(30) NULL AFTER phone;

CREATE TABLE student_applications (
    application_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    desired_program_id INT NOT NULL,
    student_id INT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    alternate_phone VARCHAR(30) NULL,
    birth_date DATE NULL,
    gender ENUM('Male', 'Female', 'Other') NULL,
    residential_address TEXT NULL,
    mailing_address TEXT NULL,
    city VARCHAR(100) NULL,
    province VARCHAR(100) NULL,
    verification_token_hash CHAR(64) NULL,
    verification_expires_at DATETIME NULL,
    email_verified_at DATETIME NULL,
    application_status ENUM('PendingVerification', 'InProgress', 'Submitted') NOT NULL DEFAULT 'PendingVerification',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (application_id),
    UNIQUE KEY uq_student_applications_email (email),
    UNIQUE KEY uq_student_applications_student (student_id),
    KEY idx_student_applications_status_created (application_status, created_at),
    CONSTRAINT fk_student_applications_program
        FOREIGN KEY (desired_program_id) REFERENCES programs (program_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_student_applications_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_application_documents (
    document_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    document_type ENUM('birth_certificate', 'moral_certificate', 'form_138', 'id_photo') NOT NULL,
    stored_name VARCHAR(64) NOT NULL,
    mime_type VARCHAR(40) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (document_id),
    UNIQUE KEY uq_student_application_document_type (application_id, document_type),
    CONSTRAINT fk_student_application_documents_application
        FOREIGN KEY (application_id) REFERENCES student_applications (application_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entrance_exam_questions (
    question_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    question_code VARCHAR(64) NOT NULL,
    subject ENUM('Math', 'English') NOT NULL,
    prompt VARCHAR(500) NOT NULL,
    answer_options LONGTEXT NOT NULL,
    correct_answer CHAR(1) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (question_id),
    UNIQUE KEY uq_entrance_exam_question_code (question_code),
    KEY idx_entrance_exam_subject_active (subject, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_application_exams (
    exam_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    submitted_at DATETIME NULL,
    score TINYINT UNSIGNED NULL,
    total_questions TINYINT UNSIGNED NOT NULL DEFAULT 20,
    PRIMARY KEY (exam_id),
    UNIQUE KEY uq_student_application_exam (application_id),
    CONSTRAINT fk_student_application_exams_application
        FOREIGN KEY (application_id) REFERENCES student_applications (application_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_application_exam_questions (
    exam_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    sequence_no TINYINT UNSIGNED NOT NULL,
    selected_answer CHAR(1) NULL,
    PRIMARY KEY (exam_id, question_id),
    UNIQUE KEY uq_student_exam_question_sequence (exam_id, sequence_no),
    CONSTRAINT fk_student_application_exam_questions_exam
        FOREIGN KEY (exam_id) REFERENCES student_application_exams (exam_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_student_application_exam_questions_question
        FOREIGN KEY (question_id) REFERENCES entrance_exam_questions (question_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO entrance_exam_questions (question_code, subject, prompt, answer_options, correct_answer)
VALUES
    ('math-001', 'Math', 'What is 48 + 27?', '{"A":"65","B":"75","C":"85","D":"74"}', 'B'),
    ('math-002', 'Math', 'What is 96 - 38?', '{"A":"58","B":"62","C":"68","D":"52"}', 'A'),
    ('math-003', 'Math', 'What is 7 × 8?', '{"A":"54","B":"56","C":"64","D":"48"}', 'B'),
    ('math-004', 'Math', 'What is 81 ÷ 9?', '{"A":"8","B":"7","C":"9","D":"6"}', 'C'),
    ('math-005', 'Math', 'Which fraction is equal to one half?', '{"A":"2/3","B":"3/8","C":"4/8","D":"5/8"}', 'C'),
    ('math-006', 'Math', 'What is the perimeter of a rectangle 6 cm long and 4 cm wide?', '{"A":"10 cm","B":"20 cm","C":"24 cm","D":"18 cm"}', 'B'),
    ('math-007', 'Math', 'A class has 24 learners. One third are absent. How many are present?', '{"A":"8","B":"12","C":"16","D":"18"}', 'C'),
    ('math-008', 'Math', 'What is 15% of 200?', '{"A":"15","B":"25","C":"30","D":"35"}', 'C'),
    ('math-009', 'Math', 'What is the next number in the pattern: 5, 10, 20, 40, ...?', '{"A":"45","B":"60","C":"75","D":"80"}', 'D'),
    ('math-010', 'Math', 'A notebook costs 35 pesos. How much do 4 notebooks cost?', '{"A":"120 pesos","B":"130 pesos","C":"140 pesos","D":"150 pesos"}', 'C'),
    ('math-011', 'Math', 'What is the area of a square with sides of 9 cm?', '{"A":"18 cm²","B":"36 cm²","C":"72 cm²","D":"81 cm²"}', 'D'),
    ('math-012', 'Math', 'What is 3/4 written as a decimal?', '{"A":"0.25","B":"0.50","C":"0.75","D":"1.25"}', 'C'),
    ('english-001', 'English', 'Choose the sentence with correct subject-verb agreement.', '{"A":"The students walks to class.","B":"The student walk to class.","C":"The students walk to class.","D":"The students walking to class."}', 'C'),
    ('english-002', 'English', 'Which word is closest in meaning to “careful”?', '{"A":"Cautious","B":"Noisy","C":"Quick","D":"Careless"}', 'A'),
    ('english-003', 'English', 'Choose the correctly spelled word.', '{"A":"Definately","B":"Separate","C":"Occured","D":"Enviroment"}', 'B'),
    ('english-004', 'English', 'Which punctuation mark ends a direct question?', '{"A":"Period","B":"Comma","C":"Question mark","D":"Colon"}', 'C'),
    ('english-005', 'English', 'In “Mia read the book because she enjoyed it,” what does “she” refer to?', '{"A":"The book","B":"Mia","C":"The reader","D":"The author"}', 'B'),
    ('english-006', 'English', 'Choose the best word: “We arrived ___ the library before noon.”', '{"A":"at","B":"on","C":"by","D":"from"}', 'A'),
    ('english-007', 'English', 'What is the plural form of “child”?', '{"A":"Childs","B":"Childes","C":"Children","D":"Childrens"}', 'C'),
    ('english-008', 'English', 'Choose the sentence with correct capitalization.', '{"A":"we visited Manila in June.","B":"We visited manila in june.","C":"We visited Manila in June.","D":"We Visited Manila in June."}', 'C'),
    ('english-009', 'English', 'Which word is an adjective in “The bright lamp lit the room”?', '{"A":"lamp","B":"bright","C":"lit","D":"room"}', 'B'),
    ('english-010', 'English', 'Choose the best connector: “It was raining, ___ we brought umbrellas.”', '{"A":"so","B":"but","C":"or","D":"although"}', 'A'),
    ('english-011', 'English', 'Which sentence is in the past tense?', '{"A":"They play outside.","B":"They are playing outside.","C":"They will play outside.","D":"They played outside."}', 'D'),
    ('english-012', 'English', 'What is the opposite of “generous”?', '{"A":"Kind","B":"Selfish","C":"Helpful","D":"Patient"}', 'B')
ON DUPLICATE KEY UPDATE
    subject = VALUES(subject),
    prompt = VALUES(prompt),
    answer_options = VALUES(answer_options),
    correct_answer = VALUES(correct_answer),
    active = 1;
