<?php

declare(strict_types=1);

$leaveValue = static fn (string $key, string $fallback = ''): string => is_string($oldInput[$key] ?? null)
    ? $oldInput[$key]
    : $fallback;
$leaveError = static fn (string $key): string => is_string($fieldErrors[$key] ?? null)
    ? $fieldErrors[$key]
    : '';
?>
<?php if (is_string($flashMessage) && $flashMessage !== ''): ?>
    <p class="teacher-notice teacher-notice--success" role="status" tabindex="-1" data-status-summary><?= dashboardEscape($flashMessage) ?></p>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <p class="teacher-notice teacher-notice--error" role="alert" tabindex="-1" data-error-summary><?= dashboardEscape($errorMessage) ?></p>
<?php endif; ?>

<div class="teacher-section">
    <?php if ($teacher === false): ?>
        <section class="teacher-panel">
            <p class="teacher-notice teacher-notice--error" role="alert">A teacher profile is not linked to your account. Contact the school administrator.</p>
        </section>
    <?php else: ?>
        <section class="teacher-panel" aria-labelledby="teacher-leave-form-heading">
            <h2 id="teacher-leave-form-heading">Submit a leave request</h2>
            <p class="teacher-panel-description">Your request is sent to Admin for approval. Submitting a request does not mean the leave has been approved.</p>
            <form class="teacher-form" method="post" action="teacher-leave.php">
                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                <input type="hidden" name="action" value="submit_leave">
                <p class="teacher-field-note"><span class="teacher-required-marker">Required</span> fields must be completed.</p>
                <div class="teacher-form-grid">
                    <div class="teacher-field">
                        <label for="teacher-leave-type">Leave type <span class="teacher-required-marker">Required</span></label>
                        <select id="teacher-leave-type" name="leave_type" required<?= $leaveError('leave_type') !== '' ? ' aria-invalid="true" aria-describedby="teacher-leave-type-error"' : '' ?>>
                            <option value="">Select a leave type</option>
                            <?php foreach (['Sick', 'Personal', 'Vacation', 'Other'] as $leaveType): ?>
                                <option value="<?= dashboardEscape($leaveType) ?>"<?= $leaveValue('leave_type') === $leaveType ? ' selected' : '' ?>><?= dashboardEscape($leaveType) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($leaveError('leave_type') !== ''): ?><p class="teacher-field-error" id="teacher-leave-type-error"><?= dashboardEscape($leaveError('leave_type')) ?></p><?php endif; ?>
                    </div>
                    <div class="teacher-field">
                        <label for="teacher-leave-start">Start date <span class="teacher-required-marker">Required</span></label>
                        <input id="teacher-leave-start" name="start_date" type="date" min="<?= dashboardEscape(date('Y-m-d')) ?>" value="<?= dashboardEscape($leaveValue('start_date')) ?>" required<?= $leaveError('start_date') !== '' ? ' aria-invalid="true" aria-describedby="teacher-leave-start-error"' : '' ?>>
                        <?php if ($leaveError('start_date') !== ''): ?><p class="teacher-field-error" id="teacher-leave-start-error"><?= dashboardEscape($leaveError('start_date')) ?></p><?php endif; ?>
                    </div>
                    <div class="teacher-field">
                        <label for="teacher-leave-end">End date <span class="teacher-required-marker">Required</span></label>
                        <input id="teacher-leave-end" name="end_date" type="date" min="<?= dashboardEscape(date('Y-m-d')) ?>" value="<?= dashboardEscape($leaveValue('end_date')) ?>" required<?= $leaveError('end_date') !== '' ? ' aria-invalid="true" aria-describedby="teacher-leave-end-error"' : '' ?>>
                        <?php if ($leaveError('end_date') !== ''): ?><p class="teacher-field-error" id="teacher-leave-end-error"><?= dashboardEscape($leaveError('end_date')) ?></p><?php endif; ?>
                    </div>
                    <div class="teacher-field teacher-field--wide">
                        <label for="teacher-leave-reason">Reason <span class="teacher-required-marker">Required</span></label>
                        <textarea id="teacher-leave-reason" name="reason" rows="3" maxlength="4000" required<?= $leaveError('reason') !== '' ? ' aria-invalid="true" aria-describedby="teacher-leave-reason-error"' : '' ?>><?= dashboardEscape($leaveValue('reason')) ?></textarea>
                        <?php if ($leaveError('reason') !== ''): ?><p class="teacher-field-error" id="teacher-leave-reason-error"><?= dashboardEscape($leaveError('reason')) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="teacher-form-actions"><button class="teacher-button" type="submit">Send request to Admin</button></div>
            </form>
        </section>

        <section class="teacher-panel" aria-labelledby="teacher-leave-history-heading">
            <h2 id="teacher-leave-history-heading">My leave history</h2>
            <?php if ($leaveRows === []): ?>
                <p class="teacher-empty">You have not submitted a leave request.</p>
            <?php else: ?>
                <div class="teacher-table-scroll" role="group" tabindex="0" aria-label="My leave request history; scroll horizontally to view all columns">
                    <table class="teacher-table">
                        <caption>Your leave requests and Admin decisions</caption>
                        <thead><tr><th scope="col">Type</th><th scope="col">Dates</th><th scope="col">Reason</th><th scope="col">Status and review</th><th scope="col">Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($leaveRows as $leave): ?>
                                <tr>
                                    <th scope="row"><?= dashboardEscape($leave['leave_type']) ?></th>
                                    <td><?= dashboardEscape($leave['start_date']) ?> – <?= dashboardEscape($leave['end_date']) ?></td>
                                    <td><?= nl2br(dashboardEscape($leave['reason'])) ?></td>
                                    <td>
                                        <?= dashboardEscape($leave['request_status']) ?>
                                        <?php if ($leave['review_note'] !== null && $leave['review_note'] !== ''): ?>
                                            <br><?= nl2br(dashboardEscape($leave['review_note'])) ?>
                                        <?php endif; ?>
                                        <?php if ($leave['reviewed_at'] !== null): ?>
                                            <br><time datetime="<?= dashboardEscape(date('c', strtotime($leave['reviewed_at']))) ?>"><?= dashboardEscape($leave['reviewed_at']) ?></time>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($leave['request_status'] === 'Pending'): ?>
                                            <form method="post" action="teacher-leave.php">
                                                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                <input type="hidden" name="action" value="cancel_leave">
                                                <input type="hidden" name="leave_request_id" value="<?= dashboardEscape((int) $leave['leave_request_id']) ?>">
                                                <button class="teacher-button teacher-button--secondary" type="submit">Cancel request</button>
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
