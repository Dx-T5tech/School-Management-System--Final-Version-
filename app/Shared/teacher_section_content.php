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
    <p class="teacher-notice teacher-notice--error" role="alert" tabindex="-1" data-error-summary><?= dashboardEscape($errorMessage) ?></p>
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
        ?>
        <section class="teacher-panel" aria-labelledby="teacher-form-<?= dashboardEscape($formKey) ?>-heading" data-form-modal data-modal-trigger-label="<?= dashboardEscape($modalTriggerLabel) ?>" data-modal-trigger-class="teacher-button" data-modal-open-on-load="<?= $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === $formKey && $errorMessage !== '' ? 'true' : 'false' ?>">
            <h2 id="teacher-form-<?= dashboardEscape($formKey) ?>-heading"><?= dashboardEscape($form['title']) ?></h2>
            <form class="teacher-form" method="post" action="teacher-section.php?section=<?= dashboardEscape(rawurlencode($sectionKey)) ?>">
                <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                <input type="hidden" name="action" value="<?= dashboardEscape($formKey) ?>">
                <p class="teacher-field-note"><span class="teacher-required-marker">Required</span> fields must be completed.</p>
                <div class="teacher-form-grid">
                    <?php foreach ($form['fields'] as $name => $definition): ?>
                        <?php if ($formKey === 'term-grade' && $name === 'preliminary_grade'): ?>
                            <?php $gradeGroupError = $teacherFieldError('grade_values'); ?>
                            <fieldset class="teacher-grade-group">
                                <legend>Term grade entries</legend>
                                <p class="teacher-field-note" id="teacher-term-grade-help">Enter a preliminary grade, a final grade, or both. Each value must be between 0 and 100.</p>
                                <?php if ($gradeGroupError !== ''): ?>
                                    <p class="teacher-field-error" id="teacher-term-grade-error"><?= dashboardEscape($gradeGroupError) ?></p>
                                <?php endif; ?>
                                <div class="teacher-form-grid">
                        <?php endif; ?>
                        <?php
                        $inputId = 'teacher-' . $formKey . '-' . $name;
                        $errorId = $inputId . '-error';
                        $value = $oldInputAction === $formKey && is_string($oldInput[$name] ?? null)
                            ? $oldInput[$name]
                            : '';
                        $gradeField = $formKey === 'term-grade'
                            && in_array($name, ['preliminary_grade', 'final_grade'], true);
                        $gradeGroupError = $gradeField ? $teacherFieldError('grade_values') : '';
                        $error = $teacherFieldError($name);
                        $describedBy = [];
                        if ($gradeField) {
                            $describedBy[] = 'teacher-term-grade-help';
                        }
                        if ($error !== '') {
                            $describedBy[] = $errorId;
                        }
                        if ($gradeGroupError !== '') {
                            $describedBy[] = 'teacher-term-grade-error';
                        }
                        $invalidAttributes = $error !== '' || $gradeGroupError !== ''
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
                                <select id="<?= dashboardEscape($inputId) ?>" name="<?= dashboardEscape($name) ?>"<?= $definition['required'] ? ' required' : '' ?><?= $invalidAttributes ?>>
                                    <option value="">Select <?= dashboardEscape(strtolower($definition['label'])) ?></option>
                                    <?php if (is_array($definition['options'])): ?>
                                        <?php foreach ($definition['options'] as $option => $label): ?>
                                            <option value="<?= dashboardEscape($option) ?>"<?= $value === (string) $option ? ' selected' : '' ?>><?= dashboardEscape($label) ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach ($options as $option): ?>
                                            <option value="<?= dashboardEscape($option['id']) ?>"<?= $value === (string) $option['id'] ? ' selected' : '' ?>><?= dashboardEscape($option['label']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            <?php else: ?>
                                <input
                                    id="<?= dashboardEscape($inputId) ?>"
                                    name="<?= dashboardEscape($name) ?>"
                                    type="<?= dashboardEscape($definition['type']) ?>"
                                    value="<?= dashboardEscape($value) ?>"
                                    <?= $definition['type'] === 'number' ? 'min="0" step="0.01"' . ($gradeField ? ' max="100"' : '') : '' ?>
                                    <?= $definition['required'] ? 'required' : '' ?>
                                    <?= $invalidAttributes ?>
                                >
                            <?php endif; ?>
                            <?php if ($error !== ''): ?>
                                <span class="teacher-field-error" id="<?= dashboardEscape($errorId) ?>"><?= dashboardEscape($error) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($formKey === 'term-grade' && $name === 'final_grade'): ?>
                                </div>
                            </fieldset>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <button class="teacher-button" type="submit"><?= dashboardEscape($form['submitLabel']) ?></button>
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
