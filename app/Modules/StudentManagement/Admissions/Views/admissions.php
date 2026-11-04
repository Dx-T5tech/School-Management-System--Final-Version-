<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1B3654">
    <title>Admissions - <?= dashboardEscape($schoolName) ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/admissions.css">
</head>
<body class="admissions-page">
    <a class="admissions-skip-link" href="#main-content">Skip to main content</a>
    <header class="admissions-header">
        <a class="admissions-brand" href="admissions.php" aria-label="<?= dashboardEscape($schoolName) ?> admissions home">
            <span class="admissions-brand-mark" aria-hidden="true"><?= dashboardEscape(substr($schoolName, 0, 1)) ?></span>
            <span class="admissions-brand-copy">
                <span class="admissions-brand-name"><?= dashboardEscape($schoolName) ?></span>
                <span class="admissions-brand-caption">Integrated Management System</span>
            </span>
        </a>
        <nav class="admissions-nav" aria-label="Admissions">
            <a class="admissions-nav-apply" href="enrollment-instructions.php">Enroll now</a>
            <a href="login.php">Sign in</a>
        </nav>
    </header>

    <main class="admissions-main" id="main-content" tabindex="-1">
        <div class="admissions-card">
            <section class="admissions-intro" aria-labelledby="admissions-heading">
                <h1 id="admissions-heading">Welcome to <?= dashboardEscape($schoolName) ?>.</h1>
                <p class="admissions-lead">
                    Complete your application, choose an available class section, and create your Student account at the end of enrollment.
                </p>

                <ol class="admissions-steps" aria-label="Enrollment steps">
                    <li>
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                            <path d="m5 10 3.2 3.2L15.5 6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Choose a program with open sections
                    </li>
                    <li>
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                            <path d="m5 10 3.2 3.2L15.5 6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Choose a class section and set your password
                    </li>
                    <li>
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                            <path d="m5 10 3.2 3.2L15.5 6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Your active Student account opens your dashboard
                    </li>
                </ol>

                <div class="admissions-actions">
                    <a class="admissions-button admissions-button--primary" href="enrollment-instructions.php">New student - enroll now</a>
                    <a class="admissions-button admissions-button--secondary" href="login.php">Existing school account - sign in</a>
                </div>
            </section>

            <section class="admissions-programs" aria-labelledby="programs-heading">
                <div class="admissions-programs-heading">
                    <h2 id="programs-heading">Programs</h2>
                    <p>Current choices from the school catalog</p>
                </div>

                <?php if ($programs === []): ?>
                    <p class="admissions-catalog-empty">
                        No active programs are listed yet. Please check back or contact the school office.
                    </p>
                <?php else: ?>
                    <ul class="admissions-program-groups">
                        <?php foreach ($programs as $program): ?>
                            <?php $hasOpenSections = (int) $program['available_sections'] > 0; ?>
                            <li class="admissions-program">
                                <h3><?= dashboardEscape($program['program_name']) ?></h3>
                                <?php if (is_string($program['description']) && trim($program['description']) !== ''): ?>
                                    <p><?= dashboardEscape($program['description']) ?></p>
                                <?php elseif (is_string($program['program_code']) && $program['program_code'] !== ''): ?>
                                    <p><?= dashboardEscape($program['program_code']) ?></p>
                                <?php endif; ?>
                                <span class="admissions-program-status<?= $hasOpenSections ? ' is-open' : '' ?>">
                                    <?= $hasOpenSections ? 'Programs available' : 'No sections open right now' ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="admissions-footer">
        <p>© <?= dashboardEscape(date('Y')) ?> <?= dashboardEscape($schoolName) ?> · Student Management System</p>
    </footer>
</body>
</html>
