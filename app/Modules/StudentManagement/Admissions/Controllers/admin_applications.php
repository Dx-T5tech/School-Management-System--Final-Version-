<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/app/Core/dashboard.php';

$user = requireDashboardUser(['Admin', 'Super Admin']);
$connection = getDatabaseConnection();
$storageDirectory = dirname(__DIR__, 5) . '/storage/uploads/admissions';

$documentId = filter_var(
    $_GET['document'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
if ($documentId !== false) {
    $documentStatement = $connection->prepare(
        "SELECT d.stored_name, d.mime_type, d.document_type, a.application_status
         FROM student_application_documents d
         INNER JOIN student_applications a ON a.application_id = d.application_id
         WHERE d.document_id = :document_id
           AND a.application_status = 'Submitted'
         LIMIT 1"
    );
    $documentStatement->execute(['document_id' => $documentId]);
    $document = $documentStatement->fetch();
    if ($document === false) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('The requested application document was not found.');
    }

    $directory = realpath($storageDirectory);
    $filePath = realpath($storageDirectory . DIRECTORY_SEPARATOR . basename($document['stored_name']));
    if (
        $directory === false
        || $filePath === false
        || !str_starts_with($filePath, $directory . DIRECTORY_SEPARATOR)
        || !is_file($filePath)
    ) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('The requested application document is unavailable.');
    }

    $auditLog = $connection->prepare(
        'INSERT INTO audit_logs (user_id, action, module_name, description, ip_address)
         VALUES (:user_id, :action, :module_name, :description, :ip_address)'
    );
    $auditLog->execute([
        'user_id' => (int) $user['user_id'],
        'action' => 'Application document download',
        'module_name' => 'Admissions',
        'description' => sprintf('Downloaded application document %d.', (int) $documentId),
        'ip_address' => loginIpAddress(),
    ]);

    $extensions = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    $extension = $extensions[$document['mime_type']] ?? 'bin';
    $downloadName = $document['document_type'] . '.' . $extension;
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    readfile($filePath);
    exit;
}

$applications = $connection->query(
    "SELECT a.application_id, a.first_name, a.middle_name, a.last_name, a.email,
            a.phone, a.alternate_phone, a.birth_date, a.gender,
            a.residential_address, a.mailing_address, a.city, a.province,
            a.created_at, p.program_name, e.score, e.total_questions, e.submitted_at
     FROM student_applications a
     INNER JOIN programs p ON p.program_id = a.desired_program_id
     LEFT JOIN student_application_exams e ON e.application_id = a.application_id
     WHERE a.application_status = 'Submitted'
     ORDER BY a.created_at DESC"
)->fetchAll();
$dashboardHref = $user['role_name'] === 'Super Admin'
    ? 'super-admin-dashboard.php'
    : 'admin-dashboard.php';
$dashboardLabel = $user['role_name'] === 'Super Admin'
    ? 'Return to Super Admin dashboard'
    : 'Return to Admin dashboard';

$documentsByApplication = [];
if ($applications !== []) {
    $documents = $connection->query(
        "SELECT d.document_id, d.application_id, d.document_type, d.file_size, d.uploaded_at
         FROM student_application_documents d
         INNER JOIN student_applications a ON a.application_id = d.application_id
         WHERE a.application_status = 'Submitted'
         ORDER BY d.document_type"
    )->fetchAll();
    foreach ($documents as $document) {
        $documentsByApplication[(int) $document['application_id']][] = $document;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admissions Review - Fullbright College ISMS</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="dashboard-page admissions-review-page">
    <a class="skip-link" href="#main-content">Skip to applications</a>
    <main class="main-content" id="main-content" tabindex="-1">
        <header class="page-intro">
            <div class="page-intro-copy">
                <h1>Completed student enrollments</h1>
                <p class="page-description">Review applicant details, entrance exam scores, and uploaded documents. These records are submitted after enrollment is complete, so each applicant already has an active Student account.</p>
            </div>
            <a class="admin-button admin-button--secondary" href="<?= dashboardEscape($dashboardHref) ?>"><?= dashboardEscape($dashboardLabel) ?></a>
        </header>
        <section class="admin-panel admissions-review-guidance" aria-labelledby="admissions-review-guidance-heading">
            <h2 id="admissions-review-guidance-heading">Recommended review steps</h2>
            <p class="admin-panel-description">Use this page for record verification, not to make an admit or reject decision.</p>
            <ol>
                <li>Compare the applicant’s personal details with the uploaded documents.</li>
                <li>Review the recorded entrance exam score and selected program.</li>
                <li>Follow the school’s established process to resolve discrepancies before changing a Student record.</li>
            </ol>
            <p class="admin-field-hint">Application document downloads are restricted to authorized Admin and Super Admin accounts.</p>
        </section>
        <?php if ($applications === []): ?>
            <section class="admin-panel admissions-review-empty" aria-labelledby="admissions-review-empty-heading">
                <h2 id="admissions-review-empty-heading">No completed enrollments to review</h2>
                <p class="admin-empty" role="status">When a student completes enrollment, their submitted record will appear here for verification.</p>
            </section>
        <?php else: ?>
            <p class="admin-table-scroll-hint">Scroll horizontally to review all application details.</p>
            <div class="admin-table-scroll" tabindex="0" role="region" aria-label="Submitted student applications table">
                <table class="admin-table admissions-review-table">
                    <caption>Submitted student applications</caption>
                    <thead>
                        <tr>
                            <th scope="col">Applicant</th>
                            <th scope="col">Contact and address</th>
                            <th scope="col">Program</th>
                            <th scope="col">Entrance exam</th>
                            <th scope="col">Required documents</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $application): ?>
                            <?php $id = (int) $application['application_id']; ?>
                            <tr>
                                <th scope="row" class="admissions-applicant-cell">
                                    <span class="admissions-applicant-name"><?= dashboardEscape(trim(implode(' ', array_filter([
                                        $application['first_name'],
                                        $application['middle_name'],
                                        $application['last_name'],
                                    ], static fn (mixed $value): bool => is_string($value) && $value !== '')))) ?></span>
                                    <a class="admissions-applicant-email" href="mailto:<?= dashboardEscape($application['email']) ?>"><?= dashboardEscape($application['email']) ?></a>
                                    <span class="admissions-applicant-meta">Birth date: <?= dashboardEscape($application['birth_date']) ?></span>
                                    <span class="admissions-applicant-meta">Gender: <?= dashboardEscape($application['gender']) ?></span>
                                </th>
                                <td>
                                    <div class="admissions-contact-detail"><span class="admissions-contact-label">Primary:</span> <?= dashboardEscape($application['phone'] ?? '—') ?></div>
                                    <div class="admissions-contact-detail"><span class="admissions-contact-label">Alternate:</span> <?= dashboardEscape($application['alternate_phone'] ?? '—') ?></div>
                                    <div class="admissions-contact-detail"><span class="admissions-contact-label">Residential:</span> <?= dashboardEscape($application['residential_address']) ?>, <?= dashboardEscape($application['city']) ?>, <?= dashboardEscape($application['province']) ?></div>
                                    <div class="admissions-contact-detail"><span class="admissions-contact-label">Mailing:</span> <?= dashboardEscape($application['mailing_address']) ?></div>
                                </td>
                                <td class="admissions-program-cell"><?= dashboardEscape($application['program_name']) ?></td>
                                <td class="admissions-exam-cell">
                                    <?php if ($application['score'] === null): ?>
                                        <span class="admissions-score admissions-score--missing">Not recorded</span>
                                    <?php else: ?>
                                        <span class="admissions-score"><?= dashboardEscape((int) $application['score']) ?> / <?= dashboardEscape((int) $application['total_questions']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <ul class="admissions-document-list">
                                        <?php foreach ($documentsByApplication[$id] ?? [] as $document): ?>
                                            <?php
                                            $documentLabels = [
                                                'birth_certificate' => 'PSA birth certificate',
                                                'moral_certificate' => 'Good moral character certificate',
                                                'form_138' => 'Form 138',
                                                'id_photo' => '2×2 ID photo',
                                            ];
                                            $label = $documentLabels[$document['document_type']] ?? 'Application document';
                                            ?>
                                            <li>
                                                <a href="admin-admissions.php?document=<?= dashboardEscape((int) $document['document_id']) ?>">
                                                    Download <?= dashboardEscape($label) ?>
                                                </a>
                                                (<?= dashboardEscape((int) round((int) $document['file_size'] / 1024)) ?> KB)
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
