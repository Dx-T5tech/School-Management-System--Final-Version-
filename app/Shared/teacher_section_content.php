<?php

declare(strict_types=1);

$teacherEscapeValue = static fn (mixed $value): string => dashboardEscape(
    is_string($value) || is_int($value) || is_float($value) ? $value : ''
);
$teacherFieldError = static fn (string $name): string => is_string($fieldErrors[$name] ?? null)
    ? $fieldErrors[$name]
    : '';
$formsForSection = match ($sectionKey) {
    'attendance' => ['attendance'],
    'assessments' => ['assessment'],
    'grades' => ['score', 'term-grade'],
    default => [],
};
?>
<?php if (is_string($flashMessage) && $flashMessage !== ''): ?>
    <p class="teacher-notice teacher-notice--success" role="status" tabindex="-1" data-status-summary><?= dashboardEscape($flashMessage) ?></p>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <p class="teacher-notice teacher-notice--error" role="alert" tabindex="-1" data-error-summary<?= $sectionKey === 'attendance' && $fieldErrors !== [] ? ' data-attendance-field-error-summary' : '' ?>><?= dashboardEscape($errorMessage) ?></p>
<?php endif; ?>

<div class="teacher-section<?= $sectionKey === 'grades' ? ' teacher-section--grade-actions' : '' ?>">
    <?php if ($teacherId === null && $errorMessage === ''): ?>
        <p class="teacher-notice teacher-notice--error" role="alert">A teacher profile is not linked to your account. Contact the school administrator.</p>
    <?php endif; ?>

    <?php foreach ($formsForSection as $formKey): ?>
        <?php $form = $formDefinitions[$formKey]; ?>
        <?php
        $modalTriggerLabel = match ($formKey) {
            'attendance' => 'Record attendance',
            'assessment' => 'Create assessment',
            'score' => 'Enter assessment score',
            'term-grade' => 'Record term grade',
        };
        $openModalOnLoad = (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && ($_POST['action'] ?? '') === $formKey
            && $errorMessage !== ''
        ) || ($formKey === 'term-grade' && $restoreGradeDraft);
        ?>
        <section class="teacher-panel" aria-labelledby="teacher-form-<?= dashboardEscape($formKey) ?>-heading" data-form-modal data-modal-trigger-label="<?= dashboardEscape($modalTriggerLabel) ?>" data-modal-trigger-class="teacher-button" data-modal-open-on-load="<?= $openModalOnLoad ? 'true' : 'false' ?>"<?= $formKey === 'attendance' ? ' data-modal-initial-focus="heading"' : '' ?>>
            <h2 id="teacher-form-<?= dashboardEscape($formKey) ?>-heading"<?= $formKey === 'attendance' ? ' tabindex="-1"' : '' ?>><?= dashboardEscape($form['title']) ?></h2>
            <form class="teacher-form" method="post" action="teacher-section.php?section=<?= dashboardEscape(rawurlencode($sectionKey)) ?>"<?= $formKey === 'attendance' ? ' data-attendance-form' : '' ?><?= $formKey === 'term-grade' ? ' data-term-grade-form' : '' ?>>
                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                <input type="hidden" name="action" value="<?= dashboardEscape($formKey) ?>">
                <?php if ($formKey === 'attendance'): ?>
                    <?php
                    $selectedAssignmentId = $oldInputAction === 'attendance' && is_string($oldInput['assignment_id'] ?? null)
                        ? $oldInput['assignment_id']
                        : '';
                    $selectedAttendanceDate = $oldInputAction === 'attendance' && is_string($oldInput['attendance_date'] ?? null)
                        ? $oldInput['attendance_date']
                        : date('Y-m-d');
                    $selectedAttendanceStatus = $oldInputAction === 'attendance' && is_string($oldInput['attendance_status'] ?? null)
                        ? $oldInput['attendance_status']
                        : '';
                    $selectedAttendanceRemarks = $oldInputAction === 'attendance' && is_string($oldInput['remarks'] ?? null)
                        ? $oldInput['remarks']
                        : '';
                    $assignmentError = $teacherFieldError('assignment_id');
                    $attendanceDateError = $teacherFieldError('attendance_date');
                    $attendanceStatusError = $teacherFieldError('attendance_status');
                    $attendanceRosterError = $teacherFieldError('enrollment_ids');
                    $hasSelectedAttendanceAssignment = $selectedAssignmentId !== '' && $assignmentError === '';
                    $attendanceSubmitDisabled = $hasSelectedAttendanceAssignment
                        && ($attendanceRosters[(int) $selectedAssignmentId] ?? []) === [];
                    $attendanceScheduleData = json_encode($attendanceSchedules, JSON_THROW_ON_ERROR);
                    $attendanceSelectionErrorAttributes = $attendanceRosterError !== ''
                        ? ' aria-invalid="true" aria-describedby="teacher-attendance-roster-error"'
                        : '';
                    ?>
                    <p class="teacher-field-note">
                        Choose an assigned class to load its next scheduled date, time, and enrolled students. You can change the date if needed.
                    </p>
                    <div class="teacher-form-grid">
                        <div class="teacher-field">
                            <label for="teacher-attendance-assignment_id">Class <span class="teacher-required-marker">Required</span></label>
                            <select
                                id="teacher-attendance-assignment_id"
                                name="assignment_id"
                                required
                                data-attendance-class
                                data-attendance-schedules="<?= dashboardEscape($attendanceScheduleData) ?>"
                                aria-describedby="teacher-attendance-schedule-summary<?= $assignmentError !== '' ? ' teacher-attendance-assignment-error' : '' ?>"
                                <?= $assignmentError !== '' ? 'aria-invalid="true"' : '' ?>
                            >
                                <option value="">Select a class</option>
                                <?php foreach ($attendanceAssignments as $assignment): ?>
                                    <option value="<?= dashboardEscape($assignment['id']) ?>"<?= $selectedAssignmentId === (string) $assignment['id'] ? ' selected' : '' ?>>
                                        <?= dashboardEscape($assignment['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($assignmentError !== ''): ?>
                                <span class="teacher-field-error" id="teacher-attendance-assignment-error"><?= dashboardEscape($assignmentError) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="teacher-attendance-schedule" id="teacher-attendance-schedule-summary" data-attendance-schedule-summary role="status" aria-live="polite" aria-atomic="true">
                            Select a class to load its next scheduled date and time.
                        </p>
                        <p class="teacher-attendance-empty" data-attendance-no-class<?= $hasSelectedAttendanceAssignment ? ' hidden' : '' ?>>
                            <?= $attendanceAssignments === [] ? 'No active scheduled classes are assigned to your account.' : 'Choose an assigned class to see its enrolled students.' ?>
                        </p>
                        <div class="teacher-form-grid teacher-field--wide" data-attendance-details<?= $hasSelectedAttendanceAssignment ? '' : ' hidden' ?>>
                            <div class="teacher-field">
                                <label for="teacher-attendance-date">Attendance date <span class="teacher-required-marker">Required</span></label>
                                <input
                                    id="teacher-attendance-date"
                                    name="attendance_date"
                                    type="date"
                                    value="<?= dashboardEscape($selectedAttendanceDate) ?>"
                                    required
                                    data-attendance-date
                                    aria-describedby="teacher-attendance-schedule-summary<?= $attendanceDateError !== '' ? ' teacher-attendance-date-error' : '' ?>"
                                    <?= $attendanceDateError !== '' ? 'aria-invalid="true"' : '' ?>
                                    <?= $hasSelectedAttendanceAssignment ? '' : 'disabled' ?>
                                >
                                <?php if ($attendanceDateError !== ''): ?>
                                    <span class="teacher-field-error" id="teacher-attendance-date-error"><?= dashboardEscape($attendanceDateError) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="teacher-field teacher-field--wide">
                                <span class="teacher-field-label">Students <span class="teacher-required-marker">Required</span></span>
                                <?php if ($attendanceRosterError !== ''): ?>
                                    <p class="teacher-field-error" id="teacher-attendance-roster-error" role="alert"><?= dashboardEscape($attendanceRosterError) ?></p>
                                <?php endif; ?>
                                <?php foreach ($attendanceAssignments as $assignment): ?>
                                    <?php
                                    $assignmentId = (string) $assignment['id'];
                                    $rosterIsSelected = $selectedAssignmentId === $assignmentId && $hasSelectedAttendanceAssignment;
                                    $roster = $attendanceRosters[(int) $assignment['id']] ?? [];
                                    $rosterId = 'teacher-attendance-roster-' . $assignmentId;
                                    ?>
                                    <fieldset
                                        class="teacher-attendance-roster"
                                        data-attendance-roster
                                        data-assignment-id="<?= dashboardEscape($assignmentId) ?>"
                                        <?= $rosterIsSelected ? '' : 'hidden disabled' ?>
                                        <?= $attendanceSelectionErrorAttributes ?>
                                    >
                                        <legend>Students in <?= dashboardEscape($assignment['label']) ?> (required; select at least one)</legend>
                                        <p class="teacher-attendance-selection-count" data-attendance-selection-count>
                                            <?= count(array_intersect(array_map(
                                                static fn (array $student): int => (int) $student['enrollment_id'],
                                                $roster
                                            ), $oldAttendanceEnrollmentIds)) ?> students selected.
                                        </p>
                                        <?php if ($roster === []): ?>
                                            <p class="teacher-attendance-empty">No enrolled students are available. Choose another assigned class or contact a school administrator.</p>
                                        <?php else: ?>
                                            <label class="teacher-attendance-select-all" for="<?= dashboardEscape($rosterId) ?>-all">
                                                <input id="<?= dashboardEscape($rosterId) ?>-all" type="checkbox" data-attendance-select-all aria-controls="<?= dashboardEscape($rosterId) ?>-students"<?= $attendanceSelectionErrorAttributes ?>>
                                                Select all <?= dashboardEscape(count($roster)) ?> students
                                            </label>
                                            <div class="teacher-attendance-student-list" id="<?= dashboardEscape($rosterId) ?>-students">
                                                <?php foreach ($roster as $student): ?>
                                                    <?php $studentEnrollmentId = (int) $student['enrollment_id']; ?>
                                                    <label class="teacher-attendance-student" for="teacher-attendance-student-<?= dashboardEscape($assignmentId) ?>-<?= dashboardEscape($studentEnrollmentId) ?>">
                                                        <input
                                                            id="teacher-attendance-student-<?= dashboardEscape($assignmentId) ?>-<?= dashboardEscape($studentEnrollmentId) ?>"
                                                            type="checkbox"
                                                            name="enrollment_ids[]"
                                                            value="<?= dashboardEscape($studentEnrollmentId) ?>"
                                                            data-attendance-student
                                                            <?= $attendanceSelectionErrorAttributes ?>
                                                            <?= in_array($studentEnrollmentId, $oldAttendanceEnrollmentIds, true) ? 'checked' : '' ?>
                                                        >
                                                        <span>
                                                            <strong><?= dashboardEscape($student['student_name']) ?></strong>
                                                            <span><?= dashboardEscape($student['student_number']) ?></span>
                                                        </span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </fieldset>
                                <?php endforeach; ?>
                                <?php if ($attendanceAssignments === []): ?>
                                    <p class="teacher-attendance-empty">No active scheduled classes are assigned to your account.</p>
                                <?php endif; ?>
                            </div>
                            <div class="teacher-field">
                                <label for="teacher-attendance-status">Status <span class="teacher-required-marker">Required</span></label>
                                <select
                                    id="teacher-attendance-status"
                                    name="attendance_status"
                                    required
                                    <?= $attendanceStatusError !== '' ? 'aria-invalid="true" aria-describedby="teacher-attendance-status-error"' : '' ?>
                                    <?= $hasSelectedAttendanceAssignment ? '' : 'disabled' ?>
                                >
                                    <option value="">Select a status</option>
                                    <?php foreach (['Present', 'Absent', 'Excused'] as $status): ?>
                                        <option value="<?= dashboardEscape($status) ?>"<?= $selectedAttendanceStatus === $status ? ' selected' : '' ?>>
                                            <?= dashboardEscape($status) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($attendanceStatusError !== ''): ?>
                                    <span class="teacher-field-error" id="teacher-attendance-status-error"><?= dashboardEscape($attendanceStatusError) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="teacher-field teacher-field--wide">
                                <label for="teacher-attendance-remarks">Remarks</label>
                                <textarea id="teacher-attendance-remarks" name="remarks" rows="3"<?= $hasSelectedAttendanceAssignment ? '' : ' disabled' ?>><?= dashboardEscape($selectedAttendanceRemarks) ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="teacher-field-note"><span class="teacher-required-marker">Required</span> fields must be completed.</p>
                    <div class="teacher-form-grid">
                    <?php foreach ($form['fields'] as $name => $definition): ?>
                        <?php
                        $inputId = 'teacher-' . $formKey . '-' . $name;
                        $errorId = $inputId . '-error';
                        $value = $oldInputAction === $formKey && is_string($oldInput[$name] ?? null)
                            ? $oldInput[$name]
                            : '';
                        $error = $teacherFieldError($name);
                        $describedBy = [];
                        if ($error !== '') {
                            $describedBy[] = $errorId;
                        }
                        $studentControllerId = match (true) {
                            $formKey === 'score' && $name === 'student_id' => 'teacher-score-assessment_id',
                            $formKey === 'term-grade' && $name === 'enrollment_id' => 'teacher-term-grade-assignment_id',
                            default => null,
                        };
                        $studentControllerName = match ($formKey) {
                            'score' => 'assessment_id',
                            'term-grade' => 'assignment_id',
                            default => '',
                        };
                        $studentStatusId = 'teacher-' . $formKey . '-student-status';
                        if ($studentControllerId !== null) {
                            $describedBy[] = $studentStatusId;
                        }
                        $studentDependencyAttributes = $studentControllerId === null
                            ? ''
                            : ' data-student-dependent data-student-controller="' . dashboardEscape($studentControllerId) . '" data-student-status="' . dashboardEscape($studentStatusId) . '"'
                                . (($oldInput[$studentControllerName] ?? '') === '' ? ' disabled' : '');
                        $studentControllerAttributes = match (true) {
                            $formKey === 'score' && $name === 'assessment_id' => ' data-student-controller-trigger',
                            $formKey === 'term-grade' && $name === 'assignment_id' => ' data-student-controller-trigger',
                            default => '',
                        };
                        $invalidAttributes = $error !== ''
                            ? ' aria-invalid="true"'
                            : '';
                        if ($describedBy !== []) {
                            $invalidAttributes .= ' aria-describedby="' . dashboardEscape(implode(' ', $describedBy)) . '"';
                        }
                        $options = is_string($definition['options'])
                            ? ($lookupOptions[$definition['options']] ?? [])
                            : [];
                        ?>
                        <div class="teacher-field<?= $definition['type'] === 'textarea' ? ' teacher-field--wide' : '' ?>">
                            <label for="<?= dashboardEscape($inputId) ?>">
                                <?= dashboardEscape($definition['label']) ?>
                                <?php if ($definition['required']): ?><span class="teacher-required-marker">Required</span><?php endif; ?>
                            </label>
                            <?php if ($definition['type'] === 'textarea'): ?>
                                <textarea id="<?= dashboardEscape($inputId) ?>" name="<?= dashboardEscape($name) ?>" rows="3"<?= $definition['required'] ? ' required' : '' ?><?= $invalidAttributes ?>><?= dashboardEscape($value) ?></textarea>
                            <?php elseif ($definition['type'] === 'select'): ?>
                                <select id="<?= dashboardEscape($inputId) ?>" name="<?= dashboardEscape($name) ?>"<?= $definition['required'] ? ' required' : '' ?><?= $invalidAttributes ?><?= $studentDependencyAttributes ?><?= $studentControllerAttributes ?><?= $formKey === 'term-grade' && $name === 'assignment_id' ? ' data-grade-assignment' : '' ?><?= $formKey === 'term-grade' && $name === 'enrollment_id' ? ' data-grade-enrollment' : '' ?><?= $formKey === 'term-grade' && $name === 'grading_period_id' ? ' data-grade-period' : '' ?>>
                                    <option value="">Select <?= dashboardEscape(strtolower($definition['label'])) ?></option>
                                    <?php if (is_array($definition['options'])): ?>
                                        <?php foreach ($definition['options'] as $option => $label): ?>
                                            <option value="<?= dashboardEscape($option) ?>"<?= $value === (string) $option ? ' selected' : '' ?>><?= dashboardEscape($label) ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach ($options as $option): ?>
                                            <option value="<?= dashboardEscape($option['id']) ?>"<?= isset($option['assignment_id']) ? ' data-assignment-id="' . dashboardEscape($option['assignment_id']) . '"' : '' ?><?= $formKey === 'term-grade' && $name === 'grading_period_id' ? ' data-period-name="' . dashboardEscape($option['period_name']) . '" data-academic-year-id="' . dashboardEscape($option['academic_year_id']) . '" data-semester-id="' . dashboardEscape($option['semester_id'] ?? '') . '"' : '' ?><?= $value === (string) $option['id'] ? ' selected' : '' ?>><?= dashboardEscape($option['label']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            <?php else: ?>
                                <input
                                    id="<?= dashboardEscape($inputId) ?>"
                                    name="<?= dashboardEscape($name) ?>"
                                    type="<?= dashboardEscape($definition['type']) ?>"
                                    value="<?= dashboardEscape($value) ?>"
                                    <?= $definition['type'] === 'number' ? 'min="0" step="0.01"' : '' ?>
                                    <?= $definition['required'] ? 'required' : '' ?>
                                    <?= $invalidAttributes ?>
                                >
                            <?php endif; ?>
                            <?php if ($error !== ''): ?>
                                <span class="teacher-field-error" id="<?= dashboardEscape($errorId) ?>"><?= dashboardEscape($error) ?></span>
                            <?php endif; ?>
                            <?php if ($studentControllerId !== null): ?>
                                <p class="teacher-field-note" id="<?= dashboardEscape($studentStatusId) ?>" data-student-dependent-status role="status" aria-live="polite" aria-atomic="true">
                                    <?= $value !== '' ? 'Loading students for the selected ' . ($formKey === 'score' ? 'assessment' : 'class assignment') . '.' : 'Choose ' . ($formKey === 'score' ? 'an assessment' : 'a class assignment') . ' to load its students.' ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <?php if ($formKey === 'term-grade'): ?>
                        <?php
                        $gradeHistoryData = json_encode($gradeHistory, JSON_THROW_ON_ERROR);
                        $gradeAssessmentScoresData = json_encode($gradeAssessmentScores, JSON_THROW_ON_ERROR);
                        $hasSubmittedComponents = $oldInputAction === 'term-grade'
                            && array_intersect(
                                ['component_attendance', 'component_recitation'],
                                array_keys($oldInput)
                            ) !== [];
                        $gradeControlsReady = ($oldInput['assignment_id'] ?? '') !== ''
                            && ($oldInput['enrollment_id'] ?? '') !== ''
                            && ($oldInput['grading_period_id'] ?? '') !== '';
                        ?>
                        <fieldset
                            class="teacher-grade-calculator"
                            data-grade-calculator
                            data-grade-history="<?= dashboardEscape($gradeHistoryData) ?>"
                            data-grade-assessment-scores="<?= dashboardEscape($gradeAssessmentScoresData) ?>"
                            data-preserve-submitted-components="<?= $hasSubmittedComponents ? 'true' : 'false' ?>"
                        >
                            <legend>Term grade calculation</legend>
                            <p class="teacher-grade-help" id="teacher-term-grade-help">
                                Exam and quiz percentages are calculated from scored assessments in the selected grading period (total points earned divided by total points possible). Enter attendance and recitation as percentages from 0 to 100. Exam contributes 50%, quizzes 25%, attendance 10%, and recitation 15%.
                                Midterm combines the saved Prelim grade (25%) with the weighted Midterm components (75%).
                                Final combines the saved Midterm grade (25%) with the weighted Final components (75%).
                            </p>
                            <div class="teacher-grade-components">
                                <?php foreach ([
                                    'component_exam' => ['Exam', '50%'],
                                    'component_quizzes' => ['Quizzes', '25%'],
                                    'component_attendance' => ['Attendance', '10%'],
                                    'component_recitation' => ['Recitation', '15%'],
                                ] as $componentName => [$componentLabel, $componentWeight]): ?>
                                    <?php
                                    $componentId = 'teacher-term-grade-' . str_replace('_', '-', $componentName);
                                    $componentError = $teacherFieldError($componentName);
                                    $isAssessmentComponent = in_array($componentName, ['component_exam', 'component_quizzes'], true);
                                    $componentValue = $oldInputAction === 'term-grade' && is_string($oldInput[$componentName] ?? null)
                                        ? $oldInput[$componentName]
                                        : '';
                                    ?>
                                    <div class="teacher-field">
                                        <label for="<?= dashboardEscape($componentId) ?>">
                                            <?= dashboardEscape($componentLabel) ?>
                                            <span class="teacher-grade-weight"><?= dashboardEscape($componentWeight) ?></span>
                                            <?php if (!$isAssessmentComponent): ?><span class="teacher-required-marker">Required</span><?php endif; ?>
                                        </label>
                                        <?php if ($isAssessmentComponent): ?>
                                            <input
                                                id="<?= dashboardEscape($componentId) ?>"
                                                type="text"
                                                value="—"
                                                readonly
                                                data-grade-auto-component="<?= dashboardEscape($componentName) ?>"
                                                aria-describedby="teacher-term-grade-help<?= $componentError !== '' ? ' ' . dashboardEscape($componentId) . '-error' : '' ?>"
                                                <?= $componentError !== '' ? 'aria-invalid="true"' : '' ?>
                                                <?= $gradeControlsReady ? '' : 'disabled' ?>
                                            >
                                            <p class="teacher-field-note" data-grade-assessment-detail="<?= dashboardEscape($componentName) ?>">Select a class, student, and grading period to load scored assessments.</p>
                                        <?php else: ?>
                                            <input
                                                id="<?= dashboardEscape($componentId) ?>"
                                                name="<?= dashboardEscape($componentName) ?>"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                inputmode="decimal"
                                                value="<?= dashboardEscape($componentValue) ?>"
                                                required
                                                data-grade-component
                                                aria-describedby="teacher-term-grade-help<?= $componentError !== '' ? ' ' . dashboardEscape($componentId) . '-error' : '' ?>"
                                                <?= $componentError !== '' ? 'aria-invalid="true"' : '' ?>
                                                <?= $gradeControlsReady ? '' : 'disabled' ?>
                                            >
                                        <?php endif; ?>
                                        <?php if ($componentError !== ''): ?>
                                            <span class="teacher-field-error" id="<?= dashboardEscape($componentId) ?>-error"><?= dashboardEscape($componentError) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="teacher-grade-calculation" data-grade-calculation-status role="status" aria-live="polite" aria-atomic="true">
                                Select a class, student, and grading period to calculate a grade.
                            </p>
                            <p class="teacher-grade-result">
                                <span>Calculated grade</span>
                                <span class="teacher-grade-result-value" data-grade-calculated-output aria-hidden="true">—</span>
                            </p>
                        </fieldset>
                    <?php endif; ?>
                <?php endif; ?>
                <button class="teacher-button" type="submit"<?= $formKey === 'attendance' ? ' data-attendance-submit' . ($attendanceSubmitDisabled ? ' disabled' : '') : '' ?>><?= dashboardEscape($form['submitLabel']) ?></button>
            </form>
        </section>
    <?php endforeach; ?>

    <?php foreach ($tableDefinitions[$sectionKey] ?? [] as $tableIndex => $table): ?>
        <section class="teacher-panel" aria-labelledby="teacher-table-<?= dashboardEscape($sectionKey) ?>-<?= dashboardEscape($tableIndex) ?>-heading">
            <h2 id="teacher-table-<?= dashboardEscape($sectionKey) ?>-<?= dashboardEscape($tableIndex) ?>-heading"><?= dashboardEscape($table['title']) ?></h2>
            <?php if ($table['rows'] === []): ?>
                <p class="teacher-empty"><?= dashboardEscape($table['empty']) ?></p>
            <?php else: ?>
                <p class="teacher-panel-description"><?= dashboardEscape($table['caption']) ?>. Scroll horizontally if needed.</p>
                <div class="teacher-table-scroll" role="group" tabindex="0" aria-label="<?= dashboardEscape($table['title']) ?>; scroll horizontally to view all columns">
                    <table class="teacher-table">
                        <caption><?= dashboardEscape($table['caption']) ?></caption>
                        <thead>
                            <tr>
                                <?php foreach ($table['columns'] as $column): ?>
                                    <th scope="col"><?= dashboardEscape($column) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table['rows'] as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cellIndex => $cell): ?>
                                        <?php if ($cellIndex === 0): ?>
                                            <th scope="row"><?= $teacherEscapeValue($cell) ?></th>
                                        <?php else: ?>
                                            <td><?= $teacherEscapeValue($cell) ?></td>
                                        <?php endif; ?>
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
