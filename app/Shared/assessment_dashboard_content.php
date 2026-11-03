<?php

declare(strict_types=1);

$assessmentData = $dashboard['assessmentDashboard'];
$assessmentRole = $assessmentData['role'];
$assessmentDateLabel = static function (mixed $date): string {
    if (!is_string($date) || $date === '') {
        return 'Date not set';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('M j, Y', $timestamp);
};
?>
<section class="assessment-dashboard" aria-labelledby="assessment-dashboard-heading">
    <div class="assessment-dashboard-heading">
        <div>
            <h2 id="assessment-dashboard-heading">
                <?= $assessmentRole === 'teacher' ? 'Assessment &amp; Grades' : 'Your learning dashboard' ?>
            </h2>
            <p>
                <?= $assessmentRole === 'teacher'
                    ? 'Your classes, assessments, grading, and student results.'
                    : 'Your enrolled classes, posted scores, and school updates.' ?>
            </p>
        </div>
        <?php if ($assessmentRole === 'student' && $assessmentData['studentNumber'] !== ''): ?>
            <span class="assessment-student-number">
                Student number: <?= dashboardEscape($assessmentData['studentNumber']) ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if ($assessmentRole === 'teacher'): ?>
        <nav class="assessment-dashboard-nav" aria-label="Assessment and grading">
            <a href="teacher-section.php?section=assessments">Assessments</a>
            <a href="teacher-section.php?section=grades">
                Grades
                <?php if ($assessmentData['pendingScoreCount'] > 0): ?>
                    <span class="assessment-dashboard-badge">
                        <?= dashboardEscape(number_format($assessmentData['pendingScoreCount'])) ?> ungraded
                    </span>
                <?php endif; ?>
            </a>
            <a href="teacher-section.php?section=report-cards">Report Cards</a>
        </nav>

        <div class="assessment-dashboard-grid assessment-dashboard-grid--teacher">
            <section class="assessment-dashboard-panel" aria-labelledby="assessment-classes-heading">
                <div class="assessment-dashboard-panel-heading">
                    <div>
                        <h3 id="assessment-classes-heading">Your classes</h3>
                        <p>
                            <?= dashboardEscape(number_format(count($assessmentData['classes']))) ?>
                            <?= count($assessmentData['classes']) === 1 ? 'teaching assignment' : 'teaching assignments' ?>
                        </p>
                    </div>
                    <a href="teacher-section.php?section=classes">View classes</a>
                </div>
                <?php if ($assessmentData['classes'] !== []): ?>
                    <ul class="assessment-class-list">
                        <?php foreach ($assessmentData['classes'] as $class): ?>
                            <li>
                                <div>
                                    <h4><?= dashboardEscape($class['subject_code']) ?> <span><?= dashboardEscape($class['subject_name']) ?></span></h4>
                                    <p>
                                        <?= dashboardEscape($class['section_name']) ?>
                                        · <?= dashboardEscape($class['year_name']) ?>
                                        · <?= dashboardEscape($class['semester_name']) ?>
                                    </p>
                                </div>
                                <span class="assessment-class-count">
                                    <?= dashboardEscape(number_format((int) $class['enrolled_students'])) ?>
                                    <?= (int) $class['enrolled_students'] === 1 ? 'student' : 'students' ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="assessment-dashboard-empty">
                        No teaching assignments are linked to your account yet. Contact the school administrator if this seems incorrect.
                    </p>
                <?php endif; ?>
            </section>

            <section class="assessment-dashboard-panel" aria-labelledby="assessment-recent-heading">
                <div class="assessment-dashboard-panel-heading">
                    <div>
                        <h3 id="assessment-recent-heading">Recent assessments</h3>
                        <p>Assessment activity in your assigned classes</p>
                    </div>
                    <a href="teacher-section.php?section=assessments">Manage</a>
                </div>
                <?php if ($assessmentData['assessments'] !== []): ?>
                    <ul class="assessment-activity-list">
                        <?php foreach ($assessmentData['assessments'] as $assessment): ?>
                            <li>
                                <div>
                                    <h4><?= dashboardEscape($assessment['assessment_title']) ?></h4>
                                    <p>
                                        <?= dashboardEscape($assessment['subject_code']) ?>
                                        · <?= dashboardEscape($assessment['section_name']) ?>
                                        · <?= dashboardEscape($assessment['period_name'] ?? 'No grading period') ?>
                                    </p>
                                </div>
                                <span class="assessment-activity-meta">
                                    <?= dashboardEscape($assessment['status']) ?>
                                    · <?= dashboardEscape($assessmentDateLabel($assessment['due_date'] ?? $assessment['assessment_date'])) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="assessment-dashboard-empty">
                        No assessments have been created for your classes yet.
                        <a href="teacher-section.php?section=assessments">Create an assessment</a>
                        when your class assignments are available.
                    </p>
                <?php endif; ?>
            </section>
        </div>
    <?php else: ?>
        <nav class="assessment-dashboard-nav" aria-label="Student learning">
            <a href="student-section.php?section=schedule">My Classes</a>
            <a href="student-section.php?section=grades">My Grades</a>
            <a href="student-section.php?section=report-card">Report Cards</a>
            <a href="student-section.php?section=notifications">Notifications</a>
        </nav>

        <div class="assessment-dashboard-grid assessment-dashboard-grid--student">
            <section class="assessment-dashboard-panel" aria-labelledby="student-classes-heading">
                <div class="assessment-dashboard-panel-heading">
                    <div>
                        <h3 id="student-classes-heading">My classes</h3>
                        <p>
                            <?= dashboardEscape(number_format(count($assessmentData['classes']))) ?>
                            <?= count($assessmentData['classes']) === 1 ? 'active class' : 'active classes' ?>
                        </p>
                    </div>
                    <a href="student-section.php?section=schedule">View schedule</a>
                </div>
                <?php if ($assessmentData['classes'] !== []): ?>
                    <ul class="assessment-class-list">
                        <?php foreach ($assessmentData['classes'] as $class): ?>
                            <li>
                                <div>
                                    <h4><?= dashboardEscape($class['subject_code']) ?> <span><?= dashboardEscape($class['subject_name']) ?></span></h4>
                                    <p>
                                        <?= dashboardEscape($class['section_name']) ?>
                                        · <?= dashboardEscape($class['semester_name']) ?>
                                    </p>
                                </div>
                                <span class="assessment-class-teacher">
                                    <?= dashboardEscape($class['teacher_name'] ?: 'Teacher not assigned') ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="assessment-dashboard-empty">
                        No classes are linked to your active enrollment yet. Contact the school office if you expected a class here.
                    </p>
                <?php endif; ?>
            </section>

            <section class="assessment-dashboard-panel" aria-labelledby="student-notifications-heading">
                <div class="assessment-dashboard-panel-heading">
                    <div>
                        <h3 id="student-notifications-heading">Recent notifications</h3>
                        <p>Updates sent to your student account</p>
                    </div>
                    <a href="student-section.php?section=notifications">View all</a>
                </div>
                <?php if ($assessmentData['notifications'] !== []): ?>
                    <ul class="assessment-activity-list">
                        <?php foreach ($assessmentData['notifications'] as $notification): ?>
                            <li>
                                <div>
                                    <h4>
                                        <?= dashboardEscape($notification['title']) ?>
                                        <?php if (!(bool) $notification['is_read']): ?>
                                            <span class="assessment-unread-label">New</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p><?= dashboardEscape($notification['message']) ?></p>
                                </div>
                                <span class="assessment-activity-meta">
                                    <?= dashboardEscape($notification['notification_type']) ?>
                                    · <?= dashboardEscape($assessmentDateLabel($notification['created_at'])) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="assessment-dashboard-empty">
                        You’re all caught up. New account notifications will appear here.
                    </p>
                <?php endif; ?>
            </section>

            <section class="assessment-dashboard-panel assessment-dashboard-panel--wide" aria-labelledby="student-assessments-heading">
                <div class="assessment-dashboard-panel-heading">
                    <div>
                        <h3 id="student-assessments-heading">Recent assessment results</h3>
                        <p>Scores appear here after they are entered by your teacher.</p>
                    </div>
                    <a href="student-section.php?section=grades">View grades</a>
                </div>
                <?php if ($assessmentData['assessments'] !== []): ?>
                    <ul class="assessment-activity-list">
                        <?php foreach ($assessmentData['assessments'] as $assessment): ?>
                            <li>
                                <div>
                                    <h4><?= dashboardEscape($assessment['assessment_title']) ?></h4>
                                    <p>
                                        <?= dashboardEscape($assessment['subject_code']) ?>
                                        · <?= dashboardEscape($assessment['period_name'] ?? 'No grading period') ?>
                                        · <?= dashboardEscape($assessment['assessment_type']) ?>
                                    </p>
                                </div>
                                <span class="assessment-activity-meta">
                                    <?= $assessment['score'] === null
                                        ? 'Score not posted'
                                        : dashboardEscape($assessment['score'] . ' / ' . $assessment['total_points']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="assessment-dashboard-empty">
                        No published assessment results are available yet. Check My Grades for updates.
                    </p>
                <?php endif; ?>
            </section>
        </div>
    <?php endif; ?>
</section>
