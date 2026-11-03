<?php

declare(strict_types=1);

$escapeSectionValue = static fn (mixed $value): string => dashboardEscape(
    is_string($value) || is_int($value) || is_float($value) ? $value : ''
);
$fieldError = static fn (string $field): string => is_string($fieldErrors[$field] ?? null)
    ? $fieldErrors[$field]
    : '';
$csrfToken = appCsrfToken();
$sectionKey = $sectionKey ?? '';
?>
<?php if (is_string($flashMessage) && $flashMessage !== ''): ?>
    <p class="admin-notice admin-notice--success" role="status" tabindex="-1" data-status-summary><?= dashboardEscape($flashMessage) ?></p>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <p class="admin-notice admin-notice--error" role="alert" tabindex="-1" data-error-summary><?= dashboardEscape($errorMessage) ?></p>
<?php endif; ?>

<div class="admin-section">
    <?php switch ($sectionKey): case 'admins': ?>
        <div class="admin-panel" data-form-modal data-modal-trigger-label="Add Admin" data-modal-trigger-class="admin-button" data-modal-open-on-load="<?= $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create' && $errorMessage !== '' ? 'true' : 'false' ?>">
            <h2>Create an Admin account</h2>
            <p class="admin-panel-description">The new account receives the Admin role and can sign in as soon as it is created.</p>
            <form class="admin-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                <input type="hidden" name="action" value="create">
                <p class="admin-field-required-note"><span class="admin-required-marker">Required</span> fields must be completed.</p>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="admin-first-name">First name <span class="admin-required-marker">Required</span></label>
                        <input id="admin-first-name" name="first_name" autocomplete="given-name" required value="<?= $escapeSectionValue($oldInput['first_name'] ?? '') ?>"<?= $fieldError('first_name') !== '' ? ' aria-invalid="true" aria-describedby="admin-first-name-error" autofocus' : '' ?>>
                        <?php if ($fieldError('first_name') !== ''): ?><span class="admin-field-error" id="admin-first-name-error"><?= dashboardEscape($fieldError('first_name')) ?></span><?php endif; ?>
                    </div>
                    <div class="admin-field">
                        <label for="admin-last-name">Last name <span class="admin-required-marker">Required</span></label>
                        <input id="admin-last-name" name="last_name" autocomplete="family-name" required value="<?= $escapeSectionValue($oldInput['last_name'] ?? '') ?>"<?= $fieldError('last_name') !== '' ? ' aria-invalid="true" aria-describedby="admin-last-name-error" autofocus' : '' ?>>
                        <?php if ($fieldError('last_name') !== ''): ?><span class="admin-field-error" id="admin-last-name-error"><?= dashboardEscape($fieldError('last_name')) ?></span><?php endif; ?>
                    </div>
                    <div class="admin-field">
                        <label for="admin-email">Email address <span class="admin-required-marker">Required</span></label>
                        <input id="admin-email" name="email" type="email" autocomplete="email" required value="<?= $escapeSectionValue($oldInput['email'] ?? '') ?>"<?= $fieldError('email') !== '' ? ' aria-invalid="true" aria-describedby="admin-email-error" autofocus' : '' ?>>
                        <?php if ($fieldError('email') !== ''): ?><span class="admin-field-error" id="admin-email-error"><?= dashboardEscape($fieldError('email')) ?></span><?php endif; ?>
                    </div>
                    <div class="admin-field">
                        <label for="admin-username">Username <span class="admin-required-marker">Required</span></label>
                        <input id="admin-username" name="username" autocomplete="username" required value="<?= $escapeSectionValue($oldInput['username'] ?? '') ?>"<?= $fieldError('username') !== '' ? ' aria-invalid="true" aria-describedby="admin-username-error" autofocus' : '' ?>>
                        <?php if ($fieldError('username') !== ''): ?><span class="admin-field-error" id="admin-username-error"><?= dashboardEscape($fieldError('username')) ?></span><?php endif; ?>
                    </div>
                    <div class="admin-field admin-field--wide">
                        <label for="admin-password">Temporary password <span class="admin-required-marker">Required</span></label>
                        <input id="admin-password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="<?= dashboardEscape(appPasswordMaxBytes()) ?>" required aria-describedby="admin-password-hint<?= $fieldError('password') !== '' ? ' admin-password-error' : '' ?>"<?= $fieldError('password') !== '' ? ' aria-invalid="true" autofocus' : '' ?>>
                        <span class="admin-field-hint" id="admin-password-hint">Use at least 12 characters and share this initial password securely.</span>
                        <?php if ($fieldError('password') !== ''): ?><span class="admin-field-error" id="admin-password-error"><?= dashboardEscape($fieldError('password')) ?></span><?php endif; ?>
                    </div>
                </div>
                <button class="admin-button" type="submit">Create Admin account</button>
            </form>
        </div>

        <div class="admin-panel">
            <h2>Admin accounts</h2>
            <?php if ($sectionData['admins'] === []): ?>
                <p class="admin-empty">No Admin accounts exist yet. Create an account above to get started.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Admin accounts table; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>School Admin accounts and their current status</caption>
                        <thead>
                            <tr><th scope="col">Name</th><th scope="col">Username</th><th scope="col">Email</th><th scope="col">Status</th><th scope="col">Created</th><th scope="col">Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectionData['admins'] as $admin): ?>
                                <tr>
                                    <th scope="row"><?= dashboardEscape(trim($admin['first_name'] . ' ' . $admin['last_name'])) ?></th>
                                    <td><?= dashboardEscape($admin['username']) ?></td>
                                    <td><?= dashboardEscape($admin['email']) ?></td>
                                    <td><?= dashboardEscape($admin['account_status']) ?></td>
                                    <td><?= dashboardEscape($admin['created_at']) ?></td>
                                    <td>
                                        <?php if ($admin['account_status'] === 'Active'): ?>
                                            <form method="post" data-confirm-deactivation data-admin-name="<?= dashboardEscape(trim($admin['first_name'] . ' ' . $admin['last_name'])) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="user_id" value="<?= dashboardEscape((int) $admin['user_id']) ?>">
                                                <button class="admin-button admin-button--danger" type="submit">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <span>Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php break; case 'users': ?>
        <div class="admin-panel">
            <h2>Account directory</h2>
            <p class="admin-panel-description">Student accounts are created automatically when applicants complete enrollment. Super Admins can deactivate active Student accounts here; Admins can do so in Student Management. Showing up to 100 most recently created accounts.</p>
            <?php if ($sectionData['users'] === []): ?>
                <p class="admin-empty">No user accounts are recorded.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="User account directory; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>User accounts, assigned roles, and account status</caption>
                        <thead>
                            <tr><th scope="col">Name</th><th scope="col">Username</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Last sign-in</th><th scope="col">Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectionData['users'] as $account): ?>
                                <tr>
                                    <th scope="row"><?= dashboardEscape(trim($account['first_name'] . ' ' . $account['last_name'])) ?></th>
                                    <td><?= dashboardEscape($account['username']) ?></td>
                                    <td><?= dashboardEscape($account['email']) ?></td>
                                    <td><?= dashboardEscape($account['role_name']) ?></td>
                                    <td><?= dashboardEscape($account['account_status']) ?></td>
                                    <td><?= dashboardEscape($account['last_login'] ?? 'Never') ?></td>
                                    <td>
                                        <?php if ($account['role_name'] === 'Student' && $account['account_status'] === 'Active'): ?>
                                            <form method="post" data-confirm-deactivation data-account-type="Student" data-admin-name="<?= dashboardEscape(trim($account['first_name'] . ' ' . $account['last_name'])) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                <input type="hidden" name="action" value="deactivate-student-account">
                                                <input type="hidden" name="user_id" value="<?= dashboardEscape((int) $account['user_id']) ?>">
                                                <button class="admin-button admin-button--danger" type="submit">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <span>—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php break; case 'roles-permissions': ?>
        <div class="admin-panel">
            <h2>Automatic role assignment</h2>
            <p class="admin-panel-description">Users cannot choose their own role during registration. Public enrollment assigns the Student role, and accounts created through Admin Management receive the Admin role. Teacher accounts require an authorized provisioning flow.</p>
        </div>
        <?php if ($sectionData['permissions'] === []): ?>
            <div class="admin-panel">
                <h2>Permissions are not configured</h2>
                <p class="admin-empty">There are no permission records to assign yet. No role access has been changed.</p>
            </div>
        <?php elseif ($sectionData['roles'] === []): ?>
            <div class="admin-panel">
                <h2>No roles are available</h2>
                <p class="admin-empty">Create or restore a role before assigning permissions.</p>
            </div>
        <?php else: ?>
            <?php
            $permissionsByModule = [];
            foreach ($sectionData['permissions'] as $permission) {
                $permissionsByModule[$permission['module_name']][] = $permission;
            }
            ?>
            <div class="admin-role-cards">
                <?php foreach ($sectionData['roles'] as $role): ?>
                    <?php
                    $roleId = (int) $role['role_id'];
                    $assignedPermissionIds = $sectionData['assignedPermissionsByRoleId'][$roleId] ?? [];
                    ?>
                    <article class="admin-panel admin-role-card">
                        <h2><?= dashboardEscape($role['role_name']) ?></h2>
                        <?php if (trim((string) $role['description']) !== ''): ?>
                            <p class="admin-panel-description"><?= dashboardEscape($role['description']) ?></p>
                        <?php endif; ?>
                        <h3>Assigned permissions</h3>
                        <?php if ($assignedPermissionIds === []): ?>
                            <p class="admin-empty">No permissions assigned.</p>
                        <?php else: ?>
                            <div class="admin-role-permissions">
                                <?php foreach ($permissionsByModule as $moduleName => $permissions): ?>
                                    <?php
                                    $assignedInModule = array_filter(
                                        $permissions,
                                        static fn (array $permission): bool => in_array(
                                            (int) $permission['permission_id'],
                                            $assignedPermissionIds,
                                            true
                                        )
                                    );
                                    ?>
                                    <?php if ($assignedInModule !== []): ?>
                                        <section>
                                            <h4><?= dashboardEscape($moduleName) ?></h4>
                                            <ul>
                                                <?php foreach ($assignedInModule as $permission): ?>
                                                    <li><?= dashboardEscape($permission['permission_name']) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </section>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="admin-panel admin-role-editor" data-form-modal data-modal-trigger-label="Edit <?= dashboardEscape($role['role_name']) ?> permissions" data-modal-trigger-class="admin-button" data-modal-open-on-load="<?= $_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage !== '' && $sectionData['selectedRoleId'] === $roleId ? 'true' : 'false' ?>">
                            <h2>Edit permissions for <?= dashboardEscape($role['role_name']) ?></h2>
                            <form class="admin-form" method="post">
                                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                <input type="hidden" name="role_id" value="<?= dashboardEscape($roleId) ?>">
                                <div class="permission-groups">
                                    <?php foreach ($permissionsByModule as $moduleName => $permissions): ?>
                                        <fieldset class="permission-group">
                                            <legend><?= dashboardEscape($moduleName) ?></legend>
                                            <?php foreach ($permissions as $permission): ?>
                                                <label class="permission-option">
                                                    <input
                                                        type="checkbox"
                                                        name="permission_ids[]"
                                                        value="<?= dashboardEscape((int) $permission['permission_id']) ?>"
                                                        <?= in_array((int) $permission['permission_id'], $assignedPermissionIds, true) ? 'checked' : '' ?>
                                                    >
                                                    <span>
                                                        <strong><?= dashboardEscape($permission['permission_name']) ?></strong>
                                                        <?php if (trim((string) $permission['description']) !== ''): ?>
                                                            <span class="permission-description"><?= dashboardEscape($permission['description']) ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </fieldset>
                                    <?php endforeach; ?>
                                </div>
                                <p class="admin-field-hint">Saving replaces this role’s current permission assignments with the selected permissions.</p>
                                <button class="admin-button" type="submit">Save role permissions</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php break; case 'school-configuration': ?>
        <?php
        $profile = array_merge([
            'school_name' => '',
            'school_code' => '',
            'school_email' => '',
            'school_phone' => '',
            'school_address' => '',
            'principal_name' => '',
            'website' => '',
            'updated_at' => '',
        ], $sectionData['settings']);
        $settings = array_merge($profile, $oldInput);
        $profileFields = [
            'School name' => 'school_name',
            'School code' => 'school_code',
            'School email' => 'school_email',
            'School phone' => 'school_phone',
            'Principal' => 'principal_name',
            'Website' => 'website',
            'School address' => 'school_address',
            'Last updated' => 'updated_at',
        ];
        ?>
        <div class="admin-panel">
            <h2>Current school profile</h2>
            <p class="admin-panel-description">Saved school information currently used by the system.</p>
            <dl class="admin-status-list admin-school-profile">
                <?php foreach ($profileFields as $label => $field): ?>
                    <?php $value = trim((string) ($profile[$field] ?? '')); ?>
                    <div>
                        <dt><?= dashboardEscape($label) ?></dt>
                        <dd><?= $value !== '' ? nl2br(dashboardEscape($value)) : 'Not configured' ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
        <div class="admin-panel" data-form-modal data-modal-trigger-label="Edit school information" data-modal-trigger-class="admin-button" data-modal-open-on-load="<?= $_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage !== '' ? 'true' : 'false' ?>">
            <h2>School information</h2>
            <p class="admin-panel-description">These details are stored in the school profile. No currency setting is currently available.</p>
            <form class="admin-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                <p class="admin-field-required-note"><span class="admin-required-marker">Required</span> fields must be completed.</p>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="school-name">School name <span class="admin-required-marker">Required</span></label>
                        <input id="school-name" name="school_name" required value="<?= $escapeSectionValue($settings['school_name']) ?>"<?= $fieldError('school_name') !== '' ? ' aria-invalid="true" aria-describedby="school-name-error" autofocus' : '' ?>>
                        <?php if ($fieldError('school_name') !== ''): ?><span class="admin-field-error" id="school-name-error"><?= dashboardEscape($fieldError('school_name')) ?></span><?php endif; ?>
                    </div>
                    <div class="admin-field">
                        <label for="school-code">School code</label>
                        <input id="school-code" name="school_code" value="<?= $escapeSectionValue($settings['school_code']) ?>">
                    </div>
                    <div class="admin-field">
                        <label for="school-email">School email</label>
                        <input id="school-email" name="school_email" type="email" autocomplete="email" value="<?= $escapeSectionValue($settings['school_email']) ?>"<?= $fieldError('school_email') !== '' ? ' aria-invalid="true" aria-describedby="school-email-error" autofocus' : '' ?>>
                        <?php if ($fieldError('school_email') !== ''): ?><span class="admin-field-error" id="school-email-error"><?= dashboardEscape($fieldError('school_email')) ?></span><?php endif; ?>
                    </div>
                    <div class="admin-field">
                        <label for="school-phone">School phone</label>
                        <input id="school-phone" name="school_phone" type="tel" autocomplete="tel" value="<?= $escapeSectionValue($settings['school_phone']) ?>">
                    </div>
                    <div class="admin-field">
                        <label for="principal-name">Principal</label>
                        <input id="principal-name" name="principal_name" value="<?= $escapeSectionValue($settings['principal_name']) ?>">
                    </div>
                    <div class="admin-field">
                        <label for="school-website">Website</label>
                        <input id="school-website" name="website" type="url" placeholder="https://" value="<?= $escapeSectionValue($settings['website']) ?>"<?= $fieldError('website') !== '' ? ' aria-invalid="true" aria-describedby="school-website-error" autofocus' : '' ?>>
                        <?php if ($fieldError('website') !== ''): ?><span class="admin-field-error" id="school-website-error"><?= dashboardEscape($fieldError('website')) ?></span><?php endif; ?>
                    </div>
                    <div class="admin-field admin-field--wide">
                        <label for="school-address">School address</label>
                        <textarea id="school-address" name="school_address" rows="3"><?= $escapeSectionValue($settings['school_address']) ?></textarea>
                    </div>
                </div>
                <button class="admin-button" type="submit">Save school information</button>
            </form>
        </div>
    <?php break; case 'academic-management': ?>
        <div class="admin-panel">
            <h2>Academic years</h2>
            <?php if ($sectionData['academicYears'] === []): ?>
                <p class="admin-empty">No academic years are configured yet.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Academic years table; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Configured academic years and dates</caption>
                        <thead><tr><th scope="col">Academic year</th><th scope="col">Start date</th><th scope="col">End date</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($sectionData['academicYears'] as $year): ?>
                                <tr><th scope="row"><?= dashboardEscape($year['year_name']) ?></th><td><?= dashboardEscape($year['start_date']) ?></td><td><?= dashboardEscape($year['end_date']) ?></td><td><?= dashboardEscape($year['status']) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="admin-panel">
            <h2>Departments</h2>
            <?php if ($sectionData['departments'] === []): ?>
                <p class="admin-empty">No departments are configured yet.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Departments table; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>School departments</caption>
                        <thead><tr><th scope="col">Code</th><th scope="col">Department</th></tr></thead>
                        <tbody><?php foreach ($sectionData['departments'] as $department): ?><tr><td><?= dashboardEscape($department['department_code']) ?></td><th scope="row"><?= dashboardEscape($department['department_name']) ?></th></tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="admin-panel">
            <h2>Programs</h2>
            <?php if ($sectionData['programs'] === []): ?>
                <p class="admin-empty">No programs are configured yet.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Programs table; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Programs and their departments</caption>
                        <thead><tr><th scope="col">Code</th><th scope="col">Program</th><th scope="col">Department</th><th scope="col">Status</th></tr></thead>
                        <tbody><?php foreach ($sectionData['programs'] as $program): ?><tr><td><?= dashboardEscape($program['program_code']) ?></td><th scope="row"><?= dashboardEscape($program['program_name']) ?></th><td><?= dashboardEscape($program['department_name'] ?? 'Not assigned') ?></td><td><?= dashboardEscape($program['status']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="admin-panel">
            <h2>Sections</h2>
            <?php if ($sectionData['sections'] === []): ?>
                <p class="admin-empty">No class sections are configured yet.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Sections table; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Class sections by program and academic year</caption>
                        <thead><tr><th scope="col">Section</th><th scope="col">Program</th><th scope="col">Year level</th><th scope="col">Academic year</th><th scope="col">Capacity</th><th scope="col">Status</th></tr></thead>
                        <tbody><?php foreach ($sectionData['sections'] as $classSection): ?><tr><th scope="row"><?= dashboardEscape($classSection['section_name']) ?></th><td><?= dashboardEscape($classSection['program_name'] ?? 'Not assigned') ?></td><td><?= dashboardEscape((int) $classSection['year_level']) ?></td><td><?= dashboardEscape($classSection['year_name'] ?? 'Not assigned') ?></td><td><?= dashboardEscape((int) $classSection['capacity']) ?></td><td><?= dashboardEscape($classSection['status']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php break; case 'system-monitoring': ?>
        <div class="admin-panel">
            <h2>System status</h2>
            <dl class="admin-status-list">
                <div><dt>Database</dt><dd><?= dashboardEscape($sectionData['databaseName']) ?></dd></div>
                <div><dt>Database engine</dt><dd><?= dashboardEscape($sectionData['databaseVersion']) ?></dd></div>
                <div><dt>Database size</dt><dd><?= dashboardEscape(number_format($sectionData['databaseSize'] / 1024 / 1024, 2)) ?> MB</dd></div>
                <div><dt>Database server uptime</dt><dd><?= dashboardEscape(number_format($sectionData['databaseUptime'] / 86400, 1)) ?> days</dd></div>
                <div><dt>Active academic years</dt><dd><?= dashboardEscape($sectionData['activeAcademicYears']) ?></dd></div>
                <div><dt>Pending enrollments</dt><dd><?= dashboardEscape($sectionData['pendingEnrollments']) ?></dd></div>
                <div><dt>Audit events in the last 30 days</dt><dd><?= dashboardEscape($sectionData['recentAuditEvents']) ?></dd></div>
            </dl>
            <p class="admin-panel-description">These figures are read from the database when this page loads. Application server and scheduled backup health are not currently tracked.</p>
        </div>
    <?php break; case 'audit-logs': ?>
        <div class="admin-panel">
            <h2>Recent system activity</h2>
            <p class="admin-panel-description">Showing up to 100 most recent audit records.</p>
            <?php if ($sectionData['auditLogs'] === []): ?>
                <p class="admin-empty">No audit records are available.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Audit log table; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Recent audit events</caption>
                        <thead><tr><th scope="col">Date and time</th><th scope="col">User</th><th scope="col">Action</th><th scope="col">Module</th><th scope="col">Description</th><th scope="col">IP address</th></tr></thead>
                        <tbody>
                            <?php foreach ($sectionData['auditLogs'] as $auditLog): ?>
                                <tr>
                                    <th scope="row"><?= dashboardEscape($auditLog['created_at']) ?></th>
                                    <td><?= dashboardEscape($auditLog['username'] ?? 'System') ?></td>
                                    <td><?= dashboardEscape($auditLog['action']) ?></td>
                                    <td><?= dashboardEscape($auditLog['module_name']) ?></td>
                                    <td class="admin-description-cell"><?= dashboardEscape($auditLog['description'] ?? '') ?></td>
                                    <td><?= dashboardEscape($auditLog['ip_address'] ?? 'Not recorded') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php break; case 'database-backup': ?>
        <div class="admin-panel">
            <h2>Create a local database backup</h2>
            <p class="admin-panel-description">For safety, database exports are not started by a web request. Run the backup from a trusted local XAMPP command prompt and save it outside the public web folder.</p>
            <ol class="admin-instructions">
                <li>Start MySQL in the XAMPP Control Panel.</li>
                <li>Open PowerShell and make a private backup folder, for example <code>C:\ISMS-Backups</code>.</li>
                <li>Run the command below. Enter your database password when prompted, then store the backup in a protected location.</li>
            </ol>
            <pre class="admin-code"><code>New-Item -ItemType Directory -Force C:\ISMS-Backups
cmd /c "&quot;C:\xampp\mysql\bin\mysqldump.exe&quot; --host=127.0.0.1 --port=3306 --user=root --password --single-transaction --routines --triggers <?= dashboardEscape($sectionData['databaseName']) ?> &gt; &quot;C:\ISMS-Backups\isms_database-backup.sql&quot;"</code></pre>
            <p class="admin-notice admin-notice--info">Replace the connection values with the same DB_HOST, DB_PORT, DB_USER, and DB_PASS configuration used by the application. Keep credentials and backup files private; do not place SQL backups under public/.</p>
            <h2>Restore and maintenance</h2>
            <p class="admin-panel-description">Test restoration against a separate local database before relying on a backup. This page does not currently track backup history or run scheduled maintenance.</p>
        </div>
    <?php break; case 'reports': ?>
        <div class="admin-panel">
            <h2>Institution-wide summary</h2>
            <dl class="admin-status-list">
                <div><dt>Administrators</dt><dd><?= dashboardEscape(number_format($sectionData['administratorCount'])) ?></dd></div>
                <div><dt>Teachers</dt><dd><?= dashboardEscape(number_format($sectionData['teacherCount'])) ?></dd></div>
                <div><dt>Students</dt><dd><?= dashboardEscape(number_format($sectionData['studentCount'])) ?></dd></div>
                <div><dt>Enrollment records</dt><dd><?= dashboardEscape(number_format($sectionData['enrollmentCount'])) ?></dd></div>
                <div><dt>Completed-payment collections</dt><dd><?= dashboardEscape(number_format($sectionData['completedCollections'], 2)) ?></dd></div>
                <div><dt>Outstanding student balances</dt><dd><?= dashboardEscape(number_format($sectionData['outstandingBalance'], 2)) ?></dd></div>
            </dl>
            <p class="admin-panel-description">Financial amounts have no currency label because the database does not currently define a school currency.</p>
        </div>
        <div class="admin-panel">
            <h2>Enrollments by status</h2>
            <?php if ($sectionData['enrollmentStatuses'] === []): ?>
                <p class="admin-empty">No enrollment records are available to report.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Enrollment summary table; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Enrollment record totals grouped by status</caption>
                        <thead><tr><th scope="col">Status</th><th scope="col">Records</th></tr></thead>
                        <tbody><?php foreach ($sectionData['enrollmentStatuses'] as $status): ?><tr><th scope="row"><?= dashboardEscape($status['enrollment_status']) ?></th><td><?= dashboardEscape(number_format((int) $status['record_count'])) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endswitch; ?>
</div>
