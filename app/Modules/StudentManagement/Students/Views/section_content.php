<?php

declare(strict_types=1);

?>
<div class="student-section">
    <?php if ($studentProfile === null && $sectionKey !== 'announcements'): ?>
        <p class="student-notice" role="status">
            A student profile is not linked to this account yet. Contact the school administrator if you believe this is an error.
        </p>
    <?php endif; ?>

    <?php if ($sectionKey === 'profile' && $profileFields !== []): ?>
        <section class="student-panel" aria-labelledby="student-profile-heading">
            <div class="student-profile-heading-row">
                <h2 id="student-profile-heading">Student details</h2>
                <?php if (!$profileEditMode): ?>
                    <a class="admin-button" href="student-section.php?section=profile&amp;edit=1">Edit profile</a>
                <?php endif; ?>
            </div>

            <?php if ($profileNotice !== ''): ?>
                <p class="student-notice" role="status"><?= dashboardEscape($profileNotice) ?></p>
            <?php endif; ?>

            <?php if ($profileEditMode): ?>
                <?php if ($profileError !== '' || $profileFieldErrors !== []): ?>
                    <div class="student-notice student-notice--error" id="student-profile-error" role="alert" tabindex="-1" data-error-summary>
                        <?php if ($profileError !== ''): ?>
                            <p><?= dashboardEscape($profileError) ?></p>
                        <?php else: ?>
                            <p>Review and correct the following fields:</p>
                            <?php
                            $profileFieldLabels = [
                                'phone' => 'Phone number',
                                'city' => 'City or municipality',
                                'province' => 'Province',
                                'address' => 'Residential address',
                            ];
                            ?>
                            <ul>
                                <?php foreach ($profileFieldErrors as $fieldName => $message): ?>
                                    <li>
                                        <a href="#student-profile-<?= dashboardEscape($fieldName) ?>">
                                            <?= dashboardEscape($profileFieldLabels[$fieldName]) ?>: <?= dashboardEscape($message) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <p class="student-profile-help">
                    Update your phone and residential address. For changes to your name, date of birth, or email, contact the school office.
                </p>
                <form class="student-profile-form" method="post" action="student-section.php?section=profile&amp;edit=1">
                    <input type="hidden" name="csrf_token" value="<?= dashboardEscape(appCsrfToken()) ?>">
                    <input type="hidden" name="action" value="update-profile">
                    <div class="admin-form-grid">
                        <div class="student-payment-field">
                            <label for="student-profile-phone">Phone number</label>
                            <input id="student-profile-phone" name="phone" type="tel" autocomplete="tel" maxlength="30"
                                   value="<?= dashboardEscape($profileValues['phone']) ?>"
                                   <?= isset($profileFieldErrors['phone']) ? 'aria-invalid="true" aria-describedby="student-profile-phone-error"' : '' ?>>
                            <?php if (isset($profileFieldErrors['phone'])): ?>
                                <span class="admin-field-error" id="student-profile-phone-error"><?= dashboardEscape($profileFieldErrors['phone']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="student-payment-field">
                            <label for="student-profile-city">City or municipality</label>
                            <input id="student-profile-city" name="city" type="text" autocomplete="address-level2" maxlength="100"
                                   value="<?= dashboardEscape($profileValues['city']) ?>"
                                   <?= isset($profileFieldErrors['city']) ? 'aria-invalid="true" aria-describedby="student-profile-city-error"' : '' ?>>
                            <?php if (isset($profileFieldErrors['city'])): ?>
                                <span class="admin-field-error" id="student-profile-city-error"><?= dashboardEscape($profileFieldErrors['city']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="student-payment-field">
                            <label for="student-profile-province">Province</label>
                            <input id="student-profile-province" name="province" type="text" autocomplete="address-level1" maxlength="100"
                                   value="<?= dashboardEscape($profileValues['province']) ?>"
                                   <?= isset($profileFieldErrors['province']) ? 'aria-invalid="true" aria-describedby="student-profile-province-error"' : '' ?>>
                            <?php if (isset($profileFieldErrors['province'])): ?>
                                <span class="admin-field-error" id="student-profile-province-error"><?= dashboardEscape($profileFieldErrors['province']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="student-payment-field student-profile-address">
                            <label for="student-profile-address">Residential address</label>
                            <textarea id="student-profile-address" name="address" autocomplete="street-address" maxlength="2000"
                                      <?= isset($profileFieldErrors['address']) ? 'aria-invalid="true" aria-describedby="student-profile-address-error"' : '' ?>><?= dashboardEscape($profileValues['address']) ?></textarea>
                            <?php if (isset($profileFieldErrors['address'])): ?>
                                <span class="admin-field-error" id="student-profile-address-error"><?= dashboardEscape($profileFieldErrors['address']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="admin-form-actions">
                        <button class="admin-button" type="submit">Save changes</button>
                        <a class="admin-button admin-button--secondary" href="student-section.php?section=profile">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <dl class="student-profile-list">
                    <?php foreach ($profileFields as $field): ?>
                        <div>
                            <dt><?= dashboardEscape($field['label']) ?></dt>
                            <dd><?= dashboardEscape($field['value'] !== '' ? $field['value'] : '—') ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </section>
    <?php elseif ($sectionKey === 'profile'): ?>
        <section class="student-panel" aria-labelledby="student-profile-heading">
            <h2 id="student-profile-heading">Student details</h2>
            <p class="student-empty">No student profile is linked to this account.</p>
        </section>
    <?php endif; ?>

    <?php if (in_array($sectionKey, ['payments', 'payment', 'financial-report'], true)): ?>
        <?php if ($sectionKey === 'payments'): ?>
            <section class="student-finance-summary" aria-label="Current academic year fee summary">
                <article class="student-finance-stat">
                    <span>Total assessed</span>
                    <strong>₱<?= dashboardEscape(number_format($financeTotals['billed_cents'] / 100, 2)) ?></strong>
                </article>
                <article class="student-finance-stat">
                    <span>Total paid</span>
                    <strong>₱<?= dashboardEscape(number_format($financeTotals['paid_cents'] / 100, 2)) ?></strong>
                </article>
                <article class="student-finance-stat">
                    <span>Remaining balance</span>
                    <strong>₱<?= dashboardEscape(number_format($financeTotals['outstanding_cents'] / 100, 2)) ?></strong>
                </article>
                <article class="student-finance-stat">
                    <span>Paid progress</span>
                    <strong><?= dashboardEscape($financeTotals['progress']) ?>%</strong>
                </article>
            </section>
            <div class="student-finance-progress" role="progressbar"
                 aria-label="Current academic year fees paid"
                 aria-valuemin="0" aria-valuemax="100"
                 aria-valuenow="<?= dashboardEscape($financeTotals['progress']) ?>">
                <span style="width: <?= dashboardEscape($financeTotals['progress']) ?>%"></span>
            </div>
            <section class="student-panel" aria-labelledby="student-term-balance-heading">
                <h2 id="student-term-balance-heading">Tuition by term</h2>
                <p class="student-payment-help">Your net tuition after discounts is divided across Prelim, Midterm, and Final. Amounts below show tuition only; other assessed fees are billed once for the academic year. Only completed payments reduce your balance.</p>
                <div class="student-term-grid">
                    <?php foreach ($termBalances as $termName => $termBalance): ?>
                        <?php $termOutstanding = max(0, $termBalance['billed_cents'] - $termBalance['paid_cents']); ?>
                        <article class="student-term-card">
                            <h3><?= dashboardEscape($termName) ?></h3>
                            <p><span>Tuition assessed</span><span>₱<?= dashboardEscape(number_format($termBalance['billed_cents'] / 100, 2)) ?></span></p>
                            <p><span>Tuition paid</span><span>₱<?= dashboardEscape(number_format($termBalance['paid_cents'] / 100, 2)) ?></span></p>
                            <p><span>Tuition to pay</span><strong>₱<?= dashboardEscape(number_format($termOutstanding / 100, 2)) ?></strong></p>
                            <span class="student-status<?= $termOutstanding === 0 && $termBalance['billed_cents'] > 0 ? ' student-status--success' : ($termBalance['paid_cents'] > 0 ? ' student-status--attention' : '') ?>">
                                <?= dashboardEscape($termBalance['billed_cents'] === 0 ? 'No tuition fee' : ($termOutstanding === 0 ? 'Paid' : ($termBalance['paid_cents'] > 0 ? 'Partially paid' : 'Unpaid'))) ?>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="student-annual-fee-summary">
                    <dl>
                        <div>
                            <dt>Other annual fees to pay</dt>
                            <dd>₱<?= dashboardEscape(number_format($annualFeeBalances['outstanding_cents'] / 100, 2)) ?></dd>
                        </div>
                        <div>
                            <dt>Annual fees assessed</dt>
                            <dd>₱<?= dashboardEscape(number_format($annualFeeBalances['billed_cents'] / 100, 2)) ?></dd>
                        </div>
                        <div>
                            <dt>Annual fees paid</dt>
                            <dd>₱<?= dashboardEscape(number_format($annualFeeBalances['paid_cents'] / 100, 2)) ?></dd>
                        </div>
                    </dl>
                    <p>
                        <?php if ($annualFeeBalances['fee_count'] > 0): ?>
                            These <?= dashboardEscape($annualFeeBalances['fee_count']) ?> non-tuition fees are billed once for the academic year, not split across terms.
                        <?php else: ?>
                            No non-tuition fees have been assessed for this academic year.
                        <?php endif; ?>
                        <a href="#student-fee-ledger-heading">View itemized fee balances</a>
                    </p>
                </div>
            </section>
            <section class="student-panel" aria-labelledby="student-fee-ledger-heading">
                <h2 id="student-fee-ledger-heading" tabindex="-1">Fee balances</h2>
                <?php if ($feeRows === []): ?>
                    <p class="student-empty">No fees are recorded for your active academic year yet. Contact the school office if you expected a fee assessment.</p>
                <?php else: ?>
                    <div class="student-table-scroll" tabindex="0" role="region" aria-label="Fee balance table; scroll horizontally to view all columns">
                        <table class="student-table">
                            <caption>Active academic year fees. Pending payment requests are not counted as paid.</caption>
                            <thead><tr>
                                <th scope="col">Fee</th><th scope="col">Assessment</th><th scope="col">Discount</th>
                                <th scope="col">Paid</th><th scope="col">Balance</th><th scope="col">Due date</th><th scope="col">Status</th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($feeRows as $fee): ?>
                                    <tr>
                                        <th scope="row"><?= dashboardEscape($fee['fee_name']) ?><span class="student-table-detail"><?= dashboardEscape($fee['fee_category']) ?></span></th>
                                        <td>₱<?= dashboardEscape(number_format((int) $fee['amount_cents'] / 100, 2)) ?></td>
                                        <td>₱<?= dashboardEscape(number_format((int) $fee['discount_cents'] / 100, 2)) ?></td>
                                        <td>₱<?= dashboardEscape(number_format((int) $fee['paid_cents'] / 100, 2)) ?></td>
                                        <td>₱<?= dashboardEscape(number_format((int) $fee['outstanding_cents'] / 100, 2)) ?></td>
                                        <td><?= dashboardEscape($fee['due_date'] ?? '—') ?></td>
                                        <td><?= dashboardEscape($fee['display_status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <?php if ($unlinkedPaymentTotals['count'] > 0): ?>
                    <p class="student-notice"><?= dashboardEscape($unlinkedPaymentTotals['count']) ?> completed payment(s), totaling ₱<?= dashboardEscape(number_format($unlinkedPaymentTotals['amount_cents'] / 100, 2)) ?>, are not linked to a specific fee and are excluded from fee balances. Contact the school office to have them reviewed.</p>
                <?php endif; ?>
            </section>
        <?php elseif ($sectionKey === 'payment'): ?>
            <?php if ($paymentFlash !== ''): ?><p class="student-notice" role="status" tabindex="-1" data-status-summary><?= dashboardEscape($paymentFlash) ?></p><?php endif; ?>
            <?php if ($paymentError !== '' || $paymentFieldErrors !== []): ?>
                <?php
                $paymentFieldLabels = [
                    'fee_id' => 'Fee',
                    'payment_term' => 'Tuition term',
                    'total_amount' => 'Amount',
                    'payment_method' => 'Payment method',
                    'reference_number' => 'Reference number',
                ];
                $paymentFieldIds = [
                    'fee_id' => 'student-payment-fee',
                    'payment_term' => 'student-payment-term',
                    'total_amount' => 'student-payment-amount',
                    'payment_method' => 'student-payment-method',
                    'reference_number' => 'student-payment-reference',
                ];
                ?>
                <div class="student-notice student-notice--error" role="alert" tabindex="-1" data-error-summary>
                    <strong>We couldn't submit this payment request.</strong>
                    <?php if ($paymentFieldErrors !== []): ?>
                        <p>Review the following <?= count($paymentFieldErrors) === 1 ? 'field' : 'fields' ?>:</p>
                        <ul>
                            <?php foreach ($paymentFieldErrors as $fieldName => $fieldError): ?>
                                <li><a href="#<?= dashboardEscape($paymentFieldIds[$fieldName]) ?>"><?= dashboardEscape($paymentFieldLabels[$fieldName]) ?>: <?= dashboardEscape($fieldError) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ($paymentError !== '' && !in_array($paymentError, $paymentFieldErrors, true)): ?>
                        <p><?= dashboardEscape($paymentError) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <section class="student-panel" aria-labelledby="student-payment-request-heading"<?= $feeRows !== [] ? ' data-form-modal data-modal-trigger-label="Submit a payment request" data-modal-trigger-class="admin-button" data-modal-open-on-load="' . ($paymentError !== '' || $paymentFieldErrors !== [] ? 'true' : 'false') . '"' : '' ?>>
                <h2 id="student-payment-request-heading">Submit a payment for verification</h2>
                <p class="student-payment-help">Pay using one of the methods listed below, then submit the amount and reference number. Your request remains Pending until the school verifies it. A submitted request is not a receipt or confirmation of completed payment.</p>
                <p class="student-payment-help">Fields marked “required” must be completed. Tuition term is required only for tuition fees; leave it blank for other fee categories.</p>
                <?php if ($feeRows === []): ?>
                    <p class="student-empty">There are no current-year fees available for a payment request.</p>
                <?php else: ?>
                    <form class="student-payment-form" method="post" action="student-section.php?section=payment">
                        <input type="hidden" name="csrf_token" value="<?= dashboardEscape(appCsrfToken()) ?>">
                        <input type="hidden" name="action" value="submit-payment">
                        <div class="student-payment-fields">
                            <div class="student-payment-field student-payment-field--wide">
                                <label for="student-payment-fee">Fee (required)</label>
                                <select id="student-payment-fee" name="fee_id" required
                                    <?= isset($paymentFieldErrors['fee_id']) ? 'aria-invalid="true" aria-describedby="student-payment-fee-error"' : '' ?>>
                                    <option value="">Select a fee</option>
                                    <?php foreach ($feeRows as $fee): ?>
                                        <?php if ($fee['display_status'] !== 'Waived' && (int) $fee['outstanding_cents'] > 0): ?>
                                            <option value="<?= dashboardEscape((int) $fee['student_fee_id']) ?>" data-category="<?= dashboardEscape($fee['fee_category']) ?>"<?= $paymentValues['fee_id'] === (string) $fee['student_fee_id'] ? ' selected' : '' ?>>
                                                <?= dashboardEscape($fee['fee_name'] . ' (' . $fee['fee_category'] . ') - balance ₱' . number_format((int) $fee['outstanding_cents'] / 100, 2)) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($paymentFieldErrors['fee_id'])): ?><span class="admin-field-error" id="student-payment-fee-error"><?= dashboardEscape($paymentFieldErrors['fee_id']) ?></span><?php endif; ?>
                            </div>
                            <div class="student-payment-field">
                                <label for="student-payment-term">Tuition term (required for tuition)</label>
                                <select id="student-payment-term" name="payment_term"
                                    <?= isset($paymentFieldErrors['payment_term']) ? 'aria-invalid="true" aria-describedby="student-payment-term-error"' : '' ?>>
                                    <option value="">Select for tuition; leave blank for other fees</option>
                                    <?php foreach ($paymentTerms as $termName): ?>
                                        <option value="<?= dashboardEscape($termName) ?>"<?= $paymentValues['payment_term'] === $termName ? ' selected' : '' ?>><?= dashboardEscape($termName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($paymentFieldErrors['payment_term'])): ?><span class="admin-field-error" id="student-payment-term-error"><?= dashboardEscape($paymentFieldErrors['payment_term']) ?></span><?php endif; ?>
                            </div>
                            <div class="student-payment-field">
                                <label for="student-payment-amount">Amount (₱, required)</label>
                                <input id="student-payment-amount" name="total_amount" type="number" min="0.01" step="0.01" inputmode="decimal" autocomplete="off" required
                                    value="<?= dashboardEscape($paymentValues['total_amount']) ?>"
                                    <?= isset($paymentFieldErrors['total_amount']) ? 'aria-invalid="true" aria-describedby="student-payment-amount-error"' : '' ?>>
                                <?php if (isset($paymentFieldErrors['total_amount'])): ?><span class="admin-field-error" id="student-payment-amount-error"><?= dashboardEscape($paymentFieldErrors['total_amount']) ?></span><?php endif; ?>
                            </div>
                            <div class="student-payment-field">
                                <label for="student-payment-method">Payment method (required)</label>
                                <select id="student-payment-method" name="payment_method" required
                                    <?= isset($paymentFieldErrors['payment_method']) ? 'aria-invalid="true" aria-describedby="student-payment-method-error"' : '' ?>>
                                    <option value="">Select a payment method</option>
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <option value="<?= dashboardEscape($method) ?>"<?= $paymentValues['payment_method'] === $method ? ' selected' : '' ?>><?= dashboardEscape($method) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($paymentFieldErrors['payment_method'])): ?><span class="admin-field-error" id="student-payment-method-error"><?= dashboardEscape($paymentFieldErrors['payment_method']) ?></span><?php endif; ?>
                            </div>
                            <div class="student-payment-field">
                                <label for="student-payment-reference">Reference number (if available)</label>
                                <input id="student-payment-reference" name="reference_number" maxlength="150"
                                    value="<?= dashboardEscape($paymentValues['reference_number']) ?>"
                                    <?= isset($paymentFieldErrors['reference_number']) ? 'aria-invalid="true" aria-describedby="student-payment-reference-error"' : '' ?>>
                                <?php if (isset($paymentFieldErrors['reference_number'])): ?><span class="admin-field-error" id="student-payment-reference-error"><?= dashboardEscape($paymentFieldErrors['reference_number']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <p class="student-payment-help">Available methods: Cash, Bank Transfer, GCash, Maya, Card, or Other. Online processing is not provided here.</p>
                        <div class="admin-form-actions"><button class="admin-button" type="submit">Submit for verification</button></div>
                    </form>
                <?php endif; ?>
            </section>
            <section class="student-panel" aria-labelledby="student-payment-history-heading">
                <h2 id="student-payment-history-heading">Payment history</h2>
                <?php if ($paymentHistory === []): ?>
                    <p class="student-empty">No payment records are linked to your profile yet.</p>
                <?php else: ?>
                    <div class="student-table-scroll" tabindex="0" role="region" aria-label="Payment history; scroll horizontally to view all columns">
                        <table class="student-table">
                            <caption>Latest 100 payments across your enrollments. Pending requests do not reduce fee balances.</caption>
                            <thead><tr><th scope="col">Date</th><th scope="col">Receipt</th><th scope="col">Fee / term</th><th scope="col">Amount</th><th scope="col">Method</th><th scope="col">Reference</th><th scope="col">Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($paymentHistory as $payment): ?>
                                    <tr>
                                        <td><?= dashboardEscape($payment['payment_date']) ?></td>
                                        <th scope="row"><?= dashboardEscape($payment['receipt_number']) ?></th>
                                        <td><?= dashboardEscape(($payment['fee_name'] ?? 'Unallocated payment') . ($payment['payment_term'] !== null ? ' / ' . $payment['payment_term'] : '')) ?></td>
                                        <td>₱<?= dashboardEscape(number_format((float) $payment['total_amount'], 2)) ?></td>
                                        <td><?= dashboardEscape($payment['payment_method']) ?></td>
                                        <td><?= dashboardEscape($payment['reference_number'] ?: '—') ?></td>
                                        <td><?= dashboardEscape($payment['payment_status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($sectionKey === 'financial-report'): ?>
            <div class="student-report-actions" data-print-hide>
                <button class="admin-button" type="button" data-print-report>Print or save as PDF</button>
            </div>
            <header class="student-report-heading">
                <h2>Financial report</h2>
                <p><?= dashboardEscape(trim(implode(' ', array_filter([
                    $studentProfile['first_name'] ?? '',
                    $studentProfile['middle_name'] ?? '',
                    $studentProfile['last_name'] ?? '',
                ], static fn (mixed $value): bool => is_string($value) && $value !== ''))) ?: $user['first_name'] . ' ' . $user['last_name']) ?></p>
                <p>Student number: <?= dashboardEscape($studentProfile['student_number'] ?? '—') ?></p>
                <p>Academic year: <?= dashboardEscape($activeAcademicYear['year_name'] ?? '—') ?> · Generated <?= dashboardEscape(date('Y-m-d H:i')) ?></p>
            </header>
            <section class="student-panel" aria-labelledby="student-report-summary-heading">
                <h2 id="student-report-summary-heading">Financial summary<?= $activeAcademicYear !== null ? ' — ' . dashboardEscape($activeAcademicYear['year_name']) : '' ?></h2>
                <p class="student-payment-help">Completed payments only are counted as paid. Pending, failed, refunded, or cancelled requests do not reduce the balance.</p>
                <dl class="student-profile-list">
                    <div><dt>Total assessed</dt><dd>₱<?= dashboardEscape(number_format($financeTotals['billed_cents'] / 100, 2)) ?></dd></div>
                    <div><dt>Total completed payments</dt><dd>₱<?= dashboardEscape(number_format($financeTotals['paid_cents'] / 100, 2)) ?></dd></div>
                    <div><dt>Remaining balance</dt><dd>₱<?= dashboardEscape(number_format($financeTotals['outstanding_cents'] / 100, 2)) ?></dd></div>
                    <div><dt>Payment progress</dt><dd><?= dashboardEscape($financeTotals['progress']) ?>%</dd></div>
                </dl>
                <?php if ($unlinkedPaymentTotals['count'] > 0): ?>
                    <p class="student-notice"><?= dashboardEscape($unlinkedPaymentTotals['count']) ?> completed payment(s), totaling ₱<?= dashboardEscape(number_format($unlinkedPaymentTotals['amount_cents'] / 100, 2)) ?>, are not linked to a fee and are excluded from the fee balances above. Ask the school office to review them.</p>
                <?php endif; ?>
            </section>
            <section class="student-panel" aria-labelledby="student-report-fees-heading">
                <h2 id="student-report-fees-heading">Assessed fees and balances</h2>
                <?php if ($feeRows === []): ?>
                    <p class="student-empty">No fees are recorded for your active academic year.</p>
                <?php else: ?>
                    <div class="student-table-scroll" tabindex="0" role="region" aria-label="Financial report fee balances; scroll horizontally to view all columns">
                        <table class="student-table">
                            <caption>Fee balances for your active academic year</caption>
                            <thead><tr><th scope="col">Fee</th><th scope="col">Category</th><th scope="col">Assessment</th><th scope="col">Discount</th><th scope="col">Paid</th><th scope="col">Balance</th><th scope="col">Due</th><th scope="col">Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($feeRows as $fee): ?>
                                    <tr>
                                        <th scope="row"><?= dashboardEscape($fee['fee_name']) ?></th>
                                        <td><?= dashboardEscape($fee['fee_category']) ?></td>
                                        <td>₱<?= dashboardEscape(number_format((int) $fee['amount_cents'] / 100, 2)) ?></td>
                                        <td>₱<?= dashboardEscape(number_format((int) $fee['discount_cents'] / 100, 2)) ?></td>
                                        <td>₱<?= dashboardEscape(number_format((int) $fee['paid_cents'] / 100, 2)) ?></td>
                                        <td>₱<?= dashboardEscape(number_format((int) $fee['outstanding_cents'] / 100, 2)) ?></td>
                                        <td><?= dashboardEscape($fee['due_date'] ?? '—') ?></td>
                                        <td><?= dashboardEscape($fee['display_status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
            <section class="student-panel" aria-labelledby="student-report-payments-heading">
                <h2 id="student-report-payments-heading">Payment history</h2>
                <?php if ($paymentHistory === []): ?>
                    <p class="student-empty">No payment records are linked to your profile yet.</p>
                <?php else: ?>
                    <div class="student-table-scroll" tabindex="0" role="region" aria-label="Financial report payment history; scroll horizontally to view all columns">
                        <table class="student-table">
                            <caption>Latest 100 payments across your enrollments</caption>
                            <thead><tr><th scope="col">Date</th><th scope="col">Receipt</th><th scope="col">Fee / term</th><th scope="col">Amount</th><th scope="col">Method</th><th scope="col">Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($paymentHistory as $payment): ?>
                                    <tr>
                                        <td><?= dashboardEscape($payment['payment_date']) ?></td>
                                        <th scope="row"><?= dashboardEscape($payment['receipt_number']) ?></th>
                                        <td><?= dashboardEscape(($payment['fee_name'] ?? 'Unallocated payment') . ($payment['payment_term'] !== null ? ' / ' . $payment['payment_term'] : '')) ?></td>
                                        <td>₱<?= dashboardEscape(number_format((float) $payment['total_amount'], 2)) ?></td>
                                        <td><?= dashboardEscape($payment['payment_method']) ?></td>
                                        <td><?= dashboardEscape($payment['payment_status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <p class="student-payment-help">Generated <?= dashboardEscape(date('Y-m-d H:i')) ?>. Amounts are shown in Philippine pesos.</p>
            </section>
        <?php endif; ?>
    <?php elseif ($sectionKey === 'notifications'): ?>
        <?php if ($notificationFlash !== ''): ?><p class="student-notice" role="status" tabindex="-1" data-status-summary><?= dashboardEscape($notificationFlash) ?></p><?php endif; ?>
        <?php if ($studentNotifications !== [] && $unreadNotifications > 0): ?>
            <form method="post" action="student-section.php?section=notifications" data-print-hide>
                <input type="hidden" name="csrf_token" value="<?= dashboardEscape(appCsrfToken()) ?>">
                <input type="hidden" name="action" value="mark-all-read">
                <button class="admin-button admin-button--secondary" type="submit">Mark all as read</button>
            </form>
        <?php endif; ?>
        <section class="student-panel" aria-labelledby="student-notifications-heading">
            <h2 id="student-notifications-heading">Your notifications</h2>
            <?php if ($studentNotifications === []): ?>
                <p class="student-empty">You have no current notifications. Fee reminders, payment updates, and posted grades will appear here.</p>
            <?php else: ?>
                <?php foreach ($studentNotifications as $notification): ?>
                    <article class="student-notification<?= (int) $notification['is_read'] === 0 ? ' student-notification--unread' : '' ?>">
                        <div>
                            <h3><?= dashboardEscape($notification['title']) ?></h3>
                            <p><?= dashboardEscape($notification['message']) ?></p>
                            <?php if ($notification['notification_type'] === 'Grade posted'): ?>
                                <a class="student-notification-link" href="student-section.php?section=grades">View My Grades</a>
                            <?php endif; ?>
                            <time datetime="<?= dashboardEscape(date('c', strtotime($notification['created_at']))) ?>"><?= dashboardEscape($notification['created_at']) ?></time>
                        </div>
                        <span class="student-status<?= $notification['notification_type'] === 'Payment status' ? ' student-status--success' : ' student-status--attention' ?>"><?= dashboardEscape($notification['notification_type']) ?></span>
                        <?php if ((int) $notification['is_read'] === 0): ?>
                            <form method="post" action="student-section.php?section=notifications">
                                <input type="hidden" name="csrf_token" value="<?= dashboardEscape(appCsrfToken()) ?>">
                                <input type="hidden" name="action" value="mark-read">
                                <input type="hidden" name="notification_id" value="<?= dashboardEscape((int) $notification['notification_id']) ?>">
                                <button class="admin-button admin-button--secondary" type="submit">Mark as read</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php foreach ($tables as $index => $recordTable): ?>
        <section class="student-panel">
            <h2 id="student-table-heading-<?= dashboardEscape($index) ?>"><?= dashboardEscape($recordTable['title']) ?></h2>
            <?php if ($recordTable['rows'] === []): ?>
                <p class="student-empty"><?= dashboardEscape($recordTable['empty']) ?></p>
            <?php else: ?>
                <div class="student-table-scroll" tabindex="0" role="region" aria-label="Scrollable table: <?= dashboardEscape($recordTable['title']) ?>">
                    <table class="student-table">
                        <caption><?= dashboardEscape($recordTable['caption']) ?></caption>
                        <thead>
                            <tr>
                                <?php foreach ($recordTable['columns'] as $column): ?>
                                    <th scope="col"><?= dashboardEscape($column) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recordTable['rows'] as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td><?= nl2br(dashboardEscape((string) $value)) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
