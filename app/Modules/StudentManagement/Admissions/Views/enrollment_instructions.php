<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172b3b">
    <title>Enrollment Instructions - Fullbright College</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/admissions.css">
</head>
<body class="admissions-page">
    <a class="admissions-skip-link" href="#main-content">Skip to enrollment instructions</a>
    <header class="admissions-header">
        <a class="admissions-brand" href="admissions.php" aria-label="Fullbright College admissions home">
            <span class="admissions-brand-mark" aria-hidden="true">F</span>
            <span class="admissions-brand-copy">
                <span class="admissions-brand-name">Fullbright College</span>
                <span class="admissions-brand-caption">Student Management System - Enrollment</span>
            </span>
        </a>
        <nav class="admissions-nav" aria-label="Enrollment">
            <a href="admissions.php">Home</a>
            <a class="admissions-nav-apply" href="login.php">Student login</a>
        </nav>
    </header>

    <main class="instructions-main" id="main-content" tabindex="-1">
        <section class="instructions-card" aria-labelledby="instructions-heading">
            <h1 id="instructions-heading">Student Enrollment Instructions</h1>
            <p class="instructions-intro">
                Complete the admissions application, choose an available class section, and set your password to finish enrollment and open your Student dashboard.
            </p>

            <section class="instructions-panel" aria-labelledby="steps-heading">
                <h2 id="steps-heading">Enrollment steps</h2>
                <p class="instructions-summary">
                    Complete the application steps in order. Your active Student account is created only after you verify your email, provide your details, upload documents, finish the entrance exam, choose a class section, and set your password.
                </p>
                <ol class="instructions-list">
                    <li>
                        <strong>Choose a program and verify your email</strong>
                        <span>Start your application with a program choice, then open the emailed verification link within 24 hours.</span>
                    </li>
                    <li>
                        <strong>Provide your personal details</strong>
                        <span>Enter your date of birth, contact numbers, residential address, and mailing address.</span>
                    </li>
                    <li>
                        <strong>Upload the four required documents</strong>
                        <span>Submit your PSA birth certificate, good moral character certificate, Form 138, and 2×2 ID photo. Each file must be PDF, JPG/JPEG, or PNG and no larger than 10 MB.</span>
                    </li>
                    <li>
                        <strong>Complete the entrance exam</strong>
                        <span>Answer 20 Math and English questions within 30 minutes. Your score is recorded automatically; there is no passing cutoff.</span>
                    </li>
                    <li>
                        <strong>Choose a class section and set your password</strong>
                        <span>After your application is complete, choose an available section in the active academic year and set a secure password. Your active account is created and you are signed in to your Student dashboard.</span>
                    </li>
                </ol>
                <p class="instructions-note">
                    Application documents are stored privately and can only be downloaded by authorized school staff. If the verification email does not arrive or no suitable section is available, please contact the school office.
                </p>
                <div class="instructions-actions">
                    <a class="admissions-button admissions-button--primary" href="apply.php">Continue to application</a>
                    <a class="instructions-login-link" href="login.php">Already have an account? Student login</a>
                </div>
            </section>
        </section>
    </main>

    <footer class="admissions-footer">
        <p>© <?= date('Y') ?> Fullbright College · Student Management System</p>
    </footer>
</body>
</html>
