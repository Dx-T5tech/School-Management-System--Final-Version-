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

## Demo data cleanup

Apply
`database/migrations/202611040006_remove_demo_seed_data.sql` to remove fictional
records created by the former demo seed. It preserves all login accounts and
account-linked teacher/student profiles, the active academic year and four
requested programs, current semesters, and the configured room inventory. The
former demo seed script has been removed so it cannot repopulate the database.
Apply `database/migrations/202611040007_restore_building_a_lab_1.sql` and
`database/migrations/202611040008_restore_building_a_room_101.sql` to restore
the requested Building A first-floor Lab 1 and second-floor Room 101 floor-plan
entries.

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
school configuration status. Financial amounts are displayed in Philippine
pesos (PHP) using the ₱ symbol; this changes presentation only and does not
alter stored monetary values or calculations.
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
years. Apply
`database/migrations/202611040009_import_program_curricula.sql` to add the 211
supplied course entries for 2026-2027. Source BSCOE entries map to the existing
BSCE program; course names and unit variations are kept on their individual
curriculum entries. Class sections require a program, academic year, and whole-number year
level. Teaching assignments are limited to active teachers and sections in the
active academic year; their subject must be in that section's program curriculum
for its year level and the selected semester. Curriculum subject names and units
are shown for the selected program, year, and semester. Attendance & Scheduling includes
Attendance Overview, Teacher Schedule, Student Schedule, Room Monitoring, and
Conflict Review views. Schedule and room
availability views use the active class schedules saved in the school database;
Room Monitoring displays the four screenshot-based building inventories and
their fixed floor plans. Scheduled classroom occupancy and upcoming class times
refresh every 30 seconds; facilities remain marked separately and are not
schedulable rooms. Occupancy is derived from the timetable, not a physical
sensor reading. Apply
`database/migrations/202611040002_create_room_monitor_layout.sql` after the
rooms table exists, then apply
`database/migrations/202611040003_seed_reference_room_monitoring.sql` to seed
the Building A-D rooms, facilities, and saved positions and remove the two
demo-room records. Apply
`database/migrations/202611040010_create_screenshot_sections.sql` to create the
section groups shown in the schedule screenshots for the active academic year
(CS→BSCS, IT→BSIT, EN→BSELE, CE→BSCE). This adds sections only; the schedule
migration below separately creates the approved teaching assignments and
eligible class schedules. Apply
`database/migrations/202611040011_import_screenshot_schedules.sql` to create
teaching assignments for the active teacher and import screenshot schedule
slots only when the course exists in that section's active-year curriculum and
the room exists as an available classroom or laboratory. Online locations and
unmatched courses/rooms are skipped. The imported screenshot's time overlaps
are retained for Conflict Review. Conflict review checks overlapping active
schedules for shared teachers, rooms, or class sections. Apply
`database/migrations/202611040012_create_schedule_conflict_workflow.sql` to
enable acknowledged automatic conflicts and saved manual conflict reports.
Apply `database/migrations/202611040013_distribute_demo_teaching_assignments.sql`
after the screenshot schedule import to add 13 active demo teacher profiles
without login accounts and distribute the active assignments across them and
the existing Demo Teacher, avoiding teacher timetable overlaps.
Apply `database/migrations/202611040016_seed_demo_students_per_section.sql` to
add fictional, profile-only students to active sections, bringing each roster
to 30 enrolled students where capacity allows. The new students have no login
accounts.
Manual reports reference two active schedules and include a reason. Conflict
Review also reports advisory scheduling-policy warnings: at most 12 course
units and 6 subjects per teacher per day, with 2–4 daily class sessions per
assigned course. These policy warnings do not prevent schedule changes.
Teacher Schedule's course filter lists courses present in active schedules.
Student Schedule's year, program, and section options are derived from those
same scheduled courses; selecting a year narrows the available programs and
sections, and selecting a program further narrows sections. Submit the filters
automatically when a selection changes. The schedule filter restores keyboard
focus to the changed control after applying the selection, and retains its
Show schedule fallback when JavaScript is unavailable.
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
On Student Fees, Admins can configure fee types and fee structures by academic
year, optional program, and optional year level. Saving an active fee structure
creates Unpaid fee records for matching enrolled students; matching structures
are also applied when an enrollment becomes Enrolled. The generated fee keeps
the amount and due date from the structure at the time it is created; later
structure edits do not change existing student fee records. Generated fees
start without a scholarship or discount, which Admins can apply to the student
fee record as appropriate. The
`database/migrations/202611040014_prevent_duplicate_enrollment_fees.sql`
migration prevents the same structure from being charged twice to one
enrollment. Student payment submissions continue to create Pending payment
records for staff verification; payment submission does not mark a balance paid.
Students can review active-year balances, completed payments, methods, and
Prelim/Midterm/Final tuition amounts under Balance Fees. Tuition after discounts
is split evenly across the three terms, while other configured fees are shown
as annual charges with their itemized balances. Students can submit a payment
already made for staff verification under Payment and print or save a Financial
Report as PDF. Student payment submissions stay Pending until staff verify them;
this is not online payment processing. The Notifications page shows unread
payment status updates and due/overdue fee reminders. Fee reminders refresh
when the student opens a page and are shown for fees due within seven days or
already overdue. Apply
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
Teacher Management tracks Admin-managed applicants and creates a pending
Teacher account after hiring. The Admin receives a one-time setup link to share
with the teacher; it expires after 24 hours. Set `APP_BASE_URL` when the system
is accessed through a non-local address. Admins can add dated performance reviews,
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
`public/apply.php` requires a one-time localhost verification link (valid for
24 hours), personal and contact details, four required private document uploads (PDF,
JPG/JPEG, or PNG; 10 MB maximum each), and a one-attempt, 30-minute entrance
exam. Selecting **Generate Exam** draws 10 active Math and 10 active English
questions, creates an exam code, and displays a QR code identifying that exam.
The QR is an identifier, not a way to open the quiz on another device. Answers
are saved as the quiz is completed and the score is recorded automatically when
submitted; there is no passing cutoff. The student then selects an available
class section and sets a password; only then are the active account, Enrolled
profile, and enrollment record created and the student sent to the dashboard.
Signed-in Admin, Super Admin, and Teacher accounts are redirected
from the admissions, enrollment-instructions, and application pages to their
role dashboards; signed-in students continue to their dashboard or enrollment
step as appropriate. Apply migrations
`202611020004_add_student_program_interest.sql` and
`202611020005_create_verified_student_applications.sql` in order, then apply
`database/migrations/202611020009_defer_student_account_creation.sql` and
`database/migrations/202611040001_seed_entrance_exam_question_bank.sql` to
ensure the active Math and English question bank is populated. Admin and Super
Admin accounts can review submitted applications and download documents at
`public/admin-admissions.php`; private files are stored under
`storage/uploads/admissions` and are never served through public file URLs.

The application does not send email. Enrollment verification links
are displayed only for localhost requests and use loopback addresses. Teacher
setup links are shown to Admins to copy and share manually.
The application accepts four required document files in one upload, up to 10 MB
each. Configure PHP with `upload_max_filesize = 10M` and
`post_max_size = 45M` to allow for the combined files and request overhead, then
restart Apache.

The Teacher sidebar links to assigned classes, student rosters, class schedules,
attendance, assessments, grade management, and student performance. Teacher
records are scoped to the signed-in teacher's assignments. Teachers can record
attendance, create assessments, save assessment scores, and record term grades;
these changes require a valid session CSRF token and are written to the audit
log. Apply
`database/migrations/202611040015_seed_assessment_periods_and_categories.sql`
to seed Prelim, Midterm, and Final grading periods across active and upcoming
academic years, split evenly over each year's dates, and add the default
Written Work, Performance Task, and Major Exam categories. Category weights are
left unset for administrators to configure. Attendance is recorded for one
assigned class at a time. Selecting a class loads its enrolled students and
defaults the editable attendance date to that class's next scheduled day; the
date and active schedule time are announced together. Teachers can multi-select
students and apply Present, Absent, or Excused status together. Time in/out are
derived from the earliest start and latest end of that class's active schedule
on the selected attendance date.

Apply
`database/migrations/202611040017_add_weighted_term_grade_calculations.sql`
before using the weighted grade calculator. Exam and Quiz components are
calculated separately for the selected student, assignment, and grading period
as total points earned divided by total points possible across scored
assessments of that type. Only Published or Completed assessments count;
Attendance and Recitation remain teacher-entered percentages. The component
weights are Exam 50%, Quizzes 25%, Attendance 10%, and Recitation 15%, with the
weighted result rounded to one decimal place. Prelim uses that weighted result
directly; Midterm is the saved Prelim grade × 25% plus the weighted Midterm
result × 75%; Final is the saved Midterm grade × 25% plus the weighted Final
result × 75%. Periods are recorded sequentially, so the preceding period must
be saved first. Updating an earlier period recalculates saved later periods
using their current assessment scores and saved Attendance/Recitation
components; changed result statuses reset to Pending for teacher review, and
grades without complete components are invalidated. Updating a scored Exam or
Quiz also refreshes saved grades for the affected assignment and student and
deactivates the linked Final-grade notification if its result changes; the
teacher must post the updated Final grade to notify the student again. The
calculated Prelim, Midterm, and overall Final grades appear in the teacher grade
table and the student's My Grades page. Saving an overall Final grade also
creates or refreshes an in-app notification for a linked student account; no
email is sent.

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
