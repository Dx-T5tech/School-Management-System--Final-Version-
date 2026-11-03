<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/dashboard.php';
require_once dirname(__DIR__) . '/app/Core/super_admin.php';

$user = requireDashboardUser('Super Admin');
$connection = getDatabaseConnection();
$sectionKey = $_GET['section'] ?? '';
$sectionDefinitions = [
    'admins' => ['title' => 'Admin Management', 'eyebrow' => 'Account administration', 'description' => 'Create and deactivate school Admin accounts.'],
    'users' => ['title' => 'User Management', 'eyebrow' => 'Account directory', 'description' => 'Review active accounts and deactivate Student access when needed.'],
    'roles-permissions' => ['title' => 'Roles & Permissions', 'eyebrow' => 'Access control', 'description' => 'Review each role’s permissions and edit role access.'],
    'school-configuration' => ['title' => 'School Configuration', 'eyebrow' => 'Institution profile', 'description' => 'Maintain the school information used throughout the system.'],
    'academic-management' => ['title' => 'Academic Management', 'eyebrow' => 'Academic structure', 'description' => 'Review academic years, departments, programs, and class sections.'],
    'system-monitoring' => ['title' => 'System Monitoring', 'eyebrow' => 'System status', 'description' => 'Review current database health and school-wide activity at a glance.'],
    'audit-logs' => ['title' => 'Audit Logs', 'eyebrow' => 'Governance', 'description' => 'Review recent actions recorded by the school management system.'],
    'database-backup' => ['title' => 'Database Backup', 'eyebrow' => 'Data protection', 'description' => 'Use the local XAMPP command line to create and store a database backup safely.'],
    'reports' => ['title' => 'Reports', 'eyebrow' => 'School-wide reporting', 'description' => 'Review current enrollment, staffing, and financial summaries.'],
];

if (!is_string($sectionKey) || !isset($sectionDefinitions[$sectionKey])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Super Admin section not found.');
}

$sectionDefinition = $sectionDefinitions[$sectionKey];
$errorMessage = '';
$flashMessage = $_SESSION['super_admin_flash'] ?? null;
unset($_SESSION['super_admin_flash']);
$oldInput = [];
$fieldErrors = [];
$selectedRoleId = filter_var(
    $_GET['role_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$selectedRoleId = $selectedRoleId === false ? null : $selectedRoleId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your security token has expired. Reload this page and try again.';
    } elseif ($sectionKey === 'admins') {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $firstName = superAdminPostString('first_name');
            $lastName = superAdminPostString('last_name');
            $email = superAdminPostString('email');
            $username = superAdminPostString('username');
            $password = $_POST['password'] ?? '';
            $oldInput = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'username' => $username,
            ];
            $adminRoleId = $connection->query(
                "SELECT role_id FROM roles WHERE role_name = 'Admin' LIMIT 1"
            )->fetchColumn();

            if ($firstName === '' || $lastName === '' || $username === '') {
                $errorMessage = 'Enter the Admin’s first name, last name, and username.';
                if ($firstName === '') {
                    $fieldErrors['first_name'] = $errorMessage;
                }
                if ($lastName === '') {
                    $fieldErrors['last_name'] = $errorMessage;
                }
                if ($username === '') {
                    $fieldErrors['username'] = $errorMessage;
                }
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorMessage = 'Enter a valid email address.';
                $fieldErrors['email'] = $errorMessage;
            } elseif (!is_string($password) || strlen($password) < 12 || strlen($password) > appPasswordMaxBytes()) {
                $errorMessage = 'The password must be at least 12 characters and fit the password length limit.';
                $fieldErrors['password'] = $errorMessage;
            } elseif ($adminRoleId === false) {
                $errorMessage = 'The Admin role is missing. Restore the default roles before creating an account.';
            } else {
                $duplicateAccount = $connection->prepare(
                    'SELECT COUNT(*) FROM users WHERE email = :email OR username = :username'
                );
                $duplicateAccount->execute(['email' => $email, 'username' => $username]);

                if ((int) $duplicateAccount->fetchColumn() > 0) {
                    $errorMessage = 'That email address or username is already in use.';
                    $fieldErrors['email'] = $errorMessage;
                    $fieldErrors['username'] = $errorMessage;
                } else {
                    try {
                        $connection->beginTransaction();
                        $createAccount = $connection->prepare(
                            'INSERT INTO users
                                (role_id, first_name, last_name, email, username, password, account_status)
                             VALUES
                                (:role_id, :first_name, :last_name, :email, :username, :password, :account_status)'
                        );
                        $createAccount->execute([
                            'role_id' => (int) $adminRoleId,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => $email,
                            'username' => $username,
                            'password' => appPasswordHash($password),
                            'account_status' => 'Active',
                        ]);
                        superAdminRecordAudit(
                            $connection,
                            (int) $user['user_id'],
                            'Create',
                            'Admin Management',
                            'Created Admin account for ' . $username . '.'
                        );
                        $connection->commit();
                        $_SESSION['super_admin_flash'] = 'Admin account created and ready to use.';
                        header('Location: super-admin-section.php?section=admins', true, 303);
                        exit;
                    } catch (PDOException $exception) {
                        if ($connection->inTransaction()) {
                            $connection->rollBack();
                        }

                        if ($exception->getCode() !== '23000') {
                            throw $exception;
                        }

                        $errorMessage = 'That email address or username is already in use.';
                    } catch (Throwable $exception) {
                        if ($connection->inTransaction()) {
                            $connection->rollBack();
                        }
                        throw $exception;
                    }
                }
            }
        } elseif ($action === 'deactivate') {
            $targetUserId = filter_var(
                $_POST['user_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            $adminRoleId = $connection->query(
                "SELECT role_id FROM roles WHERE role_name = 'Admin' LIMIT 1"
            )->fetchColumn();

            if ($targetUserId === false || $adminRoleId === false) {
                $errorMessage = 'The selected Admin account could not be found.';
            } elseif ((int) $targetUserId === (int) $user['user_id']) {
                $errorMessage = 'You cannot deactivate the account you are currently using.';
            } else {
                $deactivateAccount = $connection->prepare(
                    'UPDATE users
                     SET account_status = :account_status
                     WHERE user_id = :user_id
                       AND role_id = :role_id
                       AND account_status = :active_status'
                );
                $deactivateAccount->execute([
                    'account_status' => 'Inactive',
                    'user_id' => (int) $targetUserId,
                    'role_id' => (int) $adminRoleId,
                    'active_status' => 'Active',
                ]);

                if ($deactivateAccount->rowCount() !== 1) {
                    $errorMessage = 'The selected account is not an active Admin account.';
                } else {
                    superAdminRecordAudit(
                        $connection,
                        (int) $user['user_id'],
                        'Deactivate',
                        'Admin Management',
                        'Deactivated Admin account ID ' . (int) $targetUserId . '.'
                    );
                    $_SESSION['super_admin_flash'] = 'Admin account deactivated.';
                    header('Location: super-admin-section.php?section=admins', true, 303);
                    exit;
                }
            }
        } else {
            $errorMessage = 'Choose a supported Admin account action.';
        }
    } elseif ($sectionKey === 'users') {
        $action = superAdminPostString('action');
        if ($action !== 'deactivate-student-account') {
            $errorMessage = 'Choose a supported Student account action.';
        } else {
            $targetUserId = filter_var(
                $_POST['user_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            if ($targetUserId === false || (int) $targetUserId === (int) $user['user_id']) {
                $errorMessage = 'Select a different Student account to deactivate.';
            } else {
                try {
                    $connection->beginTransaction();
                    $studentAccount = $connection->prepare(
                        "SELECT u.account_status, s.student_id
                         FROM users u
                         INNER JOIN roles r ON r.role_id = u.role_id
                         INNER JOIN students s ON s.user_id = u.user_id
                         WHERE u.user_id = :user_id
                           AND r.role_name = 'Student'
                         FOR UPDATE"
                    );
                    $studentAccount->execute(['user_id' => (int) $targetUserId]);
                    $studentRecord = $studentAccount->fetch();

                    if ($studentRecord === false || $studentRecord['account_status'] !== 'Active') {
                        $connection->rollBack();
                        $errorMessage = 'The selected account is not an active Student account.';
                    } else {
                        $deactivateAccount = $connection->prepare(
                            "UPDATE users
                             SET account_status = 'Inactive'
                             WHERE user_id = :user_id AND account_status = 'Active'"
                        );
                        $deactivateAccount->execute(['user_id' => (int) $targetUserId]);
                        $deactivateProfile = $connection->prepare(
                            "UPDATE students
                             SET student_status = 'Inactive'
                             WHERE student_id = :student_id"
                        );
                        $deactivateProfile->execute(['student_id' => (int) $studentRecord['student_id']]);
                        superAdminRecordAudit(
                            $connection,
                            (int) $user['user_id'],
                            'Deactivate',
                            'User Management',
                            'Deactivated Student account ID ' . (int) $targetUserId . '.'
                        );
                        $connection->commit();
                        $_SESSION['super_admin_flash'] = 'Student account deactivated.';
                        header('Location: super-admin-section.php?section=users', true, 303);
                        exit;
                    }
                } catch (Throwable $exception) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                    throw $exception;
                }
            }
        }
    } elseif ($sectionKey === 'roles-permissions') {
        $roleId = filter_var(
            $_POST['role_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $postedPermissions = $_POST['permission_ids'] ?? [];
        $selectedPermissions = [];
        if (is_array($postedPermissions)) {
            foreach ($postedPermissions as $postedPermission) {
                if (!is_string($postedPermission) && !is_int($postedPermission)) {
                    continue;
                }

                $permissionId = filter_var(
                    $postedPermission,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                if ($permissionId !== false) {
                    $selectedPermissions[] = $permissionId;
                }
            }
            $selectedPermissions = array_values(array_unique($selectedPermissions));
        }
        $selectedRoleId = $roleId === false ? null : $roleId;

        if ($selectedRoleId === null) {
            $errorMessage = 'Select a valid role.';
        } else {
            $roleExists = $connection->prepare('SELECT role_name FROM roles WHERE role_id = :role_id LIMIT 1');
            $roleExists->execute(['role_id' => $selectedRoleId]);
            $roleName = $roleExists->fetchColumn();
            $permissionRows = $connection->query('SELECT permission_id FROM permissions')->fetchAll(PDO::FETCH_COLUMN);
            $availablePermissionIds = array_map('intval', $permissionRows);
            $unknownPermissions = array_diff($selectedPermissions, $availablePermissionIds);

            if (!is_string($roleName)) {
                $errorMessage = 'The selected role does not exist.';
            } elseif ($unknownPermissions !== []) {
                $errorMessage = 'One or more selected permissions are unavailable. Reload the page and try again.';
            } else {
                try {
                    $connection->beginTransaction();
                    $removeAssignments = $connection->prepare(
                        'DELETE FROM role_permissions WHERE role_id = :role_id'
                    );
                    $removeAssignments->execute(['role_id' => $selectedRoleId]);
                    $addAssignment = $connection->prepare(
                        'INSERT INTO role_permissions (role_id, permission_id)
                         VALUES (:role_id, :permission_id)'
                    );

                    foreach ($selectedPermissions as $permissionId) {
                        $addAssignment->execute([
                            'role_id' => $selectedRoleId,
                            'permission_id' => $permissionId,
                        ]);
                    }

                    superAdminRecordAudit(
                        $connection,
                        (int) $user['user_id'],
                        'Update',
                        'Roles & Permissions',
                        'Updated permissions for the ' . $roleName . ' role.'
                    );
                    $connection->commit();
                    $_SESSION['super_admin_flash'] = 'Permissions saved for the ' . $roleName . ' role.';
                    header(
                        'Location: super-admin-section.php?section=roles-permissions&role_id=' . $selectedRoleId,
                        true,
                        303
                    );
                    exit;
                } catch (Throwable $exception) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                    throw $exception;
                }
            }
        }
    } elseif ($sectionKey === 'school-configuration') {
        $schoolName = superAdminPostString('school_name');
        $schoolCode = superAdminPostString('school_code');
        $schoolEmail = superAdminPostString('school_email');
        $schoolPhone = superAdminPostString('school_phone');
        $schoolAddress = superAdminPostString('school_address');
        $principalName = superAdminPostString('principal_name');
        $website = superAdminPostString('website');
        $oldInput = [
            'school_name' => $schoolName,
            'school_code' => $schoolCode,
            'school_email' => $schoolEmail,
            'school_phone' => $schoolPhone,
            'school_address' => $schoolAddress,
            'principal_name' => $principalName,
            'website' => $website,
        ];

        if ($schoolName === '') {
            $errorMessage = 'Enter the school name.';
            $fieldErrors['school_name'] = $errorMessage;
        } elseif ($schoolEmail !== '' && !filter_var($schoolEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Enter a valid school email address.';
            $fieldErrors['school_email'] = $errorMessage;
        } elseif ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            $errorMessage = 'Enter a complete website URL, including https://.';
            $fieldErrors['website'] = $errorMessage;
        } else {
            $settingsId = $connection->query(
                'SELECT setting_id FROM school_settings ORDER BY setting_id LIMIT 1'
            )->fetchColumn();
            $values = [
                'school_name' => $schoolName,
                'school_code' => $schoolCode !== '' ? $schoolCode : null,
                'school_email' => $schoolEmail !== '' ? $schoolEmail : null,
                'school_phone' => $schoolPhone !== '' ? $schoolPhone : null,
                'school_address' => $schoolAddress !== '' ? $schoolAddress : null,
                'principal_name' => $principalName !== '' ? $principalName : null,
                'website' => $website !== '' ? $website : null,
            ];

            if ($settingsId === false) {
                $saveSettings = $connection->prepare(
                    'INSERT INTO school_settings
                        (school_name, school_code, school_email, school_phone, school_address, principal_name, website)
                     VALUES
                        (:school_name, :school_code, :school_email, :school_phone, :school_address, :principal_name, :website)'
                );
            } else {
                $values['setting_id'] = (int) $settingsId;
                $saveSettings = $connection->prepare(
                    'UPDATE school_settings
                     SET school_name = :school_name,
                         school_code = :school_code,
                         school_email = :school_email,
                         school_phone = :school_phone,
                         school_address = :school_address,
                         principal_name = :principal_name,
                         website = :website
                     WHERE setting_id = :setting_id'
                );
            }

            $saveSettings->execute($values);
            superAdminRecordAudit(
                $connection,
                (int) $user['user_id'],
                'Update',
                'School Configuration',
                'Updated the school profile settings.'
            );
            $_SESSION['super_admin_flash'] = 'School information saved.';
            header('Location: super-admin-section.php?section=school-configuration', true, 303);
            exit;
        }
    } else {
        $errorMessage = 'This section is read-only. No changes were saved.';
    }
}

$sectionData = [];

switch ($sectionKey) {
    case 'admins':
        $statement = $connection->prepare(
            'SELECT u.user_id, u.first_name, u.last_name, u.username, u.email, u.account_status, u.created_at
             FROM users u
             INNER JOIN roles r ON r.role_id = u.role_id
             WHERE r.role_name = :role_name
             ORDER BY u.created_at DESC, u.user_id DESC'
        );
        $statement->execute(['role_name' => 'Admin']);
        $sectionData['admins'] = $statement->fetchAll();
        break;

    case 'users':
        $sectionData['users'] = $connection->query(
            'SELECT u.user_id, u.first_name, u.last_name, u.username, u.email, u.account_status, u.last_login, r.role_name
             FROM users u
             INNER JOIN roles r ON r.role_id = u.role_id
             ORDER BY u.created_at DESC, u.user_id DESC
             LIMIT 100'
        )->fetchAll();
        break;

    case 'roles-permissions':
        $sectionData['roles'] = $connection->query(
            'SELECT role_id, role_name, description FROM roles ORDER BY role_id'
        )->fetchAll();
        $sectionData['permissions'] = $connection->query(
            'SELECT permission_id, permission_name, module_name, description
             FROM permissions
             ORDER BY module_name, permission_name'
        )->fetchAll();

        $sectionData['assignedPermissionsByRoleId'] = [];
        foreach ($sectionData['roles'] as $role) {
            $sectionData['assignedPermissionsByRoleId'][(int) $role['role_id']] = [];
        }

        $rolePermissionRows = $connection->query(
            'SELECT role_id, permission_id
             FROM role_permissions
             ORDER BY role_id, permission_id'
        )->fetchAll();
        foreach ($rolePermissionRows as $rolePermission) {
            $roleId = (int) $rolePermission['role_id'];
            if (isset($sectionData['assignedPermissionsByRoleId'][$roleId])) {
                $sectionData['assignedPermissionsByRoleId'][$roleId][] = (int) $rolePermission['permission_id'];
            }
        }
        $sectionData['selectedRoleId'] = $selectedRoleId;
        break;

    case 'school-configuration':
        $sectionData['settings'] = $connection->query(
            'SELECT setting_id, school_name, school_code, school_email, school_phone,
                    school_address, principal_name, website, updated_at
             FROM school_settings
             ORDER BY setting_id
             LIMIT 1'
        )->fetch() ?: [];
        break;

    case 'academic-management':
        $sectionData['academicYears'] = $connection->query(
            'SELECT year_name, start_date, end_date, status
             FROM academic_years
             ORDER BY start_date DESC, academic_year_id DESC
             LIMIT 50'
        )->fetchAll();
        $sectionData['departments'] = $connection->query(
            'SELECT department_code, department_name
             FROM departments
             ORDER BY department_name
             LIMIT 100'
        )->fetchAll();
        $sectionData['programs'] = $connection->query(
            'SELECT p.program_code, p.program_name, p.status, d.department_name
             FROM programs p
             LEFT JOIN departments d ON d.department_id = p.department_id
             ORDER BY p.program_name
             LIMIT 100'
        )->fetchAll();
        $sectionData['sections'] = $connection->query(
            'SELECT s.section_name, s.year_level, s.capacity, s.status,
                    p.program_name, ay.year_name
             FROM sections s
             LEFT JOIN programs p ON p.program_id = s.program_id
             LEFT JOIN academic_years ay ON ay.academic_year_id = s.academic_year_id
             ORDER BY ay.start_date DESC, s.year_level, s.section_name
             LIMIT 100'
        )->fetchAll();
        break;

    case 'system-monitoring':
        $sectionData['databaseName'] = (string) $connection->query('SELECT DATABASE()')->fetchColumn();
        $sectionData['databaseVersion'] = (string) $connection->query('SELECT VERSION()')->fetchColumn();
        $uptimeStatus = $connection->query("SHOW GLOBAL STATUS LIKE 'Uptime'")->fetch();
        $sectionData['databaseUptime'] = isset($uptimeStatus['Value'])
            ? (int) $uptimeStatus['Value']
            : 0;
        $sectionData['databaseSize'] = (int) $connection->query(
            'SELECT COALESCE(SUM(data_length + index_length), 0)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()'
        )->fetchColumn();
        $sectionData['recentAuditEvents'] = (int) $connection->query(
            'SELECT COUNT(*) FROM audit_logs WHERE created_at >= CURRENT_TIMESTAMP - INTERVAL 30 DAY'
        )->fetchColumn();
        $sectionData['pendingEnrollments'] = (int) $connection->query(
            "SELECT COUNT(*) FROM enrollments WHERE enrollment_status = 'Pending'"
        )->fetchColumn();
        $sectionData['activeAcademicYears'] = (int) $connection->query(
            "SELECT COUNT(*) FROM academic_years WHERE status = 'Active'"
        )->fetchColumn();
        break;

    case 'audit-logs':
        $sectionData['auditLogs'] = $connection->query(
            'SELECT al.log_id, al.action, al.module_name, al.description, al.ip_address, al.created_at,
                    u.username
             FROM audit_logs al
             LEFT JOIN users u ON u.user_id = al.user_id
             ORDER BY al.created_at DESC, al.log_id DESC
             LIMIT 100'
        )->fetchAll();
        break;

    case 'database-backup':
        $sectionData['databaseName'] = (string) $connection->query('SELECT DATABASE()')->fetchColumn();
        break;

    case 'reports':
        $sectionData['administratorCount'] = (int) $connection->query(
            "SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = 'Admin'"
        )->fetchColumn();
        $sectionData['teacherCount'] = (int) $connection->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
        $sectionData['studentCount'] = (int) $connection->query('SELECT COUNT(*) FROM students')->fetchColumn();
        $sectionData['enrollmentCount'] = (int) $connection->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
        $sectionData['completedCollections'] = (float) $connection->query(
            "SELECT COALESCE(SUM(total_amount), 0) FROM payments WHERE payment_status = 'Completed'"
        )->fetchColumn();
        $sectionData['outstandingBalance'] = (float) $connection->query(
            'SELECT COALESCE(SUM(outstanding_balance), 0) FROM vw_student_balance'
        )->fetchColumn();
        $sectionData['enrollmentStatuses'] = $connection->query(
            'SELECT enrollment_status, COUNT(*) AS record_count
             FROM enrollments
             GROUP BY enrollment_status
             ORDER BY enrollment_status'
        )->fetchAll();
        break;
}

$dashboard = [
    'theme' => 'executive',
    'roleLabel' => 'Super Admin',
    'pageTitle' => $sectionDefinition['title'],
    'eyebrow' => $sectionDefinition['eyebrow'],
    'heading' => $sectionDefinition['title'],
    'description' => $sectionDefinition['description'],
    'navigationStyle' => 'super-admin',
    'sidebarTitle' => 'SUPER ADMIN PANEL',
    'sidebarCaption' => 'SYSTEM CONTROL',
    'homeHref' => 'super-admin-dashboard.php',
    'navItems' => superAdminNavigationItems($sectionKey),
    'sectionContent' => true,
];

$dashboardLayout = dirname(__DIR__) . '/app/Shared/dashboard_layout.php';
if (!is_file($dashboardLayout)) {
    header('Location: account.php', true, 303);
    exit;
}

require $dashboardLayout;
