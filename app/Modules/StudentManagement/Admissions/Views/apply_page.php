<?php

declare(strict_types=1);

$stageNames = ['Start & program', 'Personal details', 'Documents', 'Entrance exam'];
$stageShortNames = ['Start', 'Profile', 'Files', 'Exam'];
$stageIndex = match ($step) {
    'details' => 1,
    'documents' => 2,
    'exam' => 3,
    default => 0,
};
$fieldTargets = [
    'first_name' => 'apply-first-name',
    'middle_name' => 'apply-middle-name',
    'last_name' => 'apply-last-name',
    'email' => 'apply-email',
    'phone' => 'apply-phone',
    'desired_program_id' => 'apply-program',
    'birth_date' => 'detail-birth-date',
    'gender' => 'detail-gender',
    'alternate_phone' => 'detail-alternate-phone',
    'residential_address' => 'detail-residential-address',
    'mailing_address' => 'detail-mailing-address',
    'city' => 'detail-city',
    'province' => 'detail-province',
    'birth_certificate' => 'document-birth_certificate',
    'moral_certificate' => 'document-moral_certificate',
    'form_138' => 'document-form_138',
    'id_photo' => 'document-id_photo',
];
$fieldTargets['phone'] = $step === 'details' ? 'detail-phone' : 'apply-phone';
$detailValue = static fn (string $field, string $fallback = ''): string => isset($_POST[$field]) && is_string($_POST[$field])
    ? $_POST[$field]
    : (string) ($application[$field] ?? $fallback);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1B3654">
    <title><?= dashboardEscape($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=20261104-blue-theme">
    <link rel="stylesheet" href="assets/css/admissions.css?v=20261104-exam-qr-blue-theme">
    <script src="assets/js/auth.js" defer></script>
    <?php if ($step === 'exam' && isset($timeRemaining)): ?>
        <script src="assets/js/vendor/qrcode-generator.js" defer></script>
        <script src="assets/js/admissions-exam.js" defer></script>
    <?php endif; ?>
</head>
<body class="admissions-page">
    <a class="admissions-skip-link" href="#main-content">Skip to application</a>
    <header class="admissions-header">
        <a class="admissions-brand" href="admissions.php" aria-label="Fullbright College admissions home">
            <span class="admissions-brand-mark" aria-hidden="true">F</span>
            <span class="admissions-brand-copy">
                <span class="admissions-brand-name">Fullbright College</span>
                <span class="admissions-brand-caption">Student Management System</span>
            </span>
        </a>
        <nav class="admissions-nav" aria-label="Admissions">
            <a class="admissions-nav-apply" href="login.php">Sign in</a>
        </nav>
    </header>

    <main class="application-main" id="main-content" tabindex="-1">
        <a class="application-back-link" href="admissions.php">Back to admissions</a>
        <div class="application-layout">
            <section class="application-card" aria-labelledby="application-heading">
                <p class="application-kicker">Student admissions · Step <?= dashboardEscape($stageIndex + 1) ?> of 4</p>
                <h1 id="application-heading">
                    <?php if ($step === 'start'): ?>Start your application.
                    <?php elseif ($step === 'verify'): ?>Continue your application.
                    <?php elseif ($step === 'details'): ?>Your personal details.
                    <?php elseif ($step === 'documents'): ?>Upload your documents.
                    <?php else: ?>Entrance examination.
                    <?php endif; ?>
                </h1>
                <ol class="application-progress" aria-label="Application progress">
                    <?php foreach ($stageNames as $index => $stageName): ?>
                        <li class="<?= $index <= $stageIndex ? 'is-current' : '' ?>" aria-label="<?= dashboardEscape($stageName) ?>"<?= $index === $stageIndex ? ' aria-current="step"' : '' ?>>
                            <span class="application-progress-number" aria-hidden="true"><?= dashboardEscape($index + 1) ?></span>
                            <span class="application-progress-full"><?= dashboardEscape($stageName) ?></span>
                            <span class="application-progress-short" aria-hidden="true"><?= dashboardEscape($stageShortNames[$index]) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <p class="application-intro">
                    Complete the application and choose a class section. Your active Student account is created after you set a password in the final enrollment step.
                </p>

                <?php if ($errorMessage !== ''): ?>
                    <p class="application-alert" id="application-error" role="alert" tabindex="-1" data-error-summary>
                        <?= dashboardEscape($errorMessage) ?>
                    </p>
                <?php endif; ?>
                <?php if ($noticeMessage !== ''): ?>
                    <p class="application-notice" role="status"><?= dashboardEscape($noticeMessage) ?></p>
                <?php endif; ?>
                <?php if ($fieldErrors !== []): ?>
                    <div class="application-alert" id="application-field-errors" role="alert" tabindex="-1" data-error-summary>
                        <p>Review the following fields:</p>
                        <ul>
                            <?php foreach ($fieldErrors as $fieldName => $message): ?>
                                <?php $targetId = $fieldTargets[$fieldName] ?? null; ?>
                                <li>
                                    <?php if ($targetId !== null): ?>
                                        <a href="#<?= dashboardEscape($targetId) ?>"><?= dashboardEscape($message) ?></a>
                                    <?php else: ?>
                                        <?= dashboardEscape($message) ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($step === 'start'): ?>
                    <?php if ($availablePrograms === []): ?>
                        <div class="application-unavailable" role="status">
                            <h2>Enrollment options aren’t open yet</h2>
                            <p>The school needs an active academic year and available class sections before new applications can continue. Please contact the school office or check back later.</p>
                            <a class="admissions-button admissions-button--secondary" href="login.php">Already have an account? Sign in</a>
                        </div>
                    <?php else: ?>
                        <form class="application-form" method="post" action="apply.php">
                            <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                            <input type="hidden" name="action" value="start">
                            <div class="application-fields">
                                <div class="application-field">
                                    <label for="apply-first-name">First name <span>(required)</span></label>
                                    <input id="apply-first-name" name="first_name" type="text" autocomplete="given-name" maxlength="100" required value="<?= dashboardEscape($formValues['first_name']) ?>" <?= isset($fieldErrors['first_name']) ? 'aria-invalid="true" aria-describedby="apply-first-name-error"' : '' ?>>
                                    <?php if (isset($fieldErrors['first_name'])): ?><span class="application-field-error" id="apply-first-name-error"><?= dashboardEscape($fieldErrors['first_name']) ?></span><?php endif; ?>
                                </div>
                                <div class="application-field">
                                    <label for="apply-middle-name">Middle name <span>(optional)</span></label>
                                    <input id="apply-middle-name" name="middle_name" type="text" autocomplete="additional-name" maxlength="100" value="<?= dashboardEscape($formValues['middle_name']) ?>" <?= isset($fieldErrors['middle_name']) ? 'aria-invalid="true" aria-describedby="apply-middle-name-error"' : '' ?>>
                                    <?php if (isset($fieldErrors['middle_name'])): ?><span class="application-field-error" id="apply-middle-name-error"><?= dashboardEscape($fieldErrors['middle_name']) ?></span><?php endif; ?>
                                </div>
                                <div class="application-field">
                                    <label for="apply-last-name">Last name <span>(required)</span></label>
                                    <input id="apply-last-name" name="last_name" type="text" autocomplete="family-name" maxlength="100" required value="<?= dashboardEscape($formValues['last_name']) ?>" <?= isset($fieldErrors['last_name']) ? 'aria-invalid="true" aria-describedby="apply-last-name-error"' : '' ?>>
                                    <?php if (isset($fieldErrors['last_name'])): ?><span class="application-field-error" id="apply-last-name-error"><?= dashboardEscape($fieldErrors['last_name']) ?></span><?php endif; ?>
                                </div>
                                <div class="application-field">
                                    <label for="apply-email">Email address <span>(required)</span></label>
                                    <input id="apply-email" name="email" type="email" autocomplete="email" maxlength="150" required value="<?= dashboardEscape($formValues['email']) ?>" <?= isset($fieldErrors['email']) ? 'aria-invalid="true" aria-describedby="apply-email-error"' : '' ?>>
                                    <?php if (isset($fieldErrors['email'])): ?><span class="application-field-error" id="apply-email-error"><?= dashboardEscape($fieldErrors['email']) ?></span><?php endif; ?>
                                </div>
                                <div class="application-field">
                                    <label for="apply-phone">Primary phone <span>(optional)</span></label>
                                    <input id="apply-phone" name="phone" type="tel" autocomplete="tel" maxlength="30" value="<?= dashboardEscape($formValues['phone']) ?>" <?= isset($fieldErrors['phone']) ? 'aria-invalid="true" aria-describedby="apply-phone-error"' : '' ?>>
                                    <?php if (isset($fieldErrors['phone'])): ?><span class="application-field-error" id="apply-phone-error"><?= dashboardEscape($fieldErrors['phone']) ?></span><?php endif; ?>
                                </div>
                                <div class="application-field application-field--wide">
                                    <label for="apply-program">Program of interest <span>(required)</span></label>
                                    <select id="apply-program" name="desired_program_id" required <?= isset($fieldErrors['desired_program_id']) ? 'aria-invalid="true" aria-describedby="apply-program-error"' : '' ?>>
                                        <option value="">Choose an available program</option>
                                        <?php foreach ($availablePrograms as $program): ?>
                                            <option value="<?= dashboardEscape((int) $program['program_id']) ?>" <?= $formValues['desired_program_id'] === (string) $program['program_id'] ? 'selected' : '' ?>>
                                                <?= dashboardEscape($program['program_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($fieldErrors['desired_program_id'])): ?>
                                        <span class="application-field-error" id="apply-program-error"><?= dashboardEscape($fieldErrors['desired_program_id']) ?></span>
                                    <?php else: ?>
                                        <span class="application-field-hint">You’ll choose a specific class section after completing your application.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="admissions-button admissions-button--primary application-submit" type="submit">Continue to application details</button>
                        </form>
                    <?php endif; ?>
                <?php elseif ($step === 'verify'): ?>
                    <section class="application-stage-panel" aria-labelledby="verify-heading">
                        <?php if (is_string($demoVerificationUrl)): ?>
                            <h2 id="verify-heading">Confirm your application</h2>
                            <p>Email delivery is disabled. Use this localhost-only verification link within 24 hours to continue.</p>
                            <a class="admissions-button admissions-button--primary" href="<?= dashboardEscape($demoVerificationUrl) ?>">Confirm and continue</a>
                        <?php else: ?>
                            <h2 id="verify-heading">Start a new application</h2>
                            <p>Local verification links are shown when you submit the application from localhost. Email is not sent.</p>
                            <a class="admissions-button admissions-button--primary" href="apply.php">Return to the application form</a>
                        <?php endif; ?>
                        <a class="admissions-button admissions-button--secondary" href="apply.php">Return to application</a>
                    </section>
                <?php elseif ($step === 'details' && $application !== false): ?>
                    <form class="application-form" method="post" action="apply.php?step=details">
                        <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                        <input type="hidden" name="action" value="save_details">
                        <div class="application-fields">
                            <div class="application-field">
                                <label for="detail-birth-date">Date of birth <span>(required)</span></label>
                                <input id="detail-birth-date" name="birth_date" type="date" autocomplete="bday" required value="<?= dashboardEscape($detailValue('birth_date')) ?>" <?= isset($fieldErrors['birth_date']) ? 'aria-invalid="true" aria-describedby="detail-birth-date-error"' : '' ?>>
                                <?php if (isset($fieldErrors['birth_date'])): ?><span class="application-field-error" id="detail-birth-date-error"><?= dashboardEscape($fieldErrors['birth_date']) ?></span><?php endif; ?>
                            </div>
                            <div class="application-field">
                                <label for="detail-gender">Gender <span>(required)</span></label>
                                <select id="detail-gender" name="gender" required <?= isset($fieldErrors['gender']) ? 'aria-invalid="true" aria-describedby="detail-gender-error"' : '' ?>>
                                    <option value="">Choose an option</option>
                                    <?php foreach (['Male', 'Female', 'Other'] as $gender): ?>
                                        <option value="<?= dashboardEscape($gender) ?>" <?= $detailValue('gender') === $gender ? 'selected' : '' ?>><?= dashboardEscape($gender) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($fieldErrors['gender'])): ?><span class="application-field-error" id="detail-gender-error"><?= dashboardEscape($fieldErrors['gender']) ?></span><?php endif; ?>
                            </div>
                            <div class="application-field">
                                <label for="detail-phone">Primary phone <span>(required)</span></label>
                                <input id="detail-phone" name="phone" type="tel" autocomplete="tel" maxlength="30" required value="<?= dashboardEscape($detailValue('phone')) ?>" <?= isset($fieldErrors['phone']) ? 'aria-invalid="true" aria-describedby="detail-phone-error"' : '' ?>>
                                <?php if (isset($fieldErrors['phone'])): ?><span class="application-field-error" id="detail-phone-error"><?= dashboardEscape($fieldErrors['phone']) ?></span><?php endif; ?>
                            </div>
                            <div class="application-field">
                                <label for="detail-alternate-phone">Alternate phone <span>(optional)</span></label>
                                <input id="detail-alternate-phone" name="alternate_phone" type="tel" autocomplete="tel" maxlength="30" value="<?= dashboardEscape($detailValue('alternate_phone')) ?>" <?= isset($fieldErrors['alternate_phone']) ? 'aria-invalid="true" aria-describedby="detail-alternate-phone-error"' : '' ?>>
                                <?php if (isset($fieldErrors['alternate_phone'])): ?><span class="application-field-error" id="detail-alternate-phone-error"><?= dashboardEscape($fieldErrors['alternate_phone']) ?></span><?php endif; ?>
                            </div>
                            <div class="application-field application-field--wide">
                                <label for="detail-residential-address">Residential address <span>(required)</span></label>
                                <textarea id="detail-residential-address" name="residential_address" autocomplete="street-address" maxlength="2000" required <?= isset($fieldErrors['residential_address']) ? 'aria-invalid="true" aria-describedby="detail-residential-address-error"' : '' ?>><?= dashboardEscape($detailValue('residential_address')) ?></textarea>
                                <?php if (isset($fieldErrors['residential_address'])): ?><span class="application-field-error" id="detail-residential-address-error"><?= dashboardEscape($fieldErrors['residential_address']) ?></span><?php endif; ?>
                            </div>
                            <div class="application-field application-field--wide">
                                <label for="detail-mailing-address">Mailing address <span>(required)</span></label>
                                <textarea id="detail-mailing-address" name="mailing_address" maxlength="2000" required <?= isset($fieldErrors['mailing_address']) ? 'aria-invalid="true" aria-describedby="detail-mailing-address-error"' : '' ?>><?= dashboardEscape($detailValue('mailing_address')) ?></textarea>
                                <?php if (isset($fieldErrors['mailing_address'])): ?><span class="application-field-error" id="detail-mailing-address-error"><?= dashboardEscape($fieldErrors['mailing_address']) ?></span><?php endif; ?>
                            </div>
                            <div class="application-field">
                                <label for="detail-city">City or municipality <span>(required)</span></label>
                                <input id="detail-city" name="city" type="text" autocomplete="address-level2" maxlength="100" required value="<?= dashboardEscape($detailValue('city')) ?>" <?= isset($fieldErrors['city']) ? 'aria-invalid="true" aria-describedby="detail-city-error"' : '' ?>>
                                <?php if (isset($fieldErrors['city'])): ?><span class="application-field-error" id="detail-city-error"><?= dashboardEscape($fieldErrors['city']) ?></span><?php endif; ?>
                            </div>
                            <div class="application-field">
                                <label for="detail-province">Province <span>(required)</span></label>
                                <input id="detail-province" name="province" type="text" autocomplete="address-level1" maxlength="100" required value="<?= dashboardEscape($detailValue('province')) ?>" <?= isset($fieldErrors['province']) ? 'aria-invalid="true" aria-describedby="detail-province-error"' : '' ?>>
                                <?php if (isset($fieldErrors['province'])): ?><span class="application-field-error" id="detail-province-error"><?= dashboardEscape($fieldErrors['province']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <button class="admissions-button admissions-button--primary application-submit" type="submit">Save and continue to documents</button>
                    </form>
                <?php elseif ($step === 'documents' && $application !== false): ?>
                    <?php $allDocumentsSaved = count($savedDocuments) === count($documentTypes); ?>
                    <p class="application-stage-copy" id="document-upload-instructions">
                        Choose a file for each required document, then upload them together with one button. Files must be PDF, JPG/JPEG, or PNG and no larger than 10 MB each. Uploaded files are stored privately and are available only to authorized school staff.
                    </p>
                    <form
                        class="application-document-form"
                        method="post"
                        action="apply.php?step=documents"
                        enctype="multipart/form-data"
                        aria-describedby="document-upload-instructions"
                    >
                        <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                        <input type="hidden" name="action" value="upload_documents">
                        <input type="hidden" name="MAX_FILE_SIZE" value="10485760">
                        <div class="application-document-fields">
                            <?php foreach ($documentTypes as $documentType => $label): ?>
                                <?php $isSaved = isset($savedDocuments[$documentType]); ?>
                                <section
                                    class="application-document-card<?= $isSaved ? ' is-uploaded' : '' ?>"
                                    aria-labelledby="document-heading-<?= dashboardEscape($documentType) ?>"
                                >
                                    <h2 id="document-heading-<?= dashboardEscape($documentType) ?>">
                                        <?= dashboardEscape($label) ?>
                                        <span><?= $isSaved ? '(uploaded · replacement optional)' : '(required)' ?></span>
                                    </h2>
                                    <label for="document-<?= dashboardEscape($documentType) ?>">
                                        <?= dashboardEscape($label) ?> file
                                    </label>
                                    <input
                                        id="document-<?= dashboardEscape($documentType) ?>"
                                        name="documents[<?= dashboardEscape($documentType) ?>]"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                        <?= $isSaved ? '' : 'required' ?>
                                        aria-describedby="<?= isset($fieldErrors[$documentType]) ? 'document-hint-' . dashboardEscape($documentType) . ' document-error-' . dashboardEscape($documentType) : 'document-hint-' . dashboardEscape($documentType) ?>"
                                        <?= isset($fieldErrors[$documentType]) ? 'aria-invalid="true"' : '' ?>
                                    >
                                    <span class="application-field-hint" id="document-hint-<?= dashboardEscape($documentType) ?>">
                                        <?= $isSaved ? 'Received. Leave empty to keep this document, or choose a replacement.' : 'PDF, JPG/JPEG, or PNG · up to 10 MB' ?>
                                    </span>
                                    <?php if (isset($fieldErrors[$documentType])): ?>
                                        <span class="application-field-error" id="document-error-<?= dashboardEscape($documentType) ?>"><?= dashboardEscape($fieldErrors[$documentType]) ?></span>
                                    <?php endif; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>
                        <button class="admissions-button admissions-button--primary application-submit" type="submit">
                            <?= $allDocumentsSaved ? 'Continue to entrance exam' : 'Upload all required documents' ?>
                        </button>
                    </form>
                <?php elseif ($step === 'exam' && $application !== false): ?>
                    <?php if (!isset($exam) || $exam === false): ?>
                        <section class="application-stage-panel" aria-labelledby="exam-intro-heading">
                            <h2 id="exam-intro-heading">Before you begin</h2>
                            <ul>
                                <li>Generating the exam selects 20 questions from the active question bank: 10 Math and 10 English.</li>
                                <li>You have 30 minutes and one attempt. The timer begins when you start.</li>
                                <li>Your score is recorded automatically. There is no passing cutoff.</li>
                                <li>When time expires, the exam is submitted with the answers available to the system.</li>
                            </ul>
                            <form method="post" action="apply.php?step=exam">
                                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                <input type="hidden" name="action" value="start_exam">
                                <button class="admissions-button admissions-button--primary application-submit" type="submit">Generate Exam</button>
                            </form>
                        </section>
                    <?php else: ?>
                        <div class="application-exam-timer" data-exam-timer data-seconds="<?= dashboardEscape($timeRemaining) ?>">
                            Time remaining: <strong data-timer-value></strong>
                        </div>
                        <section class="application-exam-identity" aria-labelledby="exam-identity-heading">
                            <div>
                                <p class="application-exam-identity-kicker">Generated exam</p>
                                <h2 id="exam-identity-heading">Your exam code</h2>
                                <p class="application-exam-code"><?= dashboardEscape($examCode) ?></p>
                                <p class="application-exam-identity-note">This QR identifies your exam only. Keep answering in this browser; it does not open the quiz on another device.</p>
                            </div>
                            <div
                                class="application-exam-qr"
                                data-exam-qr="<?= dashboardEscape($examCode) ?>"
                                role="img"
                                aria-label="QR code for exam <?= dashboardEscape($examCode) ?>"
                            ></div>
                            <p class="visually-hidden" data-qr-status role="status" aria-live="polite" aria-atomic="true"></p>
                        </section>
                        <p class="visually-hidden" data-timer-announcement aria-live="polite" aria-atomic="true"></p>
                        <p class="visually-hidden" data-answer-save-status aria-live="polite" aria-atomic="true"></p>
                        <form class="application-form" method="post" action="apply.php?step=exam" data-exam-form>
                            <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                            <input type="hidden" name="action" value="submit_exam">
                            <?php foreach ($examQuestions as $index => $question): ?>
                                <?php $options = $question['answer_options']; ?>
                                <fieldset class="application-question">
                                    <legend>
                                        <span class="application-question-meta"><?= dashboardEscape($question['subject']) ?> · Question <?= dashboardEscape($index + 1) ?> of 20</span>
                                        <?= dashboardEscape($question['prompt']) ?>
                                    </legend>
                                    <?php foreach ($options as $option => $optionText): ?>
                                        <label class="application-answer">
                                            <input type="radio" name="answers[<?= dashboardEscape((int) $question['question_id']) ?>]" value="<?= dashboardEscape($option) ?>" <?= $question['selected_answer'] === $option ? 'checked' : '' ?>>
                                            <span><?= dashboardEscape($option) ?>. <?= dashboardEscape($optionText) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                            <?php endforeach; ?>
                            <button class="admissions-button admissions-button--primary application-submit" type="submit">Submit entrance exam</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="admissions-footer">
        <p>© <?= date('Y') ?> Fullbright College · Student Management System</p>
    </footer>
</body>
</html>
