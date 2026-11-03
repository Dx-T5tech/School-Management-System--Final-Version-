# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Prospective Fullbright College students apply for admission. Enrolled students use role-based accounts to access their school records and dashboards. School administrators configure programs, academic years, sections, and student accounts.

## Product Purpose

The Integrated School Management System connects student enrollment and school operations with role-specific dashboards and records.

## Capabilities and Constraints

- Public applicants verify their email, provide personal/contact details, upload required documents, and complete a timed Math/English entrance exam. They then choose an available class section and set a password; only after this step are the active Student account, Enrolled profile, and section enrollment created together.
- The newly enrolled Student is signed in and redirected to their dashboard. Admins may deactivate linked Student accounts in Student Management; Super Admins may deactivate them in User Management.
- Super Admin User Management does not create Teacher or Student accounts manually; public enrollment is the Student account-provisioning flow.
- An active academic year opens enrollment only for its active sections. Students become Enrolled only after selecting an available section; activating a different year completes the previous year, and prior enrollment records remain history.
- Student dashboard access and current schedules are scoped to an Enrolled record in the active academic year. Admin-created Enrolled records must match the active year, active section/program, year level, and available section capacity.
- Application documents are stored outside `public/` and are downloadable only by authorized Admin and Super Admin accounts.
- Program and section availability depends on active school configuration in the database.
- Entrance-exam QR access, online receipts, and digital student ID cards are not implemented capabilities and must not be promised by public admissions pages.
- The existing application is PHP with MySQL/MariaDB and server-rendered pages.
- Verification email uses environment-configured SMTP; no mail credentials are stored in the repository.

## Brand Commitments

The school name is Fullbright College. The user-provided admissions reference identifies the Student Management System; the existing interface uses a navy, blue, cream, and gold palette. No school logo image asset is present in the project.

## Evidence on Hand

- Existing role dashboards, login, student records, admissions enrollment gate, database schema, and school name are documented in `README.md` and implemented in `app/` and `public/`.
- The user provided a public admissions-page screenshot as a layout and content reference. It shows courses and claims exam QR access, receipts, and digital IDs; current database records and application code do not substantiate those feature claims or course names.
- No official logo asset or verified public course catalog is present in the project. Public program listings must come from active database records.

## Product Principles

- Keep enrollment connected to real active academic years, programs, and class sections.
- Require a Student to complete section selection before access to the Student dashboard.
- Keep account access role-scoped and protect account creation and enrollment writes.
