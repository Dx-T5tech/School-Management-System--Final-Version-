<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';

$user = requireDashboardUser(['Super Admin', 'Admin', 'Teacher', 'Student']);
$name = trim($user['first_name'] . ' ' . $user['last_name']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172b3b">
    <title>Account access - Fullbright College ISMS</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="dashboard-page auth-page">
    <a class="skip-link" href="#main-content">Skip to account information</a>
    <main class="auth-shell account-shell" id="main-content" tabindex="-1">
        <section class="auth-story" aria-labelledby="account-heading">
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
                    <?= htmlspecialchars($user['role_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> account
                </p>
                <h1 id="account-heading">You are signed in.</h1>
                <p class="auth-story-description">
                    Welcome, <?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.
                    Your account is active.
                </p>
            </div>
            <p class="auth-story-footer">Fullbright College · Integrated School Management System</p>
        </section>

        <section class="auth-form-panel" aria-labelledby="account-status-heading">
            <div class="auth-form-wrap">
                <p class="section-overline">Account access</p>
                <h2 id="account-status-heading">Dashboard unavailable</h2>
                <p class="auth-form-intro">
                    Your sign-in succeeded, but the dashboard interface is currently unavailable.
                    Contact your school administrator for assistance.
                </p>
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(appCsrfToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    <button class="auth-submit" type="submit">Sign out</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
