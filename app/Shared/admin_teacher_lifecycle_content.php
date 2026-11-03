<?php

declare(strict_types=1);

$lifecycleValue = static fn (string $key, string $fallback = ''): string => is_string($oldInput[$key] ?? null)
    ? $oldInput[$key]
    : $fallback;
$lifecycleError = static fn (string $key): string => is_string($fieldErrors[$key] ?? null)
    ? $fieldErrors[$key]
    : '';
$renderInput = static function (
    string $name,
    string $label,
    string $type = 'text',
    bool $required = false,
    string $value = '',
    ?string $hint = null,
    ?string $maxDate = null
) use ($lifecycleError): void {
    $id = 'teacher-lifecycle-' . $name;
    $error = $lifecycleError($name);
    $describedBy = [];
    if ($hint !== null) {
        $describedBy[] = $id . '-hint';
    }
    if ($error !== '') {
        $describedBy[] = $id . '-error';
    }
    ?>
    <div class="admin-field">
        <label for="<?= dashboardEscape($id) ?>">
            <?= dashboardEscape($label) ?>
            <?php if ($required): ?><span class="admin-required-marker">Required</span><?php endif; ?>
        </label>
        <?php if ($hint !== null): ?><p class="admin-field-hint" id="<?= dashboardEscape($id) ?>-hint"><?= dashboardEscape($hint) ?></p><?php endif; ?>
        <input
            id="<?= dashboardEscape($id) ?>"
            name="<?= dashboardEscape($name) ?>"
            type="<?= dashboardEscape($type) ?>"
            value="<?= dashboardEscape($value) ?>"
            <?= $required ? 'required' : '' ?>
            <?= $type === 'date' && $maxDate !== null ? 'max="' . dashboardEscape($maxDate) . '"' : '' ?>
            <?= $type === 'email' ? 'autocomplete="email"' : '' ?>
            <?= $error !== '' ? 'aria-invalid="true"' : '' ?>
            <?= $describedBy !== [] ? 'aria-describedby="' . dashboardEscape(implode(' ', $describedBy)) . '"' : '' ?>
        >
        <?php if ($error !== ''): ?><p class="admin-field-error" id="<?= dashboardEscape($id) ?>-error"><?= dashboardEscape($error) ?></p><?php endif; ?>
    </div>
    <?php
};
?>
<?php if (is_string($flashMessage) && $flashMessage !== ''): ?>
    <p class="admin-notice admin-notice--success" role="status" tabindex="-1" data-status-summary><?= dashboardEscape($flashMessage) ?></p>
<?php endif; ?>
<?php if (is_string($setupLink) && $setupLink !== ''): ?>
    <div class="admin-field">
        <label for="teacher-setup-link">One-time teacher setup link</label>
        <p class="admin-field-hint" id="teacher-setup-link-hint">Select this field and copy the link to share it with the teacher.</p>
        <textarea id="teacher-setup-link" rows="3" readonly aria-describedby="teacher-setup-link-hint"><?= dashboardEscape($setupLink) ?></textarea>
    </div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <p class="admin-notice admin-notice--error" role="alert" tabindex="-1" data-error-summary><?= dashboardEscape($errorMessage) ?></p>
<?php endif; ?>

<div class="admin-section">
    <p class="admin-notice admin-notice--info">
        Teacher hiring, account setup, dated performance history, leave approval, and resignation records are managed here.
        Teaching assignments remain in <a href="admin-section.php?section=academic-management&amp;academic_view=teacher-assignments">Academic Management</a>.
    </p>

    <?php if ($view === 'hiring'): ?>
        <section
            class="admin-panel"
            aria-labelledby="teacher-candidate-form-heading"
            data-form-modal
            data-modal-trigger-label="Add teacher applicant"
            data-modal-trigger-class="admin-button"
            data-modal-open-on-load="<?= $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_candidate' && ($errorMessage !== '' || $fieldErrors !== []) ? 'true' : 'false' ?>"
        >
            <h2 id="teacher-candidate-form-heading">Register an applicant</h2>
            <p class="admin-panel-description">Applicants are entered by Admin. A Teacher account is created only after the applicant reaches Offer and is hired.</p>
            <form class="admin-form" method="post" action="admin-teacher-lifecycle.php?view=hiring">
                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                <input type="hidden" name="view" value="hiring">
                <input type="hidden" name="action" value="create_candidate">
                <p class="admin-field-required-note"><span class="admin-required-marker">Required</span> fields must be completed.</p>
                <div class="admin-form-grid">
                    <?php $renderInput('first_name', 'First name', 'text', true, $lifecycleValue('first_name')); ?>
                    <?php $renderInput('middle_name', 'Middle name', 'text', false, $lifecycleValue('middle_name')); ?>
                    <?php $renderInput('last_name', 'Last name', 'text', true, $lifecycleValue('last_name')); ?>
                    <?php $renderInput('email', 'Email address', 'email', true, $lifecycleValue('email')); ?>
                    <?php $renderInput('phone', 'Phone', 'tel', false, $lifecycleValue('phone')); ?>
                    <div class="admin-field">
                        <label for="teacher-lifecycle-department_id">Department</label>
                        <select id="teacher-lifecycle-department_id" name="department_id"<?= $lifecycleError('department_id') !== '' ? ' aria-invalid="true" aria-describedby="teacher-lifecycle-department_id-error"' : '' ?>>
                            <option value="">Select a department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= dashboardEscape((int) $department['department_id']) ?>"<?= $lifecycleValue('department_id') === (string) $department['department_id'] ? ' selected' : '' ?>><?= dashboardEscape($department['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($lifecycleError('department_id') !== ''): ?><p class="admin-field-error" id="teacher-lifecycle-department_id-error"><?= dashboardEscape($lifecycleError('department_id')) ?></p><?php endif; ?>
                    </div>
                    <?php $renderInput('specialization', 'Specialization', 'text', false, $lifecycleValue('specialization')); ?>
                    <?php $renderInput('application_date', 'Application date', 'date', true, $lifecycleValue('application_date', $today), null, $today); ?>
                    <div class="admin-field admin-field--wide">
                        <label for="teacher-lifecycle-notes">Applicant notes</label>
                        <textarea id="teacher-lifecycle-notes" name="notes" rows="3"><?= dashboardEscape($lifecycleValue('notes')) ?></textarea>
                    </div>
                </div>
                <div class="admin-form-actions">
                    <button class="admin-button" type="submit">Add applicant</button>
                </div>
            </form>
        </section>

        <section class="admin-panel" aria-labelledby="teacher-candidate-list-heading">
            <h2 id="teacher-candidate-list-heading">Hiring pipeline</h2>
            <?php if ($candidateRows === []): ?>
                <p class="admin-empty">No applicants are recorded yet. Add an applicant above to begin the hiring workflow.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">Scroll horizontally if needed to view all applicant details and actions.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Hiring pipeline; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Teacher applicants, hiring stages, and account setup status</caption>
                        <thead>
                            <tr>
                                <th scope="col">Applicant</th>
                                <th scope="col">Application</th>
                                <th scope="col">Department</th>
                                <th scope="col">Stage</th>
                                <th scope="col">Specialization and notes</th>
                                <th scope="col">Next step</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidateRows as $candidate): ?>
                                <?php $candidateId = (int) $candidate['candidate_id']; ?>
                                <tr>
                                    <th scope="row">
                                        <?= dashboardEscape($candidate['first_name'] . ' ' . $candidate['last_name']) ?>
                                        <br><span class="schedule-muted"><?= dashboardEscape($candidate['email']) ?></span>
                                        <?php if ($candidate['phone'] !== null && $candidate['phone'] !== ''): ?>
                                            <br><span class="schedule-muted"><?= dashboardEscape($candidate['phone']) ?></span>
                                        <?php endif; ?>
                                    </th>
                                    <td><?= dashboardEscape($candidate['application_date']) ?></td>
                                    <td><?= dashboardEscape((string) ($candidate['department_name'] ?? 'Not assigned')) ?></td>
                                    <td><?= dashboardEscape($candidate['application_status']) ?></td>
                                    <td>
                                        <?= dashboardEscape((string) ($candidate['specialization'] ?: '—')) ?>
                                        <?php if ($candidate['notes'] !== null && $candidate['notes'] !== ''): ?>
                                            <br><?= nl2br(dashboardEscape($candidate['notes'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (in_array($candidate['application_status'], ['Applied', 'Interview', 'Offer'], true)): ?>
                                            <form class="admin-form" method="post" action="admin-teacher-lifecycle.php?view=hiring">
                                                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                <input type="hidden" name="view" value="hiring">
                                                <input type="hidden" name="action" value="update_candidate_status">
                                                <input type="hidden" name="candidate_id" value="<?= dashboardEscape($candidateId) ?>">
                                                <label for="candidate-stage-<?= dashboardEscape($candidateId) ?>">Move to stage</label>
                                                <select id="candidate-stage-<?= dashboardEscape($candidateId) ?>" name="application_status" required>
                                                    <option value="Interview">Interview</option>
                                                    <option value="Offer">Offer</option>
                                                    <option value="Rejected">Rejected</option>
                                                </select>
                                                <button class="admin-button admin-button--secondary" type="submit">Update stage</button>
                                            </form>
                                            <?php if ($candidate['application_status'] === 'Offer'): ?>
                                                <form class="admin-form" method="post" action="admin-teacher-lifecycle.php?view=hiring">
                                                    <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                    <input type="hidden" name="view" value="hiring">
                                                    <input type="hidden" name="action" value="hire_candidate">
                                                    <input type="hidden" name="candidate_id" value="<?= dashboardEscape($candidateId) ?>">
                                                    <label for="employee-number-<?= dashboardEscape($candidateId) ?>">Employee number</label>
                                                    <input id="employee-number-<?= dashboardEscape($candidateId) ?>" name="employee_number" maxlength="50" required>
                                                    <?php if ($lifecycleError('employee_number') !== '' && $lifecycleValue('candidate_id') === (string) $candidateId): ?>
                                                        <p class="admin-field-error"><?= dashboardEscape($lifecycleError('employee_number')) ?></p>
                                                    <?php endif; ?>
                                                    <label for="hire-date-<?= dashboardEscape($candidateId) ?>">Hire date</label>
                                                    <input id="hire-date-<?= dashboardEscape($candidateId) ?>" type="date" name="hire_date" value="<?= dashboardEscape($today) ?>" max="<?= dashboardEscape($today) ?>" required>
                                                    <?php if ($lifecycleError('hire_date') !== '' && $lifecycleValue('candidate_id') === (string) $candidateId): ?>
                                                        <p class="admin-field-error"><?= dashboardEscape($lifecycleError('hire_date')) ?></p>
                                                    <?php endif; ?>
                                                    <button class="admin-button" type="submit">Hire and create setup link</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php elseif ($candidate['application_status'] === 'Hired'): ?>
                                            <p>
                                                Teacher <?= dashboardEscape((string) $candidate['employee_number']) ?> —
                                                <?= dashboardEscape((string) ($candidate['account_status'] ?? 'Account not linked')) ?>
                                            </p>
                                            <?php if ($candidate['account_status'] === 'Pending'): ?>
                                                <form method="post" action="admin-teacher-lifecycle.php?view=hiring">
                                                    <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                    <input type="hidden" name="view" value="hiring">
                                                    <input type="hidden" name="action" value="resend_invite">
                                                    <input type="hidden" name="teacher_id" value="<?= dashboardEscape((int) $candidate['teacher_id']) ?>">
                                                    <button class="admin-button admin-button--secondary" type="submit">Generate new setup link</button>
                                                </form>
                                                <p class="admin-panel-description">
                                                    <?= $candidate['invite_used_at'] !== null
                                                        ? 'The invitation was used; account activation may be pending.'
                                                        : 'Setup link expires ' . dashboardEscape((string) ($candidate['invite_expires_at'] ?? '')) . '.' ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span>No further action</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($pendingInviteRows !== []): ?>
            <section class="admin-panel" aria-labelledby="pending-teacher-invites-heading">
                <h2 id="pending-teacher-invites-heading">Pending teacher account invitations</h2>
                <p class="admin-panel-description">Pending accounts cannot sign in until the teacher chooses a password from the email link.</p>
                <ul>
                    <?php foreach ($pendingInviteRows as $pendingInvite): ?>
                        <li>
                            <?= dashboardEscape($pendingInvite['employee_number'] . ' — ' . $pendingInvite['first_name'] . ' ' . $pendingInvite['last_name']) ?>:
                            invitation expires <?= dashboardEscape($pendingInvite['expires_at']) ?>.
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php elseif ($view === 'performance'): ?>
        <section
            class="admin-panel"
            aria-labelledby="teacher-review-form-heading"
            data-form-modal
            data-modal-trigger-label="Add performance review"
            data-modal-trigger-class="admin-button"
            data-modal-open-on-load="<?= $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_review' && ($errorMessage !== '' || $fieldErrors !== []) ? 'true' : 'false' ?>"
        >
            <h2 id="teacher-review-form-heading">Add a dated performance review</h2>
            <form class="admin-form" method="post" action="admin-teacher-lifecycle.php?view=performance">
                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                <input type="hidden" name="view" value="performance">
                <input type="hidden" name="action" value="create_review">
                <p class="admin-field-required-note"><span class="admin-required-marker">Required</span> fields must be completed.</p>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="teacher-review-teacher">Teacher <span class="admin-required-marker">Required</span></label>
                        <select id="teacher-review-teacher" name="teacher_id" required<?= $lifecycleError('teacher_id') !== '' ? ' aria-invalid="true" aria-describedby="teacher-review-teacher-error"' : '' ?>>
                            <option value="">Select a teacher</option>
                            <?php foreach ($eligibleTeachers as $teacher): ?>
                                <option value="<?= dashboardEscape((int) $teacher['teacher_id']) ?>"<?= $lifecycleValue('teacher_id') === (string) $teacher['teacher_id'] ? ' selected' : '' ?>>
                                    <?= dashboardEscape($teacher['employee_number'] . ' — ' . $teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($lifecycleError('teacher_id') !== ''): ?><p class="admin-field-error" id="teacher-review-teacher-error"><?= dashboardEscape($lifecycleError('teacher_id')) ?></p><?php endif; ?>
                    </div>
                    <?php $renderInput('review_date', 'Review date', 'date', true, $lifecycleValue('review_date', $today), null, $today); ?>
                    <?php $renderInput('review_period', 'Review period', 'text', true, $lifecycleValue('review_period'), 'For example, 2025–2026 or First semester.'); ?>
                    <div class="admin-field">
                        <label for="teacher-lifecycle-rating">Overall rating <span class="admin-required-marker">Required</span></label>
                        <select id="teacher-lifecycle-rating" name="rating" required<?= $lifecycleError('rating') !== '' ? ' aria-invalid="true" aria-describedby="teacher-lifecycle-rating-error"' : '' ?>>
                            <option value="">Select a rating</option>
                            <?php foreach ([1 => '1 — Needs improvement', 2 => '2 — Developing', 3 => '3 — Meets expectations', 4 => '4 — Exceeds expectations', 5 => '5 — Outstanding'] as $rating => $ratingLabel): ?>
                                <option value="<?= $rating ?>"<?= $lifecycleValue('rating') === (string) $rating ? ' selected' : '' ?>><?= dashboardEscape($ratingLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($lifecycleError('rating') !== ''): ?><p class="admin-field-error" id="teacher-lifecycle-rating-error"><?= dashboardEscape($lifecycleError('rating')) ?></p><?php endif; ?>
                    </div>
                    <div class="admin-field admin-field--wide">
                        <label for="teacher-lifecycle-strengths">Strengths</label>
                        <textarea id="teacher-lifecycle-strengths" name="strengths" rows="3"><?= dashboardEscape($lifecycleValue('strengths')) ?></textarea>
                    </div>
                    <div class="admin-field admin-field--wide">
                        <label for="teacher-lifecycle-improvement_areas">Growth areas</label>
                        <textarea id="teacher-lifecycle-improvement_areas" name="improvement_areas" rows="3"><?= dashboardEscape($lifecycleValue('improvement_areas')) ?></textarea>
                    </div>
                    <div class="admin-field admin-field--wide">
                        <label for="teacher-lifecycle-notes">Additional notes</label>
                        <textarea id="teacher-lifecycle-notes" name="notes" rows="3"><?= dashboardEscape($lifecycleValue('notes')) ?></textarea>
                    </div>
                </div>
                <div class="admin-form-actions"><button class="admin-button" type="submit">Save review</button></div>
            </form>
        </section>
        <section class="admin-panel" aria-labelledby="teacher-review-history-heading">
            <h2 id="teacher-review-history-heading">Performance history</h2>
            <?php if ($reviewRows === []): ?>
                <p class="admin-empty">No performance reviews have been recorded.</p>
            <?php else: ?>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Teacher performance history; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Teacher reviews, ordered by review date</caption>
                        <thead><tr><th scope="col">Teacher</th><th scope="col">Date and period</th><th scope="col">Rating</th><th scope="col">Strengths</th><th scope="col">Growth areas</th><th scope="col">Reviewer</th></tr></thead>
                        <tbody>
                            <?php foreach ($reviewRows as $review): ?>
                                <tr>
                                    <th scope="row"><?= dashboardEscape($review['employee_number'] . ' — ' . $review['first_name'] . ' ' . $review['last_name']) ?></th>
                                    <td><?= dashboardEscape($review['review_date']) ?><br><?= dashboardEscape($review['review_period']) ?></td>
                                    <td><?= dashboardEscape((int) $review['rating']) ?> / 5</td>
                                    <td><?= nl2br(dashboardEscape((string) ($review['strengths'] ?? '—'))) ?></td>
                                    <td><?= nl2br(dashboardEscape((string) ($review['improvement_areas'] ?? '—'))) ?></td>
                                    <td><?= dashboardEscape($review['reviewer_first_name'] . ' ' . $review['reviewer_last_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php elseif ($view === 'leave'): ?>
        <section class="admin-panel" aria-labelledby="teacher-leave-requests-heading">
            <h2 id="teacher-leave-requests-heading">Teacher leave requests</h2>
            <p class="admin-panel-description">Teachers submit requests from their dashboard. Approvals set the employment status to On Leave during the approved dates.</p>
            <?php if ($leaveRows === []): ?>
                <p class="admin-empty">No teacher leave requests have been submitted.</p>
            <?php else: ?>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Teacher leave requests; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Leave requests and Admin decisions</caption>
                        <thead><tr><th scope="col">Teacher</th><th scope="col">Leave period</th><th scope="col">Reason</th><th scope="col">Status and review</th><th scope="col">Admin decision</th></tr></thead>
                        <tbody>
                            <?php foreach ($leaveRows as $leave): ?>
                                <?php $leaveId = (int) $leave['leave_request_id']; ?>
                                <tr>
                                    <th scope="row"><?= dashboardEscape($leave['employee_number'] . ' — ' . $leave['first_name'] . ' ' . $leave['last_name']) ?></th>
                                    <td><?= dashboardEscape($leave['leave_type']) ?><br><?= dashboardEscape($leave['start_date']) ?> – <?= dashboardEscape($leave['end_date']) ?></td>
                                    <td><?= nl2br(dashboardEscape($leave['reason'])) ?></td>
                                    <td>
                                        <?= dashboardEscape($leave['request_status']) ?>
                                        <?php if ($leave['review_note'] !== null && $leave['review_note'] !== ''): ?>
                                            <br><?= nl2br(dashboardEscape($leave['review_note'])) ?>
                                        <?php endif; ?>
                                        <?php if ($leave['reviewer_first_name'] !== null): ?>
                                            <br><span class="schedule-muted">Reviewed by <?= dashboardEscape($leave['reviewer_first_name'] . ' ' . $leave['reviewer_last_name']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($leave['request_status'] === 'Pending'): ?>
                                            <form class="admin-form" method="post" action="admin-teacher-lifecycle.php?view=leave">
                                                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                <input type="hidden" name="view" value="leave">
                                                <input type="hidden" name="action" value="review_leave">
                                                <input type="hidden" name="leave_request_id" value="<?= dashboardEscape($leaveId) ?>">
                                                <label for="leave-decision-<?= dashboardEscape($leaveId) ?>">Decision</label>
                                                <select id="leave-decision-<?= dashboardEscape($leaveId) ?>" name="decision" required>
                                                    <option value="Approved">Approve</option>
                                                    <option value="Declined">Decline</option>
                                                </select>
                                                <label for="leave-note-<?= dashboardEscape($leaveId) ?>">Review note</label>
                                                <textarea id="leave-note-<?= dashboardEscape($leaveId) ?>" name="review_note" rows="2"></textarea>
                                                <button class="admin-button" type="submit">Save decision</button>
                                            </form>
                                        <?php else: ?>
                                            <span>No action available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php elseif ($view === 'resignations'): ?>
        <section
            class="admin-panel"
            aria-labelledby="teacher-resignation-form-heading"
            data-form-modal
            data-modal-trigger-label="Schedule resignation"
            data-modal-trigger-class="admin-button"
            data-modal-open-on-load="<?= $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'schedule_resignation' && ($errorMessage !== '' || $fieldErrors !== []) ? 'true' : 'false' ?>"
        >
            <h2 id="teacher-resignation-form-heading">Schedule a resignation</h2>
            <p class="admin-panel-description">On the effective date, linked account access is disabled and assignments for academic years still in progress or yet to begin are marked Ended. Records remain available as history.</p>
            <form class="admin-form" method="post" action="admin-teacher-lifecycle.php?view=resignations">
                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                <input type="hidden" name="view" value="resignations">
                <input type="hidden" name="action" value="schedule_resignation">
                <p class="admin-field-required-note"><span class="admin-required-marker">Required</span> fields must be completed.</p>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="teacher-resignation-teacher">Teacher <span class="admin-required-marker">Required</span></label>
                        <select id="teacher-resignation-teacher" name="teacher_id" required<?= $lifecycleError('teacher_id') !== '' ? ' aria-invalid="true" aria-describedby="teacher-resignation-teacher-error"' : '' ?>>
                            <option value="">Select a teacher</option>
                            <?php foreach ($eligibleTeachers as $teacher): ?>
                                <option value="<?= dashboardEscape((int) $teacher['teacher_id']) ?>"<?= $lifecycleValue('teacher_id') === (string) $teacher['teacher_id'] ? ' selected' : '' ?>>
                                    <?= dashboardEscape($teacher['employee_number'] . ' — ' . $teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['employment_status'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($lifecycleError('teacher_id') !== ''): ?><p class="admin-field-error" id="teacher-resignation-teacher-error"><?= dashboardEscape($lifecycleError('teacher_id')) ?></p><?php endif; ?>
                    </div>
                    <?php $renderInput('effective_date', 'Effective date', 'date', true, $lifecycleValue('effective_date', $today)); ?>
                    <div class="admin-field admin-field--wide">
                        <label for="teacher-resignation-reason">Reason or notes</label>
                        <textarea id="teacher-resignation-reason" name="reason" rows="3"><?= dashboardEscape($lifecycleValue('reason')) ?></textarea>
                    </div>
                </div>
                <div class="admin-form-actions"><button class="admin-button" type="submit">Schedule resignation</button></div>
            </form>
        </section>
        <section class="admin-panel" aria-labelledby="teacher-resignation-history-heading">
            <h2 id="teacher-resignation-history-heading">Resignation history</h2>
            <?php if ($resignationRows === []): ?>
                <p class="admin-empty">No resignation records have been created.</p>
            <?php else: ?>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Resignation history; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Scheduled, completed, and cancelled resignations</caption>
                        <thead><tr><th scope="col">Teacher</th><th scope="col">Effective date</th><th scope="col">Notes</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($resignationRows as $resignation): ?>
                                <tr>
                                    <th scope="row"><?= dashboardEscape($resignation['employee_number'] . ' — ' . $resignation['first_name'] . ' ' . $resignation['last_name']) ?></th>
                                    <td><?= dashboardEscape($resignation['effective_date']) ?></td>
                                    <td><?= nl2br(dashboardEscape((string) ($resignation['reason'] ?? '—'))) ?></td>
                                    <td><?= dashboardEscape($resignation['resignation_status']) ?></td>
                                    <td>
                                        <?php if ($resignation['resignation_status'] === 'Scheduled' && $resignation['effective_date'] > $today): ?>
                                            <form method="post" action="admin-teacher-lifecycle.php?view=resignations">
                                                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                <input type="hidden" name="view" value="resignations">
                                                <input type="hidden" name="action" value="cancel_resignation">
                                                <input type="hidden" name="resignation_id" value="<?= dashboardEscape((int) $resignation['resignation_id']) ?>">
                                                <button class="admin-button admin-button--secondary" type="submit">Cancel scheduled resignation</button>
                                            </form>
                                        <?php else: ?>
                                            <span>No action available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
