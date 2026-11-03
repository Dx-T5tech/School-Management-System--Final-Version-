<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/app/Core/dashboard.php';
require_once dirname(__DIR__, 2) . '/Students/Services/student.php';
require_once dirname(__DIR__) . '/Services/application_mail.php';

startAppSession();

if (isset($_SESSION['user_id'])) {
    header('Location: login.php', true, 303);
    exit;
}

$connection = getDatabaseConnection();
$availablePrograms = array_values(array_filter(
    studentAvailablePrograms($connection),
    static fn (array $program): bool => (int) $program['available_sections'] > 0
));
$documentTypes = [
    'birth_certificate' => 'PSA birth certificate',
    'moral_certificate' => 'Good moral character certificate',
    'form_138' => 'Form 138 (report card)',
    'id_photo' => '2×2 ID photo',
];
$errorMessage = '';
$noticeMessage = '';
$fieldErrors = [];
$formValues = [
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'desired_program_id' => '',
];
$step = is_string($_GET['step'] ?? null) ? $_GET['step'] : 'start';
$allowedSteps = ['start', 'verify', 'details', 'documents', 'exam'];
if (!in_array($step, $allowedSteps, true)) {
    $step = 'start';
}

$submittedToken = $_GET['token'] ?? null;
if (is_string($submittedToken) && preg_match('/^[a-f0-9]{64}$/', $submittedToken) === 1) {
    $verification = $connection->prepare(
        "SELECT application_id
         FROM student_applications
         WHERE verification_token_hash = :token_hash
           AND verification_expires_at > CURRENT_TIMESTAMP
           AND application_status = 'PendingVerification'
         LIMIT 1"
    );
    $verification->execute(['token_hash' => hash('sha256', $submittedToken)]);
    $verifiedApplicationId = $verification->fetchColumn();

    if ($verifiedApplicationId !== false) {
        $markVerified = $connection->prepare(
            "UPDATE student_applications
             SET email_verified_at = CURRENT_TIMESTAMP,
                 verification_token_hash = NULL,
                 verification_expires_at = NULL,
                 application_status = 'InProgress'
             WHERE application_id = :application_id
               AND verification_token_hash = :token_hash
               AND verification_expires_at > CURRENT_TIMESTAMP
               AND application_status = 'PendingVerification'"
        );
        $markVerified->execute([
            'application_id' => (int) $verifiedApplicationId,
            'token_hash' => hash('sha256', $submittedToken),
        ]);
        if ($markVerified->rowCount() === 1) {
            session_regenerate_id(true);
            $_SESSION['admission_application_id'] = (int) $verifiedApplicationId;
            header('Location: apply.php?step=details', true, 303);
            exit;
        }
    }

    $errorMessage = 'This email verification link is invalid or has expired. Start a new application to request a fresh link.';
    $step = 'start';
}

$applicationId = $_SESSION['admission_application_id'] ?? null;
$application = false;
if (is_int($applicationId) || (is_string($applicationId) && ctype_digit($applicationId))) {
    $applicationStatement = $connection->prepare(
        'SELECT * FROM student_applications WHERE application_id = :application_id LIMIT 1'
    );
    $applicationStatement->execute(['application_id' => (int) $applicationId]);
    $application = $applicationStatement->fetch();
    if ($application === false || $application['email_verified_at'] === null) {
        unset($_SESSION['admission_application_id']);
        $application = false;
    }
}

if ($application !== false && $application['application_status'] === 'InProgress') {
    $completedExam = $connection->prepare(
        'SELECT 1
         FROM student_application_exams
         WHERE application_id = :application_id
           AND submitted_at IS NOT NULL
         LIMIT 1'
    );
    $completedExam->execute(['application_id' => (int) $application['application_id']]);
    if ($completedExam->fetchColumn() !== false) {
        $_SESSION['admission_application_id'] = (int) $application['application_id'];
        header('Location: student-enrollment.php', true, 303);
        exit;
    }
}

$readPostString = static function (string $key): string {
    $value = $_POST[$key] ?? '';

    return is_string($value) ? trim($value) : '';
};
$sendJson = static function (int $statusCode, array $payload): never {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $readPostString('action');
    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        if ($action === 'save_exam_answer') {
            $sendJson(400, ['error' => 'Your exam session expired. Reload the page before answering again.']);
        }
        http_response_code(400);
        $errorMessage = 'Your application session expired. Reload this page and try again.';
    } elseif ($application !== false && $action === 'save_exam_answer') {
        $questionId = filter_var(
            $_POST['question_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $answer = $readPostString('answer');
        if ($questionId === false || !in_array($answer, ['A', 'B', 'C', 'D'], true)) {
            $sendJson(400, ['error' => 'Choose a valid exam answer.']);
        }

        $connection->beginTransaction();
        $activeExam = $connection->prepare(
            "SELECT e.exam_id
             FROM student_application_exams e
             INNER JOIN student_applications a ON a.application_id = e.application_id
             WHERE e.application_id = :application_id
               AND e.submitted_at IS NULL
               AND e.expires_at >= CURRENT_TIMESTAMP
               AND a.application_status = 'InProgress'
               AND a.email_verified_at IS NOT NULL
             FOR UPDATE"
        );
        $activeExam->execute(['application_id' => (int) $application['application_id']]);
        $examIdForAnswer = $activeExam->fetchColumn();
        if ($examIdForAnswer === false) {
            $connection->rollBack();
            $sendJson(409, ['error' => 'The exam is no longer active.']);
        }

        $saveAnswer = $connection->prepare(
            'UPDATE student_application_exam_questions
             SET selected_answer = :answer
             WHERE exam_id = :exam_id AND question_id = :question_id'
        );
        $saveAnswer->execute([
            'answer' => $answer,
            'exam_id' => (int) $examIdForAnswer,
            'question_id' => (int) $questionId,
        ]);
        if ($saveAnswer->rowCount() === 0) {
            $questionExists = $connection->prepare(
                'SELECT 1
                 FROM student_application_exam_questions
                 WHERE exam_id = :exam_id AND question_id = :question_id
                 LIMIT 1'
            );
            $questionExists->execute([
                'exam_id' => (int) $examIdForAnswer,
                'question_id' => (int) $questionId,
            ]);
            if ($questionExists->fetchColumn() === false) {
                $connection->rollBack();
                $sendJson(400, ['error' => 'That question is not part of this exam.']);
            }
        }
        $connection->commit();
        $sendJson(200, ['saved' => true]);
    } elseif ($action === 'start') {
        $formValues = [
            'first_name' => $readPostString('first_name'),
            'middle_name' => $readPostString('middle_name'),
            'last_name' => $readPostString('last_name'),
            'email' => strtolower($readPostString('email')),
            'phone' => $readPostString('phone'),
            'desired_program_id' => $readPostString('desired_program_id'),
        ];
        if ($formValues['first_name'] === '' || strlen($formValues['first_name']) > 100) {
            $fieldErrors['first_name'] = 'Enter a first name of 1 to 100 characters.';
        }
        if (strlen($formValues['middle_name']) > 100) {
            $fieldErrors['middle_name'] = 'The middle name must be 100 characters or fewer.';
        }
        if ($formValues['last_name'] === '' || strlen($formValues['last_name']) > 100) {
            $fieldErrors['last_name'] = 'Enter a last name of 1 to 100 characters.';
        }
        if (
            $formValues['email'] === ''
            || strlen($formValues['email']) > 150
            || filter_var($formValues['email'], FILTER_VALIDATE_EMAIL) === false
        ) {
            $fieldErrors['email'] = 'Enter a valid email address of 150 characters or fewer.';
        }
        if (strlen($formValues['phone']) > 30) {
            $fieldErrors['phone'] = 'The phone number must be 30 characters or fewer.';
        }
        $programId = filter_var(
            $formValues['desired_program_id'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $availableProgramIds = array_map(
            static fn (array $program): int => (int) $program['program_id'],
            $availablePrograms
        );
        if ($programId === false || !in_array((int) $programId, $availableProgramIds, true)) {
            $fieldErrors['desired_program_id'] = 'Choose a program with an available section.';
        }
        if ($fieldErrors === []) {
            $emailCheck = $connection->prepare(
                'SELECT
                    EXISTS(SELECT 1 FROM users WHERE email = :user_email)
                    OR EXISTS(SELECT 1 FROM students WHERE email = :student_email)'
            );
            $emailCheck->execute([
                'user_email' => $formValues['email'],
                'student_email' => $formValues['email'],
            ]);

            if ((bool) $emailCheck->fetchColumn()) {
                $fieldErrors['email'] = 'An account or student profile already uses this email. Sign in or contact the school office for help.';
            } else {
                $currentPrograms = array_values(array_filter(
                    studentAvailablePrograms($connection),
                    static fn (array $program): bool => (int) $program['available_sections'] > 0
                ));
                if (!in_array(
                    (int) $programId,
                    array_map(static fn (array $program): int => (int) $program['program_id'], $currentPrograms),
                    true
                )) {
                    $fieldErrors['desired_program_id'] = 'That program no longer has an available section. Choose another program.';
                    $availablePrograms = $currentPrograms;
                } else {
                    $rawToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $rawToken);
                    $existingApplication = $connection->prepare(
                        "SELECT application_id, application_status
                         FROM student_applications
                         WHERE email = :email
                         LIMIT 1"
                    );
                    $existingApplication->execute(['email' => $formValues['email']]);
                    $existing = $existingApplication->fetch();

                    if ($existing !== false && $existing['application_status'] === 'Submitted') {
                        $fieldErrors['email'] = 'An application already exists for this email. Sign in or contact the school office.';
                    } else {
                        $previousStoredNames = [];
                        if ($existing !== false) {
                            $oldDocuments = $connection->prepare(
                                'SELECT stored_name
                                 FROM student_application_documents
                                 WHERE application_id = :application_id'
                            );
                            $oldDocuments->execute(['application_id' => (int) $existing['application_id']]);
                            $previousStoredNames = array_map(
                                static fn (array $document): string => (string) $document['stored_name'],
                                $oldDocuments->fetchAll()
                            );
                        }
                        $connection->beginTransaction();
                        $parameters = [
                            'program_id' => (int) $programId,
                            'first_name' => $formValues['first_name'],
                            'middle_name' => $formValues['middle_name'] !== '' ? $formValues['middle_name'] : null,
                            'last_name' => $formValues['last_name'],
                            'email' => $formValues['email'],
                            'phone' => $formValues['phone'] !== '' ? $formValues['phone'] : null,
                            'token_hash' => $tokenHash,
                        ];
                        if ($existing === false) {
                            $saveApplication = $connection->prepare(
                                "INSERT INTO student_applications (
                                    desired_program_id, first_name, middle_name, last_name,
                                    email, phone, verification_token_hash,
                                    verification_expires_at, application_status
                                 ) VALUES (
                                    :program_id, :first_name, :middle_name, :last_name,
                                    :email, :phone, :token_hash,
                                    DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 24 HOUR), 'PendingVerification'
                                 )"
                            );
                            $saveApplication->execute($parameters);
                            $newApplicationId = (int) $connection->lastInsertId();
                        } else {
                            $newApplicationId = (int) $existing['application_id'];
                            $saveApplication = $connection->prepare(
                                "UPDATE student_applications
                                 SET desired_program_id = :program_id,
                                     first_name = :first_name,
                                     middle_name = :middle_name,
                                     last_name = :last_name,
                                     password_hash = NULL,
                                     phone = :phone,
                                     alternate_phone = NULL,
                                     birth_date = NULL,
                                     gender = NULL,
                                     residential_address = NULL,
                                     mailing_address = NULL,
                                     city = NULL,
                                     province = NULL,
                                     verification_token_hash = :token_hash,
                                     verification_expires_at = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 24 HOUR),
                                     email_verified_at = NULL,
                                     application_status = 'PendingVerification'
                                 WHERE application_id = :application_id"
                            );
                            $parameters['application_id'] = $newApplicationId;
                            $saveApplication->execute($parameters);
                            $connection->prepare(
                                'DELETE FROM student_application_documents WHERE application_id = :application_id'
                            )->execute(['application_id' => $newApplicationId]);
                            $connection->prepare(
                                'DELETE FROM student_application_exams WHERE application_id = :application_id'
                            )->execute(['application_id' => $newApplicationId]);
                        }
                        $connection->commit();
                        $privateDirectory = dirname(__DIR__, 5) . '/storage/uploads/admissions';
                        foreach ($previousStoredNames as $previousStoredName) {
                            $previousPath = $privateDirectory . '/' . basename($previousStoredName);
                            if (is_file($previousPath) && !unlink($previousPath)) {
                                error_log('Unable to remove a superseded private admissions document.');
                            }
                        }

                        $baseUrl = getenv('APP_BASE_URL');
                        if ($baseUrl === false || trim($baseUrl) === '') {
                            $host = $_SERVER['HTTP_HOST'] ?? '';
                            if (!is_string($host) || preg_match('/^[A-Za-z0-9.-]+(?::[0-9]{1,5})?$/', $host) !== 1) {
                                throw new RuntimeException('APP_BASE_URL is required when the request host is not valid.');
                            }
                            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                            $scriptDirectory = str_replace(
                                '\\',
                                '/',
                                dirname(is_string($_SERVER['SCRIPT_NAME'] ?? null) ? $_SERVER['SCRIPT_NAME'] : '/apply.php')
                            );
                            $scriptSegments = array_values(array_filter(
                                explode('/', trim($scriptDirectory, '/')),
                                static fn (string $segment): bool => $segment !== ''
                            ));
                            $encodedScriptPath = $scriptSegments === []
                                ? ''
                                : '/' . implode('/', array_map(
                                    static fn (string $segment): string => rawurlencode(rawurldecode($segment)),
                                    $scriptSegments
                                ));
                            $baseUrl = $scheme . '://' . $host . $encodedScriptPath;
                        } else {
                            $baseUrl = trim($baseUrl);
                            $baseUrlParts = parse_url($baseUrl);
                            if (
                                filter_var($baseUrl, FILTER_VALIDATE_URL) === false
                                || !is_array($baseUrlParts)
                                || !in_array($baseUrlParts['scheme'] ?? '', ['http', 'https'], true)
                                || !isset($baseUrlParts['host'])
                                || isset($baseUrlParts['user'])
                                || isset($baseUrlParts['pass'])
                                || isset($baseUrlParts['query'])
                                || isset($baseUrlParts['fragment'])
                            ) {
                                throw new RuntimeException('APP_BASE_URL must be an absolute HTTP or HTTPS base URL without credentials or query parameters.');
                            }
                        }
                        $verificationUrl = rtrim($baseUrl, '/') . '/apply.php?token=' . $rawToken;
                        try {
                            admissionSendVerificationEmail(
                                $formValues['email'],
                                $formValues['first_name'],
                                $verificationUrl
                            );
                            $noticeMessage = 'Check your email for a verification link. It expires in 24 hours.';
                            $step = 'verify';
                        } catch (RuntimeException $mailError) {
                            error_log('Student application verification email failed: ' . $mailError->getMessage());
                            http_response_code(503);
                            $errorMessage = 'Your application was saved, but the verification email could not be sent. Check the SMTP configuration and try again.';
                        }
                    }
                }
            }
        }
    } elseif ($application !== false && $action === 'save_details') {
        $details = [
            'birth_date' => $readPostString('birth_date'),
            'gender' => $readPostString('gender'),
            'phone' => $readPostString('phone'),
            'alternate_phone' => $readPostString('alternate_phone'),
            'residential_address' => $readPostString('residential_address'),
            'mailing_address' => $readPostString('mailing_address'),
            'city' => $readPostString('city'),
            'province' => $readPostString('province'),
        ];
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $details['birth_date']);
        if ($date === false || $date->format('Y-m-d') !== $details['birth_date'] || $date > new DateTimeImmutable('today')) {
            $fieldErrors['birth_date'] = 'Enter a valid date of birth that is not in the future.';
        }
        if (!in_array($details['gender'], ['Male', 'Female', 'Other'], true)) {
            $fieldErrors['gender'] = 'Choose an option for gender.';
        }
        if ($details['phone'] === '') {
            $fieldErrors['phone'] = 'Enter your primary phone number.';
        }
        foreach (['phone' => 30, 'alternate_phone' => 30, 'city' => 100, 'province' => 100] as $field => $limit) {
            if (strlen($details[$field]) > $limit) {
                $fieldErrors[$field] = sprintf('Enter no more than %d characters.', $limit);
            }
        }
        foreach (['residential_address', 'mailing_address'] as $field) {
            if ($details[$field] === '' || strlen($details[$field]) > 2000) {
                $fieldErrors[$field] = 'Enter an address of 1 to 2,000 characters.';
            }
        }
        if ($details['city'] === '' || $details['province'] === '') {
            $fieldErrors['city'] = $details['city'] === '' ? 'Enter your city or municipality.' : 'Enter your province.';
            if ($details['province'] === '') {
                $fieldErrors['province'] = 'Enter your province.';
            }
        }

        if ($fieldErrors === []) {
            $saveDetails = $connection->prepare(
                "UPDATE student_applications
                 SET birth_date = :birth_date,
                     gender = :gender,
                     phone = :phone,
                     alternate_phone = :alternate_phone,
                     residential_address = :residential_address,
                     mailing_address = :mailing_address,
                     city = :city,
                     province = :province
                 WHERE application_id = :application_id
                   AND email_verified_at IS NOT NULL
                   AND application_status = 'InProgress'"
            );
            $saveDetails->execute(array_merge($details, [
                'alternate_phone' => $details['alternate_phone'] !== '' ? $details['alternate_phone'] : null,
                'application_id' => (int) $application['application_id'],
            ]));
            header('Location: apply.php?step=documents', true, 303);
            exit;
        }
    } elseif ($application !== false && $action === 'upload_documents') {
        $uploadSet = $_FILES['documents'] ?? null;
        $storageDirectory = dirname(__DIR__, 5) . '/storage/uploads/admissions';
        if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0700, true) && !is_dir($storageDirectory)) {
            throw new RuntimeException('The private admissions document directory could not be created.');
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('File type detection is not available on this server.');
        }

        foreach ($documentTypes as $documentType => $label) {
            if (
                !is_array($uploadSet)
                || !is_array($uploadSet['error'] ?? null)
                || !isset($uploadSet['error'][$documentType])
            ) {
                continue;
            }
            $uploadError = $uploadSet['error'][$documentType];
            if ($uploadError === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($uploadError !== UPLOAD_ERR_OK) {
                $fieldErrors[$documentType] = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
                    ? $label . ' exceeds the 10 MB limit.'
                    : $label . ' could not be uploaded. Choose the file again.';
                continue;
            }

            $temporaryPath = $uploadSet['tmp_name'][$documentType] ?? '';
            $fileSize = (int) ($uploadSet['size'][$documentType] ?? 0);
            if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath) || $fileSize < 1 || $fileSize > 10 * 1024 * 1024) {
                $fieldErrors[$documentType] = $label . ' must be a valid file no larger than 10 MB.';
                continue;
            }
            $mimeType = finfo_file($finfo, $temporaryPath);
            $extension = null;
            if ($mimeType === 'application/pdf') {
                $signature = file_get_contents($temporaryPath, false, null, 0, 5);
                if ($signature === '%PDF-') {
                    $extension = 'pdf';
                }
            } elseif (in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                $imageInfo = getimagesize($temporaryPath);
                if (
                    is_array($imageInfo)
                    && (($mimeType === 'image/jpeg' && $imageInfo['mime'] === 'image/jpeg')
                        || ($mimeType === 'image/png' && $imageInfo['mime'] === 'image/png'))
                ) {
                    $extension = $mimeType === 'image/jpeg' ? 'jpg' : 'png';
                }
            }
            if ($extension === null) {
                $fieldErrors[$documentType] = $label . ' must be a valid PDF, JPG/JPEG, or PNG file.';
                continue;
            }

            $storedName = bin2hex(random_bytes(18));
            $destinationPath = $storageDirectory . '/' . $storedName . '.' . $extension;
            if (!move_uploaded_file($temporaryPath, $destinationPath)) {
                $fieldErrors[$documentType] = $label . ' could not be stored securely. Try again.';
                continue;
            }
            $keepStoredFile = false;
            $previous = false;
            try {
                $previousDocument = $connection->prepare(
                    'SELECT stored_name FROM student_application_documents
                     WHERE application_id = :application_id AND document_type = :document_type'
                );
                $previousDocument->execute([
                    'application_id' => (int) $application['application_id'],
                    'document_type' => $documentType,
                ]);
                $previous = $previousDocument->fetch();
                $connection->beginTransaction();
                $saveDocument = $connection->prepare(
                    'INSERT INTO student_application_documents (
                        application_id, document_type, stored_name, mime_type, file_size, sha256
                     ) VALUES (
                        :application_id, :document_type, :stored_name, :mime_type, :file_size, :sha256
                     )
                     ON DUPLICATE KEY UPDATE
                        stored_name = VALUES(stored_name),
                        mime_type = VALUES(mime_type),
                        file_size = VALUES(file_size),
                        sha256 = VALUES(sha256),
                        uploaded_at = CURRENT_TIMESTAMP'
                );
                $saveDocument->execute([
                    'application_id' => (int) $application['application_id'],
                    'document_type' => $documentType,
                    'stored_name' => $storedName . '.' . $extension,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'sha256' => hash_file('sha256', $destinationPath),
                ]);
                $connection->commit();
                $keepStoredFile = true;
            } finally {
                if (!$keepStoredFile) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                    if (is_file($destinationPath)) {
                        unlink($destinationPath);
                    }
                }
            }
            if ($previous !== false) {
                $oldPath = $storageDirectory . '/' . basename((string) $previous['stored_name']);
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }
        }
        finfo_close($finfo);

        if ($fieldErrors === []) {
            $savedDocuments = $connection->prepare(
                'SELECT COUNT(*) FROM student_application_documents WHERE application_id = :application_id'
            );
            $savedDocuments->execute(['application_id' => (int) $application['application_id']]);
            $savedDocumentCount = (int) $savedDocuments->fetchColumn();
            if ($savedDocumentCount === count($documentTypes)) {
                header('Location: apply.php?step=exam', true, 303);
                exit;
            }
            if ($savedDocumentCount > 0) {
                $noticeMessage = 'Document uploaded. Continue with the remaining required documents.';
            } else {
                $errorMessage = 'Upload all four required documents before starting the entrance exam.';
            }
        }
    } elseif ($application !== false && $action === 'start_exam') {
        $connection->beginTransaction();
        $lockApplication = $connection->prepare(
            "SELECT application_id, birth_date, gender, residential_address,
                    mailing_address, city, province
             FROM student_applications
             WHERE application_id = :application_id
               AND email_verified_at IS NOT NULL
               AND application_status = 'InProgress'
             FOR UPDATE"
        );
        $lockApplication->execute(['application_id' => (int) $application['application_id']]);
        $details = $lockApplication->fetch();
        $documentCount = $connection->prepare(
            'SELECT COUNT(*) FROM student_application_documents WHERE application_id = :application_id'
        );
        $documentCount->execute(['application_id' => (int) $application['application_id']]);

        if (
            $details === false
            || $details['birth_date'] === null
            || $details['gender'] === null
            || $details['residential_address'] === null
            || $details['mailing_address'] === null
            || $details['city'] === null
            || $details['province'] === null
            || (int) $documentCount->fetchColumn() !== count($documentTypes)
        ) {
            $connection->rollBack();
            $errorMessage = 'Complete your personal details and upload all four required documents before starting the exam.';
            $step = 'documents';
        } else {
            $existingExam = $connection->prepare(
                'SELECT exam_id FROM student_application_exams WHERE application_id = :application_id LIMIT 1'
            );
            $existingExam->execute(['application_id' => (int) $application['application_id']]);
            if ($existingExam->fetchColumn() !== false) {
                $connection->rollBack();
                $errorMessage = 'Your entrance exam has already been started and cannot be restarted.';
            } else {
                $selectedQuestions = [];
                foreach (['Math', 'English'] as $subject) {
                    $questions = $connection->prepare(
                        'SELECT question_id FROM entrance_exam_questions
                         WHERE subject = :subject AND active = 1
                         ORDER BY RAND() LIMIT 10'
                    );
                    $questions->execute(['subject' => $subject]);
                    $subjectQuestions = array_map('intval', $questions->fetchAll(PDO::FETCH_COLUMN));
                    if (count($subjectQuestions) !== 10) {
                        $connection->rollBack();
                        throw new RuntimeException('The entrance exam question bank needs at least 10 active questions per subject.');
                    }
                    $selectedQuestions = array_merge($selectedQuestions, $subjectQuestions);
                }
                shuffle($selectedQuestions);
                $saveExam = $connection->prepare(
                    'INSERT INTO student_application_exams (application_id, started_at, expires_at)
                     VALUES (:application_id, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 MINUTE))'
                );
                $saveExam->execute(['application_id' => (int) $application['application_id']]);
                $examId = (int) $connection->lastInsertId();
                $saveQuestion = $connection->prepare(
                    'INSERT INTO student_application_exam_questions (exam_id, question_id, sequence_no)
                     VALUES (:exam_id, :question_id, :sequence_no)'
                );
                foreach ($selectedQuestions as $index => $questionId) {
                    $saveQuestion->execute([
                        'exam_id' => $examId,
                        'question_id' => $questionId,
                        'sequence_no' => $index + 1,
                    ]);
                }
                $connection->commit();
                header('Location: apply.php?step=exam', true, 303);
                exit;
            }
        }
    } elseif ($application !== false && $action === 'submit_exam') {
        $connection->beginTransaction();
        $examStatement = $connection->prepare(
            'SELECT exam_id, expires_at, submitted_at,
                    (CURRENT_TIMESTAMP > DATE_ADD(expires_at, INTERVAL 10 SECOND)) AS is_late
             FROM student_application_exams
             WHERE application_id = :application_id
             FOR UPDATE'
        );
        $examStatement->execute(['application_id' => (int) $application['application_id']]);
        $exam = $examStatement->fetch();
        $completeApplication = $connection->prepare(
            "SELECT application_id
             FROM student_applications
             WHERE application_id = :application_id
               AND application_status = 'InProgress'
               AND email_verified_at IS NOT NULL
               AND birth_date IS NOT NULL
               AND gender IS NOT NULL
               AND phone IS NOT NULL
               AND residential_address IS NOT NULL
               AND mailing_address IS NOT NULL
               AND city IS NOT NULL
               AND province IS NOT NULL
             FOR UPDATE"
        );
        $completeApplication->execute(['application_id' => (int) $application['application_id']]);
        $completeApplicationExists = $completeApplication->fetchColumn() !== false;
        $completedDocumentCount = $connection->prepare(
            'SELECT COUNT(*) FROM student_application_documents WHERE application_id = :application_id'
        );
        $completedDocumentCount->execute(['application_id' => (int) $application['application_id']]);
        $allDocumentsPresent = (int) $completedDocumentCount->fetchColumn() === count($documentTypes);
        if ($exam === false) {
            $connection->rollBack();
            $errorMessage = 'Start your entrance exam before submitting it.';
        } elseif ($exam['submitted_at'] !== null) {
            $connection->rollBack();
            $errorMessage = 'This entrance exam has already been submitted.';
        } elseif (!$completeApplicationExists || !$allDocumentsPresent) {
            $connection->rollBack();
            $errorMessage = 'Your application is missing a required step. Contact the school office before submitting the exam.';
        } else {
            $answerStatement = $connection->prepare(
                'SELECT q.question_id
                 FROM student_application_exam_questions q
                 WHERE q.exam_id = :exam_id'
            );
            $answerStatement->execute(['exam_id' => (int) $exam['exam_id']]);
            $updateAnswer = $connection->prepare(
                'UPDATE student_application_exam_questions
                 SET selected_answer = :answer
                 WHERE exam_id = :exam_id AND question_id = :question_id'
            );
            $isLateSubmission = (bool) $exam['is_late'];
            $submittedAnswers = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];
            foreach ($answerStatement->fetchAll(PDO::FETCH_COLUMN) as $questionId) {
                $answer = $isLateSubmission ? null : ($submittedAnswers[(string) $questionId] ?? null);
                $answer = is_string($answer) && in_array($answer, ['A', 'B', 'C', 'D'], true) ? $answer : null;
                $updateAnswer->execute([
                    'answer' => $answer,
                    'exam_id' => (int) $exam['exam_id'],
                    'question_id' => (int) $questionId,
                ]);
            }

            $scoreStatement = $connection->prepare(
                'SELECT COUNT(*)
                 FROM student_application_exam_questions eq
                 INNER JOIN entrance_exam_questions q ON q.question_id = eq.question_id
                 WHERE eq.exam_id = :exam_id
                   AND eq.selected_answer = q.correct_answer'
            );
            $scoreStatement->execute(['exam_id' => (int) $exam['exam_id']]);
            $score = (int) $scoreStatement->fetchColumn();
            $recordScore = $connection->prepare(
                'UPDATE student_application_exams
                 SET submitted_at = CURRENT_TIMESTAMP, score = :score
                 WHERE exam_id = :exam_id AND submitted_at IS NULL'
            );
            $recordScore->execute(['score' => $score, 'exam_id' => (int) $exam['exam_id']]);

            $accountCheck = $connection->prepare(
                'SELECT
                    EXISTS(SELECT 1 FROM users WHERE email = :user_email)
                    OR EXISTS(SELECT 1 FROM students WHERE email = :student_email)'
            );
            $accountCheck->execute([
                'user_email' => $application['email'],
                'student_email' => $application['email'],
            ]);
            $activeAcademicYear = academicActiveYear($connection, true);
            $hasAvailableSection = false;
            if ($activeAcademicYear !== null) {
                $availableSection = $connection->prepare(
                    "SELECT sec.section_id
                     FROM sections sec
                     INNER JOIN programs p ON p.program_id = sec.program_id
                     WHERE sec.academic_year_id = :academic_year_id
                       AND sec.program_id = :program_id
                       AND sec.status = 'Active'
                       AND p.status = 'Active'
                       AND (
                            sec.capacity IS NULL
                            OR (
                                SELECT COUNT(*)
                                FROM enrollments e
                                WHERE e.section_id = sec.section_id
                                  AND e.enrollment_status = 'Enrolled'
                            ) < sec.capacity
                       )
                     LIMIT 1
                     FOR UPDATE"
                );
                $availableSection->execute([
                    'academic_year_id' => (int) $activeAcademicYear['academic_year_id'],
                    'program_id' => (int) $application['desired_program_id'],
                ]);
                $hasAvailableSection = $availableSection->fetchColumn() !== false;
            }

            if ($activeAcademicYear === null) {
                $connection->rollBack();
                $errorMessage = 'Student applications are currently closed because no academic year is active.';
            } elseif (!$hasAvailableSection) {
                $connection->rollBack();
                $errorMessage = 'Your selected program no longer has an available section in the active academic year. Contact the school office to continue your application.';
            } elseif ((bool) $accountCheck->fetchColumn()) {
                $connection->rollBack();
                $errorMessage = 'An account now uses this email address. Contact the school office to complete your application.';
            } else {
                $connection->commit();
                session_regenerate_id(true);
                unset($_SESSION['user_id']);
                $_SESSION['admission_application_id'] = (int) $application['application_id'];
                header('Location: student-enrollment.php', true, 303);
                exit;
            }
        }
    } else {
        http_response_code(400);
        $errorMessage = 'This application step is unavailable. Follow the enrollment steps in order.';
    }
}

if ($application !== false) {
    $applicationStatement = $connection->prepare(
        'SELECT * FROM student_applications WHERE application_id = :application_id LIMIT 1'
    );
    $applicationStatement->execute(['application_id' => (int) $application['application_id']]);
    $application = $applicationStatement->fetch();
    if ($application === false || $application['application_status'] === 'Submitted') {
        unset($_SESSION['admission_application_id']);
        $application = false;
        $step = 'start';
    } else {
        if (
            $application['birth_date'] === null
            || $application['gender'] === null
            || $application['residential_address'] === null
            || $application['mailing_address'] === null
            || $application['city'] === null
            || $application['province'] === null
        ) {
            $step = 'details';
        }

        $documentStatement = $connection->prepare(
            'SELECT document_type, uploaded_at
             FROM student_application_documents
             WHERE application_id = :application_id'
        );
        $documentStatement->execute(['application_id' => (int) $application['application_id']]);
        $savedDocuments = [];
        foreach ($documentStatement->fetchAll() as $savedDocument) {
            $savedDocuments[$savedDocument['document_type']] = $savedDocument;
        }
        if (count($savedDocuments) !== count($documentTypes) && in_array($step, ['exam'], true)) {
            $step = 'documents';
        }
        if ($step === 'start' || $step === 'verify') {
            $step = 'details';
        }

        if ($step === 'exam') {
            $examStatement = $connection->prepare(
                'SELECT exam_id, started_at, expires_at, submitted_at, score, total_questions,
                        GREATEST(TIMESTAMPDIFF(SECOND, CURRENT_TIMESTAMP, expires_at), 0) AS seconds_remaining
                 FROM student_application_exams
                 WHERE application_id = :application_id
                 LIMIT 1'
            );
            $examStatement->execute(['application_id' => (int) $application['application_id']]);
            $exam = $examStatement->fetch();
            if ($exam !== false && $exam['submitted_at'] === null && strtotime($exam['expires_at']) <= time()) {
                header('Location: apply.php?step=exam&expired=1', true, 303);
                exit;
            }
            if ($exam !== false && $exam['submitted_at'] === null) {
                $questionStatement = $connection->prepare(
                    'SELECT q.question_id, q.subject, q.prompt, q.answer_options,
                            eq.sequence_no, eq.selected_answer
                     FROM student_application_exam_questions eq
                     INNER JOIN entrance_exam_questions q ON q.question_id = eq.question_id
                     WHERE eq.exam_id = :exam_id
                     ORDER BY eq.sequence_no'
                );
                $questionStatement->execute(['exam_id' => (int) $exam['exam_id']]);
                $examQuestions = $questionStatement->fetchAll();
                foreach ($examQuestions as &$question) {
                    $question['answer_options'] = json_decode($question['answer_options'], true, 16, JSON_THROW_ON_ERROR);
                }
                unset($question);
                $timeRemaining = (int) $exam['seconds_remaining'];
            }
        }
    }
}

if (is_string($_GET['expired'] ?? null) && $_GET['expired'] === '1' && $application !== false) {
    $errorMessage = 'The 30-minute time limit ended. Your answers have been submitted automatically.';
}

if ($application === false && $_SERVER['REQUEST_METHOD'] !== 'POST' && $step !== 'verify') {
    $step = 'start';
}

$csrfToken = appCsrfToken();
$pageTitle = 'Student Application - Fullbright College';
require dirname(__DIR__) . '/Views/apply_page.php';
