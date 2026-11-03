<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Core/auth.php';
require_once dirname(__DIR__) . '/app/Core/dashboard.php';

startAppSession();
$connection = getDatabaseConnection();
$rawToken = $_GET['token'] ?? $_POST['token'] ?? '';
$tokenIsValidFormat = is_string($rawToken) && preg_match('/^[a-f0-9]{64}$/', $rawToken) === 1;
$errorMessage = '';
$success = false;
$tokenHash = $tokenIsValidFormat ? hash('sha256', $rawToken) : '';
$invite = false;

if ($tokenIsValidFormat) {
    $inviteQuery = $connection->prepare(
        "SELECT invite.invite_id, invite.user_id, invite.teacher_id, t.employment_status
         FROM teacher_account_setup_invites invite
         INNER JOIN teachers t ON t.teacher_id = invite.teacher_id
         INNER JOIN users u ON u.user_id = invite.user_id
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE invite.token_hash = :token_hash
           AND invite.expires_at > CURRENT_TIMESTAMP
           AND invite.used_at IS NULL
           AND u.account_status = 'Pending'
           AND r.role_name = 'Teacher'
           AND t.employment_status <> 'Resigned'
         LIMIT 1"
    );
    $inviteQuery->execute(['token_hash' => $tokenHash]);
    $invite = $inviteQuery->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? null;
    $passwordConfirmation = $_POST['password_confirmation'] ?? null;

    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your security token has expired. Reload the page and try again.';
    } elseif ($invite === false) {
        $errorMessage = 'This account setup link is invalid, expired, or has already been used. Ask your school administrator for a new invitation.';
    } elseif (!is_string($password) || strlen($password) < 12 || strlen($password) > appPasswordMaxBytes()) {
        $errorMessage = 'Choose a password with at least 12 characters and no more than ' . appPasswordMaxBytes() . ' bytes.';
    } elseif (!is_string($passwordConfirmation) || !hash_equals($password, $passwordConfirmation)) {
        $errorMessage = 'The password confirmation does not match.';
    } else {
        $connection->beginTransaction();
        try {
            $lockInvite = $connection->prepare(
                "SELECT invite.invite_id, invite.user_id
                 FROM teacher_account_setup_invites invite
                 INNER JOIN users u ON u.user_id = invite.user_id
                 INNER JOIN roles r ON r.role_id = u.role_id
                 INNER JOIN teachers t ON t.teacher_id = invite.teacher_id
                 WHERE invite.token_hash = :token_hash
                   AND invite.expires_at > CURRENT_TIMESTAMP
                   AND invite.used_at IS NULL
                   AND u.account_status = 'Pending'
                   AND r.role_name = 'Teacher'
                   AND t.employment_status <> 'Resigned'
                 FOR UPDATE"
            );
            $lockInvite->execute(['token_hash' => $tokenHash]);
            $lockedInvite = $lockInvite->fetch();
            if ($lockedInvite === false) {
                throw new RuntimeException('This account setup link is invalid, expired, or has already been used. Ask your school administrator for a new invitation.');
            }

            $activateAccount = $connection->prepare(
                "UPDATE users
                 SET password = :password,
                     account_status = 'Active'
                 WHERE user_id = :user_id
                   AND account_status = 'Pending'"
            );
            $activateAccount->execute([
                'password' => appPasswordHash($password),
                'user_id' => (int) $lockedInvite['user_id'],
            ]);
            if ($activateAccount->rowCount() !== 1) {
                throw new RuntimeException('The teacher account could not be activated. Ask your school administrator for help.');
            }

            $consumeInvite = $connection->prepare(
                'UPDATE teacher_account_setup_invites
                 SET used_at = CURRENT_TIMESTAMP
                 WHERE invite_id = :invite_id
                   AND token_hash = :token_hash
                   AND used_at IS NULL'
            );
            $consumeInvite->execute([
                'invite_id' => (int) $lockedInvite['invite_id'],
                'token_hash' => $tokenHash,
            ]);
            if ($consumeInvite->rowCount() !== 1) {
                throw new RuntimeException('The account setup link could not be confirmed as used.');
            }
            $connection->commit();
            $success = true;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            if ($exception instanceof RuntimeException) {
                $errorMessage = $exception->getMessage();
            } else {
                throw $exception;
            }
        }
    }
}

$csrfToken = appCsrfToken();
$escape = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172b3b">
    <title>Set up teacher account - ISMS</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="dashboard-page auth-page">
    <a class="skip-link" href="#main-content">Skip to account setup</a>
    <main class="auth-shell" id="main-content" tabindex="-1">
        <section class="auth-story" aria-labelledby="setup-welcome-heading">
            <a class="brand auth-brand" href="login.php">
                <span class="brand-mark" aria-hidden="true">F</span>
                <span class="brand-copy">
                    <span class="brand-name">Fullbright</span>
                    <span class="brand-caption">Integrated School Management</span>
                </span>
            </a>
            <div class="auth-story-copy">
                <p class="eyebrow"><span class="eyebrow-line" aria-hidden="true"></span>Teacher onboarding</p>
                <h1 id="setup-welcome-heading">Set up your school account.</h1>
                <p class="auth-story-description">Choose a password to activate your Fullbright College teacher account.</p>
            </div>
            <p class="auth-story-footer">Fullbright College · Integrated School Management System</p>
        </section>
        <section class="auth-form-panel" aria-labelledby="setup-heading">
            <div class="auth-form-wrap">
                <p class="section-overline">Secure account setup</p>
                <h2 id="setup-heading"><?= $success ? 'Account activated' : 'Create your password' ?></h2>
                <?php if ($success): ?>
                    <p class="auth-success" role="status">Your Teacher account is active. You can now sign in with your email address and new password.</p>
                    <p class="auth-help"><a href="login.php">Go to sign in</a></p>
                <?php else: ?>
                    <p class="auth-form-intro">This one-time link expires after 24 hours. Choose a password with at least 12 characters.</p>
                    <?php if ($errorMessage !== ''): ?>
                        <p class="auth-alert" id="setup-error" role="alert" tabindex="-1" data-error-summary><?= $escape($errorMessage) ?></p>
                    <?php endif; ?>
                    <?php if ($invite !== false): ?>
                        <form class="auth-form" action="teacher-account-setup.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="token" value="<?= $escape($rawToken) ?>">
                            <div class="form-field">
                                <label for="password">New password</label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="new-password"
                                    minlength="12"
                                    maxlength="<?= appPasswordMaxBytes() ?>"
                                    required
                                    <?= $errorMessage !== '' ? 'aria-describedby="setup-error"' : '' ?>
                                    <?= $errorMessage !== '' ? 'aria-invalid="true"' : '' ?>
                                >
                            </div>
                            <div class="form-field">
                                <label for="password-confirmation">Confirm new password</label>
                                <input
                                    id="password-confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    minlength="12"
                                    maxlength="<?= appPasswordMaxBytes() ?>"
                                    required
                                    <?= $errorMessage !== '' ? 'aria-describedby="setup-error"' : '' ?>
                                    <?= $errorMessage !== '' ? 'aria-invalid="true"' : '' ?>
                                >
                            </div>
                            <button class="auth-submit" type="submit">Activate account</button>
                        </form>
                    <?php elseif (!$tokenIsValidFormat): ?>
                        <p class="auth-alert" role="alert">This account setup link is invalid. Ask your school administrator for a new invitation.</p>
                    <?php else: ?>
                        <p class="auth-alert" role="alert">This account setup link is invalid, expired, or has already been used. Ask your school administrator for a new invitation.</p>
                    <?php endif; ?>
                    <p class="auth-help">Need a new invitation? Contact your school administrator.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script src="assets/js/auth.js" defer></script>
</body>
</html>
