# Integrated School Management System

PHP/MySQL project scaffold. This creates the application structure only; module
behavior and database tables still need to be implemented.

## Database connection

`config/database.php` provides `getDatabaseConnection()`, which returns a
configured PDO connection to the `isms_database` MySQL database. It defaults
to the standard local XAMPP settings (`127.0.0.1:3306`, user `root`, empty
password). Set `DB_HOST`, `DB_PORT`, `DB_USER`, and `DB_PASS` in the PHP/Apache
environment to override them. Do not commit credentials.

Start MySQL in the XAMPP Control Panel and create the `isms_database` database
before connecting. The connection function throws a `PDOException` if the
database is unavailable or the credentials are invalid.

## User roles

The `roles` table contains four default roles: Super Admin, Admin, Teacher, and
Student. User accounts reference a role through `users.role_id`; individual
access grants are stored in `role_permissions`. Apply
`database/migrations/202611020001_sync_default_roles.sql` to synchronize the
default role list.

The Super Admin, Admin, Teacher, and Student dashboards are at
`public/super-admin-dashboard.php`, `public/admin-dashboard.php`,
`public/teacher-dashboard.php`, and `public/student-dashboard.php`,
respectively. Each page requires an authenticated PHP session and checks the
account's database role. The role pages use separate metrics and responsibilities
while sharing
`app/Shared/dashboard_layout.php`, `public/assets/css/dashboard.css`, and
`public/assets/js/dashboard.js`.
The Super Admin overview includes administrator, teacher, student, and
enrollment totals; completed-payment collections; 30-day audit activity; and
school configuration status. Collection amounts are shown without a currency
symbol because no currency setting is defined.
Its reports also summarize account status by role, monthly account growth,
enrollment status, completed payments over the last six months, and database
table/index size. Database size is an informational footprint only; it does not
indicate backup health or maintenance completion.

The Super Admin sidebar links to account administration, the user directory,
role permissions, school configuration, academic records, system monitoring,
audit logs, local database backup instructions, and school-wide reports.
Administrator creation/deactivation, role permission assignments, and school
profile changes are available to active Super Admin accounts. Academic records,
monitoring, audit history, and reports are read-only. Database backups are
created from the local command line and are not written into the public web
directory.
The Roles & Permissions page displays every role’s current permissions and lets
Super Admins edit permissions per role. Public student enrollment assigns the
Student role automatically, and Admin accounts created through Admin Management
receive the Admin role; registration does not let users choose a role. Teacher
accounts require an authorized provisioning flow.

The Admin sidebar provides Student Management, Teacher Management, Academic
Management, Attendance & Scheduling, Assessment & Grading, Finance & Payments,
Reports, and Announcements. Admin module pages use the existing school tables,
require the Admin role, protect writes with CSRF tokens, and record successful
changes in the audit log. Student and teacher records are deactivated rather
than removed; academic records with dependants cannot be permanently deleted;
payments are cancelled rather than deleted. Published announcements appear on
the dashboards of signed-in school users. Apply
`database/migrations/202611020003_create_announcements.sql` to add the
school-wide announcement store. Academic Management includes a live Curriculum
view organized by program, academic year, year level, semester, and subject.
Curriculum entries can be added, edited, and removed from the same page; subject
and year-level totals are based on the saved curriculum records across academic
years. Attendance & Scheduling includes Attendance Overview, Teacher Schedule,
Student Schedule, Room Monitoring, and Conflict Review views. Schedule and room
availability views use the active class schedules saved in the school database;
conflict review checks overlapping active schedules for shared teachers, rooms,
or class sections.
Create and edit forms across Super Admin account/configuration pages, Admin
management modules, Teacher attendance/assessment/grade entry, and Student
payment requests open in centered dialogs. Validation errors reopen the matching
dialog with entered values preserved. Filters, enrollment onboarding, and
deactivation/deletion confirmations remain in their existing flows.
Finance & Payments can associate a payment with a tuition fee and the Prelim,
Midterm, or Final term. Apply
`database/migrations/202611020007_add_tuition_term_payments.sql` after the
existing migrations. A tuition fee's net amount (after discounts) is split as
evenly as possible across the three terms, with any remaining cents assigned
first to Prelim and then Midterm. Completed term payments cannot exceed that
term's balance. The Admin dashboard shows active-year billed, collected, and
outstanding amounts and paid, unpaid, and partially paid student counts per
term. Payments without a tuition fee and term assignment remain supported for
other payment types and are listed separately from term collections.
Students can review active-year balances, completed payments, methods, and
Prelim/Midterm/Final installments under Balance Fees; submit a payment already
made for staff verification under Payment; and print or save a Financial Report
as PDF. Student payment submissions stay Pending until staff verify them; this
is not online payment processing. The Notifications page shows unread payment
status updates and due/overdue fee reminders. Fee reminders refresh when the
student opens a page and are shown for fees due within seven days or already
overdue. Apply
`database/migrations/202611020008_add_student_finance_notifications.sql` after
the tuition-term migration to allow fee-linked payments for non-tuition fees
and create in-app student notifications.

The Super Admin User Management page lists accounts and lets Super Admins
deactivate active Student accounts. Student accounts are not created manually:
public applicants verify their email, complete the application and entrance
exam, choose an available section in the active academic year, and set a
password in the final enrollment step. The system creates the active Student
account, Enrolled profile, and section enrollment together, then signs the
Student in and redirects to the dashboard. Admins can deactivate a linked
Student account through Student Management; Super Admins can deactivate it
through User Management. Apply
`database/migrations/202611020009_defer_student_account_creation.sql` after
the verified-student-applications migration to allow pending applications
without storing an account password. Admins must configure an active academic year and
available sections before enrollment can proceed. Activating an academic year
opens enrollment for its active sections; activating a different year completes
the previously active year. Student enrollment access is scoped to the current
year, while older enrollment records remain available as history. Admin-created enrollments marked Enrolled
are checked against the active year, section, program, year level, and capacity.
Apply `database/migrations/202611020006_enforce_single_active_academic_year.sql`
after the existing migrations to enforce the single-active-year rule in MySQL.
The password chosen during final enrollment must contain at least 12 characters
and fit the byte limit shown on the form. Teacher accounts continue to sign in
directly to the Teacher dashboard.
Teacher Management tracks Admin-managed applicants, creates a pending Teacher
account only after hiring, and sends a one-time email link for password setup.
The link expires after 24 hours; SMTP settings and `APP_BASE_URL` must be
configured for account invitations. Admins can add dated performance reviews,
approve or decline teacher leave requests, and schedule resignations. At the
effective date, a resignation disables linked account access and ends
assignments for academic years still in progress or yet to begin without
deleting staff or assignment history. Teachers can submit and track leave
requests from their dashboard. Apply
`database/migrations/202611020010_create_teacher_lifecycle.sql` after the
existing migrations to add the hiring, invitation, review, leave, resignation,
and assignment-status records.
The Teacher dashboard includes assigned classes, recent assessments, and
direct links to score entry and report cards; report-card records are limited
to students in the teacher’s assigned sections. The Student dashboard shows
active-year classes, published assessment results, and account notifications.
Students continue to be redirected to section enrollment until they have an
active enrollment.
Each role dashboard includes database-backed summary cards and accessible
reports. Admin reports cover enrollment growth, tuition by term, staff and
student attendance, grading completion, room availability, and published
announcements. Teacher reports show today's assigned schedule, class
attendance over the last 30 days, and average graded assessment results by
class. Student reports show attendance and published grades by current
subject, today's schedule, tuition balance, and recent payment history.
Charts use server-rendered bars with text values and do not depend on a
third-party chart service.

Prospective students can browse the live program catalog at
`public/admissions.php`. The `Enroll now` links open
`public/enrollment-instructions.php` first. The application at
`public/apply.php` requires email verification (link valid for 24 hours),
personal and contact details, four required private document uploads (PDF,
JPG/JPEG, or PNG; 10 MB maximum each), and a one-attempt, 30-minute entrance
exam with 10 randomly selected Math and 10 English questions. The score is
recorded automatically, with no passing cutoff. The student then selects an
available class section and sets a password; only then are the active account,
Enrolled profile, and enrollment record created and the student sent to the
dashboard. Apply migrations
`202611020004_add_student_program_interest.sql` and
`202611020005_create_verified_student_applications.sql` in order, then apply
`database/migrations/202611020009_defer_student_account_creation.sql`. Admin and
Super Admin accounts can review submitted applications and download documents
at `public/admin-admissions.php`; private files are stored under
`storage/uploads/admissions` and are never served through public file URLs.

Verification email delivery uses SMTP settings supplied through the PHP/Apache
environment: `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`,
`SMTP_FROM_EMAIL`, and `SMTP_ENCRYPTION` (`tls` or `ssl`). Optionally set
`SMTP_FROM_NAME` and `APP_BASE_URL` (the base URL that contains `apply.php`).
The application logs delivery failures without exposing SMTP credentials or
issuing a successful verification message. Never commit SMTP secrets.
For single-file uploads of up to 10 MB, configure PHP with
`upload_max_filesize = 10M` and `post_max_size` greater than 10 MB (for example,
`12M`) and restart Apache.

The Teacher sidebar links to assigned classes, student rosters, class schedules,
attendance, assessments, grade management, and student performance. Teacher
records are scoped to the signed-in teacher's assignments. Teachers can record
attendance, create assessments, save assessment scores, and record term grades;
these changes require a valid session CSRF token and are written to the audit
log.

The Student sidebar links to the dashboard, student profile, enrollment history,
active class schedule, attendance, published assessment scores and term grades,
published report cards, payment history and fee balances, and published school
announcements. Student records are read-only and scoped to the profile linked to
the signed-in Student account. Draft report cards, unpublished assessments, and
draft announcements are not shown.

Existing school accounts sign in at `public/login.php`; `frontend/login.php`
redirects there. The project root and `public/index.php` open the admissions
page for both the default XAMPP htdocs setup and a web server rooted at `public/`.
Create the first Super Admin from a local command line by setting
`SUPER_ADMIN_FIRST_NAME`, `SUPER_ADMIN_LAST_NAME`,
`SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_USERNAME`, and `SUPER_ADMIN_PASSWORD`, then
running `php scripts/provision_super_admin.php`. The provisioner requires all
five environment variables, hashes the password, creates one active Super Admin,
and does not change an existing Super Admin. No default credentials or public
Super Admin setup page are provided. Sign out using the POST sign-out button. The
`database/migrations/202611020002_create_login_attempts.sql` migration enables
the 15-minute failed-login limit. Passwords are stored using PHP's password
hash API (Argon2id when available), and login/logout forms use session-bound
CSRF tokens.

The local database currently has a built-in Super Admin account:
`superadmin` (`superadmin@fullbright.test`). Its password was randomly generated
during provisioning and is provided directly to the project owner; it is not
stored in the repository or a reusable setup page.

On Windows/XAMPP, run this in PowerShell from the project directory. The
password is prompted rather than written into shell history, and temporary
environment values are removed afterward:

```powershell
$env:SUPER_ADMIN_FIRST_NAME = Read-Host "First name"
$env:SUPER_ADMIN_LAST_NAME = Read-Host "Last name"
$env:SUPER_ADMIN_EMAIL = Read-Host "Email"
$env:SUPER_ADMIN_USERNAME = Read-Host "Username"
$securePassword = Read-Host "Password (12+ characters)" -AsSecureString
$passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
try {
    $env:SUPER_ADMIN_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    & C:\xampp\php\php.exe .\scripts\provision_super_admin.php
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    Remove-Item Env:\SUPER_ADMIN_FIRST_NAME, Env:\SUPER_ADMIN_LAST_NAME, Env:\SUPER_ADMIN_EMAIL, Env:\SUPER_ADMIN_USERNAME, Env:\SUPER_ADMIN_PASSWORD -ErrorAction SilentlyContinue
}
```

## Structure

```text
config/                 Application and database configuration
database/
  migrations/            Versioned database schema changes
  seeds/                 Development and reference data
public/                  Web-server document root
  assets/                CSS, JavaScript, and images
routes/                  HTTP route definitions
app/
  Core/                  Shared routing, database, authentication, and security
  Modules/
    AcademicManagement/
      Academics/          Classes, subjects, and schedules
    StudentManagement/
      Admissions/         Applications and enrollment
      Students/            Student records and guardians
      Attendance/          Student and staff attendance
    FinanceAndReporting/
      Finance/             Fees, invoices, and payments
      Reports/             Cross-module reports
    AssessmentAndGrade/
      Assessment/          Assessments, grading, and report cards
    Administration/
      Staff/               Staff and teacher records
      Library/             Catalog and circulation
      Transport/           Routes and vehicle assignments
      Inventory/           School assets and supplies
      Communication/       Notices and messaging
  Shared/                 Reusable layouts, components, and helpers
storage/
  cache/                  Generated cache (not publicly served)
  logs/                   Application logs (not publicly served)
  uploads/                Private uploaded files (not publicly served)
tests/
  Feature/                End-to-end and integration tests
  Unit/                   Isolated unit tests
docs/                     Technical and user documentation
```

Each module can grow into its own `Controllers/`, `Models/`, `Services/`, and
`Views/` directories as features are implemented. Configure the web server's
document root to `public/`; keep configuration, logs, and private uploads
outside the public web root.
