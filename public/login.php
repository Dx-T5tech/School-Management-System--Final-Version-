<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Core/auth.php';
require_once dirname(__DIR__) . '/app/Modules/StudentManagement/Students/Services/student.php';

function signedInDestination(string $roleName, PDO $connection, int $userId): ?string
{
    $dashboardPath = match ($roleName) {
        'Super Admin' => 'super-admin-dashboard.php',
        'Admin' => 'admin-dashboard.php',
        'Teacher' => 'teacher-dashboard.php',
        'Student' => studentSignInDestination($connection, $userId),
        default => null,
    };

    if ($dashboardPath !== null && !is_file(dirname(__DIR__) . '/app/Shared/dashboard_layout.php')) {
        return 'account.php';
    }

    return $dashboardPath;
}

startAppSession();
$connection = getDatabaseConnection();
$identifier = '';
$errorMessage = '';
$credentialsRejected = false;

if (isset($_SESSION['user_id'])) {
    $statement = $connection->prepare(
        'SELECT r.role_name, u.user_id
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE u.user_id = :user_id AND u.account_status = :account_status
         LIMIT 1'
    );
    $statement->execute([
        'user_id' => (int) $_SESSION['user_id'],
        'account_status' => 'Active',
    ]);
    $signedInUser = $statement->fetch();

    if ($signedInUser !== false) {
        $dashboardPath = signedInDestination(
            $signedInUser['role_name'],
            $connection,
            (int) $signedInUser['user_id']
        );

        if ($dashboardPath !== null) {
            header('Location: ' . $dashboardPath, true, 303);
            exit;
        }
    }

    $_SESSION = [];
    session_regenerate_id(true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedIdentifier = $_POST['identifier'] ?? null;
    $identifier = is_string($submittedIdentifier) ? trim($submittedIdentifier) : '';
    $submittedToken = $_POST['csrf_token'] ?? null;

    if (!appCsrfTokenIsValid($submittedToken)) {
        http_response_code(400);
        $errorMessage = 'Your sign-in session expired. Refresh the page and try again.';
    } elseif (
        $identifier === ''
        || !isset($_POST['password'])
        || !is_string($_POST['password'])
        || $_POST['password'] === ''
    ) {
        $credentialsRejected = true;
        $errorMessage = 'Enter your username or email and password.';
    } else {
        $identifierHash = loginIdentifierHash($identifier);
        $ipAddress = loginIpAddress();

        if (loginIsRateLimited($connection, $identifierHash, $ipAddress)) {
            http_response_code(429);
            $errorMessage = 'Sign-in is temporarily unavailable. Wait 15 minutes before trying again.';
        } else {
            $statement = $connection->prepare(
                'SELECT u.user_id, u.first_name, u.last_name, u.password, u.account_status, r.role_name
                 FROM users u
                 INNER JOIN roles r ON r.role_id = u.role_id
                 WHERE u.username = :username OR u.email = :email
                 LIMIT 1'
            );
            $statement->execute([
                'username' => $identifier,
                'email' => $identifier,
            ]);
            $account = $statement->fetch();
            $passwordIsValid = $account !== false
                && is_string($account['password'])
                && password_verify($_POST['password'], $account['password']);
            $accountIsActive = $account !== false && $account['account_status'] === 'Active';
            $roleIsSupported = $account !== false
                && in_array($account['role_name'], ['Super Admin', 'Admin', 'Teacher', 'Student'], true);

            if (!$passwordIsValid || !$accountIsActive || !$roleIsSupported) {
                recordFailedLogin($connection, $identifierHash, $ipAddress);
                $credentialsRejected = true;
                $errorMessage = 'We could not sign you in with those details.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $account['user_id'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $updateLastLogin = $connection->prepare(
                    'UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id'
                );
                $updateLastLogin->execute(['user_id' => (int) $account['user_id']]);

                $dashboardPath = signedInDestination(
                    $account['role_name'],
                    $connection,
                    (int) $account['user_id']
                );

                header('Location: ' . $dashboardPath, true, 303);
                exit;
            }
        }
    }
}

$csrfToken = appCsrfToken();
$escape = static fn (string $value): string => htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172b3b">
    <title>Sign in - Fullbright College ISMS</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="dashboard-page auth-page">
    <a class="skip-link" href="#main-content">Skip to sign in</a>
    <main class="auth-shell" id="main-content" tabindex="-1">
        <section class="auth-story" aria-labelledby="welcome-heading">
            <a class="brand auth-brand" href="login.php">
                <span class="brand-mark" aria-hidden="true">F</span>
                <span class="brand-copy">
                    <span class="brand-name">Fullbright</span>
                    <span class="brand-caption">Integrated School Management</span>
                </span>
            </a>
            <div class="auth-story-copy">
                <p class="eyebrow">
                    <span class="eyebrow-line" aria-hidden="true"></span>
                    One school, working together
                </p>
                <h1 id="welcome-heading">Sign in to Fullbright College ISMS.</h1>
                <p class="auth-story-description">
                    A connected workspace for the people who teach, learn, and keep
                    Fullbright College moving forward.
                </p>
            </div>
            <p class="auth-story-footer">Fullbright College · Integrated School Management System</p>
        </section>

        <section class="auth-form-panel" aria-labelledby="signin-heading">
            <div class="auth-form-wrap">
                <p class="section-overline">Secure portal</p>
                <h2 id="signin-heading">Sign in to your account</h2>
                <p class="auth-form-intro">Sign in with the account provided by your school administrator.</p>
                <p class="form-instructions">All fields are required.</p>

                <?php if ($errorMessage !== ''): ?>
                    <p class="auth-alert" id="signin-error" role="alert" tabindex="-1" data-error-summary>
                        <?= $escape($errorMessage) ?>
                    </p>
                <?php elseif (isset($_GET['signed_out'])): ?>
                    <p class="auth-success" role="status">You have been signed out.</p>
                <?php endif; ?>

                <form class="auth-form" action="login.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="form-field">
                        <label for="identifier">Username or email</label>
                        <input
                            id="identifier"
                            name="identifier"
                            type="text"
                            value="<?= $escape($identifier) ?>"
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            required
                            maxlength="150"
                            <?= $credentialsRejected ? 'aria-describedby="signin-error"' : '' ?>
                            <?= $credentialsRejected ? 'aria-invalid="true"' : '' ?>
                        >
                    </div>
                    <div class="form-field">
                        <label for="password">Password</label>
                        <div class="password-control">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                maxlength="255"
                                <?= $credentialsRejected ? 'aria-describedby="signin-error"' : '' ?>
                                <?= $credentialsRejected ? 'aria-invalid="true"' : '' ?>
                            >
                            <button
                                class="password-toggle"
                                type="button"
                                aria-controls="password"
                                aria-pressed="false"
                                data-password-toggle
                                hidden
                            >Show password</button>
                        </div>
                    </div>
                    <button class="auth-submit" type="submit">Sign in</button>
                </form>

                <p class="auth-help">
                    New to Fullbright College? <a href="enrollment-instructions.php">Review enrollment steps and apply</a>.
                    Need account help? Contact your school administrator.
                </p>
                <p class="auth-security-note">
                    Access is limited to active school accounts. Sign out when you finish using a shared device.
                </p>
            </div>
        </section>
    </main>
    <script src="assets/js/auth.js" defer></script>
</body>
</html>
