<?php

declare(strict_types=1);

$sectionError = is_string($fieldErrors['section_id'] ?? null)
    ? $fieldErrors['section_id']
    : '';
?>
<div class="student-section">
    <?php if ($errorMessage !== ''): ?>
        <p class="student-notice student-notice--error" role="alert" tabindex="-1" data-error-summary>
            <?= dashboardEscape($errorMessage) ?>
        </p>
    <?php elseif ($sectionError !== ''): ?>
        <p class="student-notice student-notice--error" role="alert" tabindex="-1" data-error-summary>
            Please correct the enrollment section selection below.
            <a href="#student-section-choice">Review the class section field.</a>
        </p>
    <?php endif; ?>

    <?php if ($studentId === null): ?>
        <section class="student-panel" aria-labelledby="student-enrollment-heading">
            <h2 id="student-enrollment-heading">Student profile required</h2>
            <p class="student-empty">A student profile is not linked to this account. Contact the school administrator to complete your account setup.</p>
        </section>
    <?php else: ?>
        <section class="student-panel" aria-labelledby="student-enrollment-heading">
            <h2 id="student-enrollment-heading">Available class sections</h2>
            <p class="student-panel-description">
                Select an available section for the active academic year. Your enrollment is created immediately when you continue.
            </p>

            <?php if ($sectionChoices === []): ?>
                <p class="student-empty student-enrollment-empty">
                    No enrollment options are available yet. An administrator needs to activate an academic year and class sections before you can continue. Please contact the school office if you need help.
                </p>
            <?php else: ?>
                <form
                    class="student-enrollment-form"
                    method="post"
                    action="student-enrollment.php"
                    aria-labelledby="student-enrollment-heading"
                >
                    <input type="hidden" name="csrf_token" value="<?= dashboardEscape(appCsrfToken()) ?>">
                    <div class="student-enrollment-field">
                        <label for="student-section-choice">
                            Class section
                            <span class="student-required-marker">Required</span>
                        </label>
                        <select
                            id="student-section-choice"
                            name="section_id"
                            required
                            <?= $sectionError !== '' ? 'aria-invalid="true" aria-describedby="student-section-error"' : '' ?>
                            <?= $sectionError !== '' ? 'data-error-focus' : '' ?>
                        >
                            <option value="">Choose an available class section</option>
                            <?php foreach ($sectionChoices as $choice): ?>
                                <?php
                                $programName = is_string($choice['program_name']) && $choice['program_name'] !== ''
                                    ? $choice['program_name']
                                    : 'General program';
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
                                    $programName,
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
                        <?php if ($sectionError !== ''): ?>
                            <span class="student-field-error" id="student-section-error"><?= dashboardEscape($sectionError) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="student-enrollment-note">
                        By continuing, you will be enrolled in the selected section and redirected to your Student dashboard.
                    </p>
                    <button class="student-enrollment-submit" type="submit">Enroll and continue</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
