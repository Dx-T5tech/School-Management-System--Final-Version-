<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172b3b">
    <title><?= dashboardEscape($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/admissions.css">
    <script src="assets/js/auth.js" defer></script>
</head>
<body class="admissions-page">
    <a class="admissions-skip-link" href="#main-content">Skip to enrollment</a>
    <header class="admissions-header">
        <a class="admissions-brand" href="admissions.php" aria-label="Fullbright College admissions home">
            <span class="admissions-brand-mark" aria-hidden="true">F</span>
            <span class="admissions-brand-copy">
                <span class="admissions-brand-name">Fullbright College</span>
                <span class="admissions-brand-caption">Complete Student Enrollment</span>
            </span>
        </a>
        <nav class="admissions-nav" aria-label="Enrollment">
            <a href="admissions.php">Admissions</a>
            <a class="admissions-nav-apply" href="login.php">Sign in</a>
        </nav>
    </header>

    <main class="application-main" id="main-content" tabindex="-1">
        <div class="application-layout">
            <section class="application-card" aria-labelledby="enrollment-heading">
                <p class="application-kicker">Final enrollment step</p>
                <h1 id="enrollment-heading">Choose your class section and set your password.</h1>
                <p class="application-intro">
                    Your email is verified. Once you choose a section and create a password, the system creates your active Student account and sends you to your dashboard.
                </p>

                <?php if ($errorMessage !== ''): ?>
                    <p class="application-alert" role="alert" tabindex="-1" data-error-summary><?= dashboardEscape($errorMessage) ?></p>
                <?php endif; ?>
                <?php if ($fieldErrors !== []): ?>
                    <div class="application-alert" role="alert" tabindex="-1" data-error-summary>
                        <p>Review the following fields:</p>
                        <ul>
                            <?php foreach ($fieldErrors as $fieldName => $message): ?>
                                <?php $targetId = match ($fieldName) {
                                    'section_id' => 'public-section-choice',
                                    'password' => 'public-enrollment-password',
                                    'password_confirmation' => 'public-enrollment-password-confirmation',
                                    default => null,
                                }; ?>
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

                <?php if ($sectionChoices === []): ?>
                    <div class="application-unavailable" role="status">
                        <h2>No class sections are available</h2>
                        <p>The school needs an active academic year and an available section for your selected program before enrollment can finish. Your verified application is saved. Please contact the school office for help.</p>
                    </div>
                <?php else: ?>
                    <form class="application-form" method="post" action="student-enrollment.php">
                        <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                        <div class="application-fields">
                            <div class="application-field application-field--wide">
                                <label for="public-section-choice">Class section <span>(required)</span></label>
                                <select
                                    id="public-section-choice"
                                    name="section_id"
                                    required
                                    <?= isset($fieldErrors['section_id']) ? 'aria-invalid="true" aria-describedby="public-section-error"' : '' ?>
                                >
                                    <option value="">Choose an available class section</option>
                                    <?php foreach ($sectionChoices as $choice): ?>
                                        <?php
                                        $seatsRemaining = $choice['capacity'] === null
                                            ? null
                                            : max(0, (int) $choice['capacity'] - (int) $choice['enrolled_count']);
                                        $seatLabel = $seatsRemaining === null
                                            ? ''
                                            : sprintf(
                                                ' · %d %s available',
                                                $seatsRemaining,
                                                $seatsRemaining === 1 ? 'seat' : 'seats'
                                            );
                                        $optionLabel = sprintf(
                                            '%s · Year %d · %s · %s%s',
                                            $choice['program_name'],
                                            (int) $choice['year_level'],
                                            $choice['section_name'],
                                            $choice['year_name'],
                                            $seatLabel
                                        );
                                        ?>
                                        <option
                                            value="<?= dashboardEscape((int) $choice['section_id']) ?>"
                                            <?= $selectedSectionId === (string) $choice['section_id'] ? 'selected' : '' ?>
                                        >
                                            <?= dashboardEscape($optionLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($fieldErrors['section_id'])): ?>
                                    <span class="application-field-error" id="public-section-error"><?= dashboardEscape($fieldErrors['section_id']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="application-field">
                                <label for="public-enrollment-password">Create password <span>(required)</span></label>
                                <div class="application-password">
                                    <input
                                        id="public-enrollment-password"
                                        name="password"
                                        type="password"
                                        autocomplete="new-password"
                                        minlength="12"
                                        maxlength="<?= dashboardEscape($passwordMaxBytes) ?>"
                                        required
                                        aria-describedby="<?= isset($fieldErrors['password']) ? 'public-enrollment-password-error' : 'public-enrollment-password-hint' ?>"
                                        <?= isset($fieldErrors['password']) ? 'aria-invalid="true"' : '' ?>
                                    >
                                    <button class="application-password-toggle" type="button" aria-controls="public-enrollment-password" aria-pressed="false" data-password-toggle hidden>Show password</button>
                                </div>
                                <?php if (isset($fieldErrors['password'])): ?>
                                    <span class="application-field-error" id="public-enrollment-password-error"><?= dashboardEscape($fieldErrors['password']) ?></span>
                                <?php else: ?>
                                    <span class="application-field-hint" id="public-enrollment-password-hint">Use at least 12 characters and no more than <?= dashboardEscape($passwordMaxBytes) ?> UTF-8 bytes.</span>
                                <?php endif; ?>
                            </div>
                            <div class="application-field">
                                <label for="public-enrollment-password-confirmation">Confirm password <span>(required)</span></label>
                                <div class="application-password">
                                    <input
                                        id="public-enrollment-password-confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        minlength="12"
                                        maxlength="<?= dashboardEscape($passwordMaxBytes) ?>"
                                        required
                                        <?= isset($fieldErrors['password_confirmation']) ? 'aria-invalid="true" aria-describedby="public-enrollment-password-confirmation-error"' : '' ?>
                                    >
                                    <button class="application-password-toggle" type="button" aria-controls="public-enrollment-password-confirmation" aria-pressed="false" data-password-toggle hidden>Show password</button>
                                </div>
                                <?php if (isset($fieldErrors['password_confirmation'])): ?>
                                    <span class="application-field-error" id="public-enrollment-password-confirmation-error"><?= dashboardEscape($fieldErrors['password_confirmation']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="admissions-button admissions-button--primary application-submit" type="submit">Complete enrollment and open dashboard</button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <footer class="admissions-footer">
        <p>© <?= dashboardEscape(date('Y')) ?> Fullbright College · Student Management System</p>
    </footer>
</body>
</html>
