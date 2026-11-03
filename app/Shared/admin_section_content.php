<?php

declare(strict_types=1);

$escapeSectionValue = static fn (mixed $value): string => dashboardEscape(
    is_string($value) || is_int($value) || is_float($value) ? $value : ''
);
$fieldError = static fn (string $name): string => is_string($fieldErrors[$name] ?? null)
    ? $fieldErrors[$name]
    : '';
$entitiesForSection = array_filter(
    $entityDefinitions,
    static fn (array $entity): bool => $entity['section'] === $sectionKey
        && (
            ($sectionKey !== 'academic-management' || ($entity['academicView'] ?? null) === $academicView)
            && ($sectionKey !== 'attendance-scheduling' || ($entity['attendanceView'] ?? null) === $attendanceView)
            && ($sectionKey !== 'assessment-grading' || ($entity['assessmentView'] ?? null) === $assessmentView)
            && ($sectionKey !== 'finance-payments' || ($entity['financeView'] ?? null) === $financeView)
        ),
    ARRAY_FILTER_USE_BOTH
);
$lookupLabels = [];
foreach ($lookupOptions as $lookupName => $options) {
    foreach ($options as $option) {
        $lookupLabels[$lookupName][(string) $option['id']] = $option['label'];
    }
}
?>
<?php if (is_string($flashMessage) && $flashMessage !== ''): ?>
    <p class="admin-notice admin-notice--success" role="status" tabindex="-1" data-status-summary><?= dashboardEscape($flashMessage) ?></p>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <p class="admin-notice admin-notice--error" role="alert" tabindex="-1" data-error-summary><?= dashboardEscape($errorMessage) ?></p>
<?php endif; ?>

<div class="admin-section">
    <?php if (!empty($dashboard['attendanceSubnav'])): ?>
        <?php if ($attendanceView === 'attendance-overview' && is_array($attendanceOverview)): ?>
            <div class="admin-panel">
                <h2>Attendance overview</h2>
                <p class="admin-panel-description">Daily attendance recorded for students in scheduled class sections.</p>
                <form class="admin-form attendance-date-filter" method="get" action="admin-section.php">
                    <input type="hidden" name="section" value="attendance-scheduling">
                    <input type="hidden" name="attendance_view" value="attendance-overview">
                    <div class="admin-field">
                        <label for="attendance-date-filter">Attendance date</label>
                        <input id="attendance-date-filter" type="date" name="attendance_date" value="<?= dashboardEscape($attendanceOverview['date']) ?>">
                    </div>
                    <div class="admin-field">
                        <label for="attendance-assignment-filter">Class</label>
                        <select id="attendance-assignment-filter" name="assignment_id">
                            <option value="">All active classes</option>
                            <?php foreach ($attendanceOverview['assignments'] as $assignment): ?>
                                <option value="<?= dashboardEscape($assignment['assignment_id']) ?>"<?= $attendanceOverview['assignmentId'] === (int) $assignment['assignment_id'] ? ' selected' : '' ?>><?= dashboardEscape($assignment['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-form-actions">
                        <button class="admin-button" type="submit">Show attendance</button>
                        <a
                            class="admin-button admin-button--secondary"
                            href="admin-section.php?section=attendance-scheduling&amp;attendance_view=attendance-overview&amp;attendance_date=<?= dashboardEscape(rawurlencode($attendanceOverview['date'])) ?><?= $attendanceOverview['assignmentId'] !== null ? '&amp;assignment_id=' . dashboardEscape((int) $attendanceOverview['assignmentId']) : '' ?>&amp;export=csv"
                        >Export CSV</a>
                    </div>
                </form>
                <dl class="schedule-summary-grid">
                    <div><dt>Present</dt><dd class="schedule-status-present"><?= dashboardEscape($attendanceOverview['counts']['Present']) ?></dd></div>
                    <div><dt>Absent</dt><dd class="schedule-status-absent"><?= dashboardEscape($attendanceOverview['counts']['Absent']) ?></dd></div>
                    <div><dt>Late</dt><dd class="schedule-status-late"><?= dashboardEscape($attendanceOverview['counts']['Late']) ?></dd></div>
                    <div><dt>Total marked</dt><dd><?= dashboardEscape(count($attendanceOverview['rows'])) ?></dd></div>
                </dl>
                <?php if ($attendanceOverview['rows'] === []): ?>
                    <p class="admin-empty">No attendance records were found for this date.</p>
                <?php else: ?>
                    <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Attendance records; scroll horizontally to view all columns">
                        <table class="admin-table">
                            <caption>Attendance for <?= dashboardEscape($attendanceOverview['date']) ?></caption>
                            <thead><tr><th scope="col">Student</th><th scope="col">Section</th><th scope="col">Subject</th><th scope="col">Teacher</th><th scope="col">Time in</th><th scope="col">Time out</th><th scope="col">Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($attendanceOverview['rows'] as $record): ?>
                                    <tr>
                                        <th scope="row"><?= dashboardEscape($record['first_name'] . ' ' . $record['last_name']) ?> <span class="schedule-muted">(<?= dashboardEscape($record['student_number']) ?>)</span></th>
                                        <td><?= dashboardEscape((string) ($record['section_name'] ?? '—')) ?></td>
                                        <td><?= dashboardEscape($record['subject_code'] . ' — ' . $record['subject_name']) ?></td>
                                        <td><?= dashboardEscape($record['teacher_first_name'] . ' ' . $record['teacher_last_name']) ?></td>
                                        <td><?= dashboardEscape((string) ($record['time_in'] ?? '—')) ?></td>
                                        <td><?= dashboardEscape((string) ($record['time_out'] ?? '—')) ?></td>
                                        <td><?= dashboardEscape($record['attendance_status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($attendanceView === 'teacher-schedule' && is_array($teacherSchedule)): ?>
            <div class="admin-panel">
                <h2>Teacher schedule</h2>
                <p class="admin-panel-description">Review teaching assignments by department, teacher, and day. Open hours are shown when a teacher and day are selected.</p>
                <form class="admin-form schedule-filter-form" method="get" action="admin-section.php">
                    <input type="hidden" name="section" value="attendance-scheduling">
                    <input type="hidden" name="attendance_view" value="teacher-schedule">
                    <div class="admin-field">
                        <label for="teacher-schedule-department">Department</label>
                        <select id="teacher-schedule-department" name="department_id">
                            <option value="">All departments</option>
                            <?php foreach ($teacherSchedule['departments'] as $department): ?>
                                <option value="<?= dashboardEscape($department['department_id']) ?>"<?= $teacherSchedule['departmentId'] === (int) $department['department_id'] ? ' selected' : '' ?>><?= dashboardEscape($department['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="teacher-schedule-teacher">Teacher</label>
                        <select id="teacher-schedule-teacher" name="teacher_id">
                            <option value="">All teachers</option>
                            <?php foreach ($teacherSchedule['teachers'] as $teacher): ?>
                                <?php $teacherName = $teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['employee_number'] . ')'; ?>
                                <option value="<?= dashboardEscape($teacher['teacher_id']) ?>"<?= $teacherSchedule['teacherId'] === (int) $teacher['teacher_id'] ? ' selected' : '' ?>><?= dashboardEscape($teacherName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="teacher-schedule-day">Day</label>
                        <select id="teacher-schedule-day" name="day">
                            <option value="All days"<?= $teacherSchedule['day'] === 'All days' ? ' selected' : '' ?>>All days</option>
                            <?php foreach ($teacherSchedule['weekDays'] as $day): ?>
                                <option value="<?= dashboardEscape($day) ?>"<?= $teacherSchedule['day'] === $day ? ' selected' : '' ?>><?= dashboardEscape($day) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="admin-button" type="submit">Show schedule</button>
                </form>
                <?php if ($teacherSchedule['schedules'] === []): ?>
                    <p class="admin-empty">No active class schedules match these filters.</p>
                <?php elseif ($teacherSchedule['day'] !== 'All days' && $teacherSchedule['teacherId'] !== null): ?>
                    <?php
                    $timelineHours = range(7, 20);
                    foreach ($teacherSchedule['schedules'] as $schedule) {
                        $scheduleHour = (int) substr((string) $schedule['start_time'], 0, 2);
                        if (!in_array($scheduleHour, $timelineHours, true)) {
                            $timelineHours[] = $scheduleHour;
                        }
                    }
                    sort($timelineHours);
                    ?>
                    <p class="schedule-context"><?= dashboardEscape($teacherSchedule['schedules'][0]['teacher_first_name'] . ' ' . $teacherSchedule['schedules'][0]['teacher_last_name']) ?> · <?= dashboardEscape($teacherSchedule['day']) ?> · <?= dashboardEscape(count($teacherSchedule['schedules'])) ?> class(es)</p>
                    <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Teacher daily schedule; scroll horizontally to view all hours">
                        <table class="admin-table schedule-timeline">
                            <caption>Teacher availability by hour on <?= dashboardEscape($teacherSchedule['day']) ?></caption>
                            <thead><tr><th scope="col">Time</th><th scope="col">Schedule</th></tr></thead>
                            <tbody>
                                <?php foreach ($timelineHours as $hour): ?>
                                    <?php
                                    $hourSchedules = array_values(array_filter(
                                        $teacherSchedule['schedules'],
                                        static fn (array $schedule): bool => (string) $schedule['start_time'] < sprintf('%02d:00:00', $hour + 1)
                                            && (string) $schedule['end_time'] > sprintf('%02d:00:00', $hour)
                                    ));
                                    ?>
                                    <tr>
                                        <th scope="row"><?= dashboardEscape(date('g:00 A', strtotime(sprintf('%02d:00:00', $hour)))) ?></th>
                                        <td>
                                            <?php if ($hourSchedules === []): ?>
                                                <span class="schedule-availability">Available</span>
                                            <?php else: ?>
                                                <div class="schedule-card-list">
                                                    <?php foreach ($hourSchedules as $schedule): ?>
                                                        <article class="schedule-class-card">
                                                            <strong><?= dashboardEscape($schedule['subject_name']) ?></strong>
                                                            <span><?= dashboardEscape(date('g:i A', strtotime((string) $schedule['start_time'])) . '–' . date('g:i A', strtotime((string) $schedule['end_time'])) . ' · ' . $schedule['room_name'] . ' · ' . $schedule['section_name']) ?></span>
                                                        </article>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Teacher schedules; scroll horizontally to view all columns">
                        <table class="admin-table">
                            <caption>Active teacher schedules</caption>
                            <thead><tr><th scope="col">Day</th><th scope="col">Time</th><th scope="col">Teacher</th><th scope="col">Subject</th><th scope="col">Section</th><th scope="col">Room</th></tr></thead>
                            <tbody>
                                <?php foreach ($teacherSchedule['schedules'] as $schedule): ?>
                                    <tr>
                                        <th scope="row"><?= dashboardEscape($schedule['day_of_week']) ?></th>
                                        <td><?= dashboardEscape(date('g:i A', strtotime((string) $schedule['start_time'])) . '–' . date('g:i A', strtotime((string) $schedule['end_time']))) ?></td>
                                        <td><?= dashboardEscape($schedule['teacher_first_name'] . ' ' . $schedule['teacher_last_name']) ?></td>
                                        <td><?= dashboardEscape($schedule['subject_code'] . ' — ' . $schedule['subject_name']) ?></td>
                                        <td><?= dashboardEscape($schedule['section_name']) ?></td>
                                        <td><?= dashboardEscape($schedule['room_code'] . ' — ' . $schedule['room_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($attendanceView === 'student-schedule' && is_array($studentSchedule)): ?>
            <div class="admin-panel">
                <h2>Student schedule</h2>
                <p class="admin-panel-description">Review the weekly class schedule and time allocation for an active section.</p>
                <form class="admin-form schedule-filter-form" method="get" action="admin-section.php">
                    <input type="hidden" name="section" value="attendance-scheduling">
                    <input type="hidden" name="attendance_view" value="student-schedule">
                    <div class="admin-field">
                        <label for="student-schedule-program">Program</label>
                        <select id="student-schedule-program" name="program_id">
                            <option value="">All programs</option>
                            <?php foreach ($studentSchedule['programs'] as $program): ?>
                                <option value="<?= dashboardEscape($program['program_id']) ?>"<?= $studentSchedule['programId'] === (int) $program['program_id'] ? ' selected' : '' ?>><?= dashboardEscape($program['program_code'] . ' — ' . $program['program_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="student-schedule-year">Year level</label>
                        <select id="student-schedule-year" name="year_level">
                            <option value="">All years</option>
                            <?php foreach (array_values(array_unique(array_map(static fn (array $section): int => (int) $section['year_level'], $studentSchedule['allSections']))) as $yearLevel): ?>
                                <option value="<?= dashboardEscape($yearLevel) ?>"<?= $studentSchedule['yearLevel'] === (string) $yearLevel ? ' selected' : '' ?>>Year <?= dashboardEscape($yearLevel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="student-schedule-section">Section</label>
                        <select id="student-schedule-section" name="section_id">
                            <option value="">Select a section</option>
                            <?php foreach ($studentSchedule['sections'] as $section): ?>
                                <option value="<?= dashboardEscape($section['section_id']) ?>"<?= $studentSchedule['sectionId'] === (int) $section['section_id'] ? ' selected' : '' ?>><?= dashboardEscape($section['section_name'] . ' — Year ' . $section['year_level']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="student-schedule-day">Day</label>
                        <select id="student-schedule-day" name="day">
                            <option value="All days"<?= $studentSchedule['day'] === 'All days' ? ' selected' : '' ?>>All days</option>
                            <?php foreach ($studentSchedule['weekDays'] as $day): ?>
                                <option value="<?= dashboardEscape($day) ?>"<?= $studentSchedule['day'] === $day ? ' selected' : '' ?>><?= dashboardEscape($day) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="admin-button" type="submit">Show schedule</button>
                </form>
                <?php if ($studentSchedule['sectionId'] === null): ?>
                    <p class="admin-empty">Choose a program, year level, and section to view its schedule.</p>
                <?php elseif ($studentSchedule['schedules'] === []): ?>
                    <p class="admin-empty">No active class schedules are available for this section and day.</p>
                <?php else: ?>
                    <?php
                    $scheduleDays = $studentSchedule['day'] === 'All days'
                        ? array_values(array_unique(array_column($studentSchedule['schedules'], 'day_of_week')))
                        : [$studentSchedule['day']];
                    $scheduleDays = array_values(array_filter(
                        $studentSchedule['weekDays'],
                        static fn (string $day): bool => in_array($day, $scheduleDays, true)
                    ));
                    $scheduleTimes = array_values(array_unique(array_map(
                        static fn (array $schedule): string => (string) $schedule['start_time'],
                        $studentSchedule['schedules']
                    )));
                    sort($scheduleTimes);
                    ?>
                    <p class="schedule-context">Section <?= dashboardEscape($studentSchedule['schedules'][0]['section_name']) ?> · <?= dashboardEscape(count($studentSchedule['schedules'])) ?> class(es) across <?= dashboardEscape(count($scheduleDays)) ?> day(s)</p>
                    <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Weekly student schedule; scroll horizontally to view all days">
                        <table class="admin-table schedule-weekly">
                            <caption>Weekly schedule for <?= dashboardEscape($studentSchedule['schedules'][0]['section_name']) ?></caption>
                            <thead>
                                <tr><th scope="col">Time</th><?php foreach ($scheduleDays as $day): ?><th scope="col"><?= dashboardEscape($day) ?></th><?php endforeach; ?></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scheduleTimes as $time): ?>
                                    <tr>
                                        <th scope="row"><?= dashboardEscape(date('g:i A', strtotime($time))) ?></th>
                                        <?php foreach ($scheduleDays as $day): ?>
                                            <?php
                                            $daySchedules = array_values(array_filter(
                                                $studentSchedule['schedules'],
                                                static fn (array $schedule): bool => $schedule['day_of_week'] === $day
                                                    && (string) $schedule['start_time'] === $time
                                            ));
                                            ?>
                                            <td>
                                                <?php foreach ($daySchedules as $schedule): ?>
                                                    <article class="schedule-class-card">
                                                        <strong><?= dashboardEscape($schedule['subject_name']) ?></strong>
                                                        <span><?= dashboardEscape(date('g:i A', strtotime((string) $schedule['start_time'])) . '–' . date('g:i A', strtotime((string) $schedule['end_time'])) . ' · ' . $schedule['room_name']) ?></span>
                                                    </article>
                                                <?php endforeach; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <h3 class="schedule-subheading">Weekly allocation</h3>
                    <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Weekly time allocation; scroll horizontally to view all columns">
                        <table class="admin-table">
                            <caption>Classes and scheduled rooms by weekday</caption>
                            <thead><tr><th scope="col">Day</th><th scope="col">Classes</th><th scope="col">Time allocated</th><th scope="col">Rooms allocated</th></tr></thead>
                            <tbody>
                                <?php foreach ($scheduleDays as $day): ?>
                                    <?php
                                    $daySchedules = array_values(array_filter(
                                        $studentSchedule['schedules'],
                                        static fn (array $schedule): bool => $schedule['day_of_week'] === $day
                                    ));
                                    $allocatedTimes = array_map(
                                        static fn (array $schedule): string => date('g:i A', strtotime((string) $schedule['start_time']))
                                            . '–' . date('g:i A', strtotime((string) $schedule['end_time'])),
                                        $daySchedules
                                    );
                                    $allocatedRooms = array_values(array_unique(array_column($daySchedules, 'room_name')));
                                    ?>
                                    <tr>
                                        <th scope="row"><?= dashboardEscape($day) ?></th>
                                        <td><?= dashboardEscape(count($daySchedules)) ?></td>
                                        <td><?= dashboardEscape(implode(', ', $allocatedTimes)) ?></td>
                                        <td><?= dashboardEscape(implode(', ', $allocatedRooms)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($attendanceView === 'room-monitoring' && is_array($roomOverview)): ?>
            <?php
            $roomAvailableCount = 0;
            $roomOccupiedCount = 0;
            foreach ($roomOverview['roomStates'] as $roomState) {
                if ($roomState['status'] === 'Occupied') {
                    $roomOccupiedCount++;
                } elseif ($roomState['status'] === 'Available') {
                    $roomAvailableCount++;
                }
            }
            $roomRefreshUrl = 'admin-section.php?' . http_build_query([
                'section' => 'attendance-scheduling',
                'attendance_view' => 'room-monitoring',
                'building' => $roomOverview['building'],
                'floor' => $roomOverview['floor'],
                'q' => $roomOverview['search'],
                'room_refresh' => '1',
            ]);
            ?>
            <div class="admin-panel room-monitor-panel" data-room-monitor
                 data-room-refresh-url="<?= dashboardEscape($roomRefreshUrl) ?>">
                <h2>Room monitoring</h2>
                <p class="admin-panel-description">Live room availability from the active class schedule. This is schedule-based, not a physical sensor reading.</p>
                <form class="admin-form schedule-filter-form" method="get" action="admin-section.php">
                    <input type="hidden" name="section" value="attendance-scheduling">
                    <input type="hidden" name="attendance_view" value="room-monitoring">
                    <div class="admin-field">
                        <label for="room-building-filter">Building</label>
                        <select id="room-building-filter" name="building">
                            <option value="All buildings"<?= $roomOverview['building'] === 'All buildings' ? ' selected' : '' ?>>All buildings</option>
                            <?php foreach ($roomOverview['buildings'] as $building): ?>
                                <option value="<?= dashboardEscape($building) ?>"<?= $roomOverview['building'] === $building ? ' selected' : '' ?>><?= dashboardEscape('Building ' . $building) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="room-floor-filter">Floor</label>
                        <select id="room-floor-filter" name="floor">
                            <option value="All floors"<?= $roomOverview['floor'] === 'All floors' ? ' selected' : '' ?>>All floors</option>
                            <?php foreach ($roomOverview['floors'] as $floor): ?>
                                <option value="<?= dashboardEscape($floor) ?>"<?= $roomOverview['floor'] === $floor ? ' selected' : '' ?>><?= dashboardEscape('Floor ' . $floor) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="room-search">Search rooms</label>
                        <input id="room-search" type="search" name="q" value="<?= dashboardEscape($roomOverview['search']) ?>" placeholder="Room name or code">
                    </div>
                    <button class="admin-button" type="submit">Filter rooms</button>
                </form>
                <p class="schedule-context" data-room-summary>
                    Building <?= dashboardEscape($roomOverview['building']) ?> · <?= dashboardEscape($roomOverview['floor']) ?> ·
                    <span data-room-occupied-count><?= dashboardEscape($roomOccupiedCount) ?></span> occupied ·
                    <span data-room-available-count><?= dashboardEscape($roomAvailableCount) ?></span> available
                </p>
                <?php if ($roomOverview['rooms'] === []): ?>
                    <p class="admin-empty">No rooms match these filters.</p>
                <?php else: ?>
                    <section class="room-monitor-floor-map" aria-label="Floor room occupancy board">
                        <p class="room-monitor-map-note">Illustrative floor layout · not to scale</p>
                        <ul class="room-monitor-grid">
                            <?php $hallwayRendered = false; ?>
                            <?php foreach ($roomOverview['rooms'] as $room): ?>
                                <?php if (!$hallwayRendered && (int) $room['layout_row'] > 2): ?>
                                    <li class="room-monitor-hallway" aria-hidden="true">Hallway</li>
                                    <?php $hallwayRendered = true; ?>
                                <?php endif; ?>
                                <?php
                                $roomState = $roomOverview['roomStates'][(int) $room['room_id']];
                                $roomStatus = $roomState['status'];
                                ?>
                                <li class="room-monitor-card room-monitor-card--<?= dashboardEscape($roomState['statusKey']) ?>"
                                    data-room-card data-room-id="<?= dashboardEscape($room['room_id']) ?>"
                                    style="--room-row: <?= dashboardEscape((int) $room['layout_row']) ?>; --room-column: <?= dashboardEscape((int) $room['layout_column']) ?>; --room-column-span: <?= dashboardEscape((int) $room['column_span']) ?>;">
                                    <h3><?= dashboardEscape($room['room_name']) ?></h3>
                                    <p class="room-monitor-status">
                                        <span class="room-monitor-status-dot" aria-hidden="true"></span>
                                        <span data-room-status><?= dashboardEscape($roomStatus) ?></span>
                                    </p>
                                    <p><?= dashboardEscape($room['room_type']) ?> · <?= dashboardEscape($room['room_code']) ?></p>
                                    <p class="room-monitor-timing" data-room-timing><?= dashboardEscape($roomState['timing']) ?></p>
                                    <p class="room-monitor-details" data-room-details><?= dashboardEscape($roomState['details']) ?></p>
                                    <?php if ($room['capacity'] !== null): ?><p>Capacity: <?= dashboardEscape($room['capacity']) ?></p><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                            <?php if (!$hallwayRendered): ?>
                                <li class="room-monitor-hallway" aria-hidden="true">Hallway</li>
                            <?php endif; ?>
                        </ul>
                    </section>
                <?php endif; ?>
                <div class="room-monitor-footer">
                    <fieldset class="room-monitor-legend">
                        <legend>Room status</legend>
                        <ul>
                            <li class="room-monitor-legend-item room-monitor-legend-item--available">Available</li>
                            <li class="room-monitor-legend-item room-monitor-legend-item--occupied">Occupied by schedule</li>
                            <li class="room-monitor-legend-item room-monitor-legend-item--maintenance">Maintenance or inactive</li>
                        </ul>
                    </fieldset>
                    <p class="schedule-updated">
                        <span data-room-live-status role="status">Automatic schedule refresh every 30 seconds.</span>
                        <span>Updated <time data-room-updated datetime="<?= dashboardEscape($roomOverview['updatedAt']) ?>"><?= dashboardEscape($roomOverview['updatedLabel']) ?></time></span>
                    </p>
                </div>
            </div>
        <?php elseif ($attendanceView === 'conflict-review' && is_array($conflictOverview)): ?>
            <?php
            $conflictTypeCounts = ['Teacher' => 0, 'Room' => 0, 'Section' => 0];
            foreach ($conflictOverview['allConflicts'] as $conflict) {
                foreach ($conflict['types'] as $type) {
                    $conflictTypeCounts[$type]++;
                }
            }
            ?>
            <div class="admin-panel">
                <h2>Conflict review</h2>
                <p class="admin-panel-description">Review active schedules that overlap on the same day for a teacher, room, or class section.</p>
                <form class="admin-form schedule-filter-form" method="get" action="admin-section.php">
                    <input type="hidden" name="section" value="attendance-scheduling">
                    <input type="hidden" name="attendance_view" value="conflict-review">
                    <div class="admin-field">
                        <label for="conflict-type-filter">Conflict type</label>
                        <select id="conflict-type-filter" name="type">
                            <?php foreach (['All types', 'Teacher', 'Room', 'Section'] as $type): ?>
                                <option value="<?= dashboardEscape($type) ?>"<?= $conflictOverview['filter'] === $type ? ' selected' : '' ?>><?= dashboardEscape($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="admin-button" type="submit">Filter conflicts</button>
                </form>
                <div class="conflict-policy">
                    <strong>Schedule checks</strong>
                    <p>Active classes on the same weekday are compared for overlapping times and shared teachers, rooms, or sections.</p>
                    <span><?= count($conflictOverview['allConflicts']) === 0 ? 'No overlaps found' : 'Review overlaps below' ?></span>
                </div>
                <dl class="schedule-summary-grid">
                    <div><dt>Teacher conflicts</dt><dd><?= dashboardEscape($conflictTypeCounts['Teacher']) ?></dd></div>
                    <div><dt>Room conflicts</dt><dd><?= dashboardEscape($conflictTypeCounts['Room']) ?></dd></div>
                    <div><dt>Section conflicts</dt><dd><?= dashboardEscape($conflictTypeCounts['Section']) ?></dd></div>
                    <div><dt>Schedule pairs</dt><dd><?= dashboardEscape(count($conflictOverview['allConflicts'])) ?></dd></div>
                </dl>
                <?php if ($conflictOverview['conflicts'] === []): ?>
                    <p class="admin-empty"><?= $conflictOverview['allConflicts'] === [] ? 'No conflicts detected. Active schedules have no teacher, room, or section overlaps.' : 'No conflicts match this type filter.' ?></p>
                <?php else: ?>
                    <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Schedule conflicts; scroll horizontally to view all columns">
                        <table class="admin-table">
                            <caption>Overlapping schedule pairs</caption>
                            <thead><tr><th scope="col">Type</th><th scope="col">Day and time</th><th scope="col">Schedule 1</th><th scope="col">Schedule 2</th></tr></thead>
                            <tbody>
                                <?php foreach ($conflictOverview['conflicts'] as $conflict): ?>
                                    <?php
                                    $left = $conflict['left'];
                                    $right = $conflict['right'];
                                    ?>
                                    <tr>
                                        <td><?= dashboardEscape(implode(', ', $conflict['types'])) ?></td>
                                        <th scope="row"><?= dashboardEscape($left['day_of_week'] . ', ' . date('g:i A', strtotime((string) $left['start_time'])) . '–' . date('g:i A', strtotime((string) $left['end_time']))) ?></th>
                                        <td><?= dashboardEscape($left['subject_name'] . ' · ' . $left['teacher_first_name'] . ' ' . $left['teacher_last_name'] . ' · ' . $left['section_name'] . ' · ' . $left['room_name']) ?></td>
                                        <td><?= dashboardEscape($right['subject_name'] . ' · ' . $right['teacher_first_name'] . ' ' . $right['teacher_last_name'] . ' · ' . $right['section_name'] . ' · ' . $right['room_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($sectionKey === 'academic-management' && is_array($academicOverview)): ?>
        <section class="curriculum-browser" aria-labelledby="curriculum-browser-title">
            <?php if ($academicOverview['selectedCurriculumProgramId'] !== null): ?>
                <?php
                $detailProgram = $academicOverview['programs'][0] ?? null;
                $detailYears = [];
                foreach ($academicOverview['curriculumRows'] as $row) {
                    $academicYearId = (int) $row['academic_year_id'];
                    $yearLevel = (int) $row['year_level'];
                    $semesterKey = $row['semester_number'] === null
                        ? 'unspecified'
                        : (string) (int) $row['semester_number'];
                    if (!isset($detailYears[$academicYearId])) {
                        $detailYears[$academicYearId] = [
                            'name' => $row['year_name'],
                            'status' => $row['academic_year_status'],
                            'subjectCount' => 0,
                            'levels' => [],
                        ];
                    }
                    $detailYears[$academicYearId]['subjectCount']++;
                    $detailYears[$academicYearId]['levels'][$yearLevel][$semesterKey][] = $row;
                }
                ?>
                <?php if ($detailProgram === null): ?>
                    <div class="admin-panel curriculum-detail-empty">
                        <h2 id="curriculum-browser-title">Curriculum unavailable</h2>
                        <p class="admin-empty">This program could not be found. Return to the curriculum list and select another program.</p>
                        <a class="admin-button admin-button--secondary" href="admin-section.php?section=academic-management&amp;academic_view=curriculum">Back to all curricula</a>
                    </div>
                <?php else: ?>
                    <?php
                    $detailProgramId = (int) $detailProgram['program_id'];
                    $detailProgramName = trim((string) $detailProgram['program_code']) !== ''
                        ? $detailProgram['program_code'] . ' — ' . $detailProgram['program_name']
                        : $detailProgram['program_name'];
                    ?>
                    <header class="admin-panel curriculum-detail-heading">
                        <div>
                            <a class="curriculum-detail-back" href="admin-section.php?section=academic-management&amp;academic_view=curriculum">Back to all curricula</a>
                            <h2 id="curriculum-browser-title"><?= dashboardEscape($detailProgramName) ?></h2>
                            <p class="admin-panel-description">Curriculum details by academic year, year level, and semester.</p>
                        </div>
                        <a class="admin-button" href="#admin-form-curriculum">Add subject to curriculum</a>
                    </header>

                    <?php if ($detailYears === []): ?>
                        <div class="admin-panel curriculum-detail-empty">
                            <p class="admin-empty">No curriculum entries are recorded for this program yet. Add a subject to start building this curriculum.</p>
                            <a class="admin-button" href="#admin-form-curriculum">Add subject to curriculum</a>
                        </div>
                    <?php else: ?>
                        <p class="admin-notice admin-notice--info curriculum-detail-guidance">
                            Removing a row removes only that subject’s entry from this program’s curriculum. The subject remains in the shared catalog and any other curricula.
                        </p>
                        <div class="curriculum-detail-years">
                            <?php foreach ($detailYears as $academicYearId => $academicYear): ?>
                                <article class="admin-panel curriculum-detail-year">
                                    <header class="curriculum-detail-year-heading">
                                        <div>
                                            <h3><?= dashboardEscape($academicYear['name']) ?></h3>
                                            <p class="admin-panel-description">Academic year</p>
                                        </div>
                                        <span class="curriculum-detail-count"><?= dashboardEscape(number_format($academicYear['subjectCount'])) ?>
                                            <?= $academicYear['subjectCount'] === 1 ? 'curriculum subject' : 'curriculum subjects' ?></span>
                                    </header>

                                    <?php foreach ($academicYear['levels'] as $yearLevel => $semesters): ?>
                                        <section class="curriculum-detail-level" aria-labelledby="curriculum-level-<?= dashboardEscape($academicYearId) ?>-<?= dashboardEscape((int) $yearLevel) ?>">
                                            <h4 id="curriculum-level-<?= dashboardEscape($academicYearId) ?>-<?= dashboardEscape((int) $yearLevel) ?>">
                                                Year level <?= dashboardEscape((int) $yearLevel) ?>
                                            </h4>
                                            <?php foreach ($semesters as $semesterKey => $curriculumSubjects): ?>
                                                <?php
                                                $semesterLabel = $semesterKey === 'unspecified'
                                                    ? 'Semester not specified'
                                                    : 'Semester ' . (int) $semesterKey;
                                                $tableCaption = $academicYear['name'] . ', year level ' . (int) $yearLevel . ', ' . strtolower($semesterLabel);
                                                ?>
                                                <section class="curriculum-detail-semester" aria-labelledby="curriculum-semester-<?= dashboardEscape($academicYearId) ?>-<?= dashboardEscape((int) $yearLevel) ?>-<?= dashboardEscape($semesterKey) ?>">
                                                    <header class="curriculum-detail-semester-heading">
                                                        <h5 id="curriculum-semester-<?= dashboardEscape($academicYearId) ?>-<?= dashboardEscape((int) $yearLevel) ?>-<?= dashboardEscape($semesterKey) ?>">
                                                            <?= dashboardEscape($semesterLabel) ?>
                                                        </h5>
                                                        <span class="curriculum-detail-count"><?= dashboardEscape(number_format(count($curriculumSubjects))) ?>
                                                            <?= count($curriculumSubjects) === 1 ? 'subject' : 'subjects' ?></span>
                                                    </header>
                                                    <p class="admin-table-scroll-hint">Scroll horizontally to view all columns.</p>
                                                    <div class="admin-table-scroll curriculum-detail-table-scroll" role="group" tabindex="0" aria-label="<?= dashboardEscape($tableCaption) ?> subjects; scroll horizontally to view all columns">
                                                        <table class="admin-table curriculum-detail-table">
                                                            <caption><?= dashboardEscape($tableCaption) ?></caption>
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col">Subject code</th>
                                                                    <th scope="col">Subject name</th>
                                                                    <th scope="col">Type</th>
                                                                    <th scope="col">Units</th>
                                                                    <th scope="col">Requirement</th>
                                                                    <th scope="col">Status</th>
                                                                    <th scope="col">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($curriculumSubjects as $curriculumSubject): ?>
                                                                    <?php
                                                                    $curriculumId = (int) $curriculumSubject['curriculum_id'];
                                                                    $subjectLabel = trim((string) $curriculumSubject['subject_code']) !== ''
                                                                        ? $curriculumSubject['subject_code'] . ' — ' . $curriculumSubject['subject_name']
                                                                        : $curriculumSubject['subject_name'];
                                                                    $recordLabel = $subjectLabel . ' from ' . $detailProgramName . ', ' . $academicYear['name'] . ' curriculum';
                                                                    ?>
                                                                    <tr>
                                                                        <th scope="row"><?= dashboardEscape($curriculumSubject['subject_code']) ?></th>
                                                                        <td>
                                                                            <span class="curriculum-subject-name"><?= dashboardEscape($curriculumSubject['subject_name']) ?></span>
                                                                            <?php if (trim((string) $curriculumSubject['description']) !== ''): ?>
                                                                                <span class="curriculum-subject-description"><?= dashboardEscape($curriculumSubject['description']) ?></span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td><?= dashboardEscape($curriculumSubject['subject_type']) ?></td>
                                                                        <td><?= dashboardEscape($curriculumSubject['units']) ?></td>
                                                                        <td><?= (int) $curriculumSubject['is_required'] === 1 ? 'Required' : 'Elective' ?></td>
                                                                        <td><?= dashboardEscape($curriculumSubject['subject_status']) ?></td>
                                                                        <td>
                                                                            <div class="admin-row-actions">
                                                                                <a class="admin-button admin-button--secondary" aria-label="Edit <?= dashboardEscape($subjectLabel) ?> curriculum entry" href="admin-section.php?section=academic-management&amp;academic_view=curriculum&amp;program_id=<?= dashboardEscape($detailProgramId) ?>&amp;curriculum_program_id=<?= dashboardEscape($detailProgramId) ?>&amp;edit=curriculum:<?= dashboardEscape($curriculumId) ?>">Edit</a>
                                                                                <form method="post" data-confirm-delete data-record-label="<?= dashboardEscape($recordLabel) ?>" data-delete-label="Remove from curriculum">
                                                                                    <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                                                    <input type="hidden" name="action" value="delete">
                                                                                    <input type="hidden" name="entity" value="curriculum">
                                                                                    <input type="hidden" name="record_id" value="<?= dashboardEscape($curriculumId) ?>">
                                                                                    <input type="hidden" name="academic_view" value="curriculum">
                                                                                    <input type="hidden" name="return_program_id" value="<?= dashboardEscape($detailProgramId) ?>">
                                                                                    <button class="admin-button admin-button--danger" type="submit" aria-label="Remove <?= dashboardEscape($subjectLabel) ?> from this curriculum">Remove</button>
                                                                                </form>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </section>
                                            <?php endforeach; ?>
                                        </section>
                                    <?php endforeach; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
            <div class="admin-panel curriculum-browser-heading">
                <div>
                    <h2 id="curriculum-browser-title">Browse curriculum</h2>
                    <p class="admin-panel-description">
                        Choose a program folder to browse subjects by year level and semester.
                    </p>
                </div>
                <a class="admin-button" href="#admin-form-curriculum">Add curriculum</a>
            </div>
            <p class="admin-notice admin-notice--info curriculum-browser-guidance">
                Open a program folder to review its curriculum by academic year, year level, and semester. Edit or remove individual curriculum entries without changing the shared subject catalog.
                Subject, year-level, and semester totals include all recorded curriculum years.
                <?php if ($academicOverview['activeYear'] !== null): ?>
                    Current active year: <?= dashboardEscape($academicOverview['activeYear']['year_name']) ?>.
                <?php else: ?>
                    No academic year is currently active.
                <?php endif; ?>
            </p>
            <div class="admin-panel curriculum-filter">
                <form class="admin-form" method="get" action="admin-section.php" data-auto-submit-on-change>
                    <input type="hidden" name="section" value="academic-management">
                    <input type="hidden" name="academic_view" value="curriculum">
                    <div class="admin-form-grid">
                        <div class="admin-field">
                            <label for="curriculum-program-filter">Program</label>
                            <select id="curriculum-program-filter" name="program_id" aria-describedby="curriculum-filter-hint">
                                <option value="">All programs</option>
                                <?php foreach ($academicOverview['programOptions'] as $program): ?>
                                    <?php
                                    $programId = (int) $program['program_id'];
                                    $programName = trim((string) $program['program_code']) !== ''
                                        ? $program['program_code'] . ' — ' . $program['program_name']
                                        : $program['program_name'];
                                    ?>
                                    <option value="<?= dashboardEscape($programId) ?>"<?= $academicOverview['selectedProgramId'] === $programId ? ' selected' : '' ?>>
                                        <?= dashboardEscape($programName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="admin-field-hint" id="curriculum-filter-hint">Results update when you choose a program.</span>
                        </div>
                    </div>
                    <div class="admin-form-actions">
                        <button class="admin-button" type="submit" data-auto-submit-fallback>Apply filter</button>
                    </div>
                </form>
            </div>

            <?php if ($academicOverview['programs'] === []): ?>
                <p class="admin-empty">No programs match this filter.</p>
            <?php else: ?>
                <div class="curriculum-program-grid">
                    <?php foreach ($academicOverview['programs'] as $program): ?>
                        <?php
                        $programId = (int) $program['program_id'];
                        $programRows = array_values(array_filter(
                            $academicOverview['curriculumRows'],
                            static fn (array $row): bool => (int) $row['program_id'] === $programId
                        ));
                        ?>
                        <article class="admin-panel curriculum-program-card" aria-labelledby="curriculum-program-title-<?= dashboardEscape($programId) ?>">
                            <div class="curriculum-program-card-header">
                                <span class="curriculum-folder-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" focusable="false">
                                        <path d="M3.5 6.75A1.75 1.75 0 0 1 5.25 5h4.1l1.8 2h7.6a1.75 1.75 0 0 1 1.75 1.75v9.5A1.75 1.75 0 0 1 18.75 20H5.25A1.75 1.75 0 0 1 3.5 18.25v-11.5Z" />
                                        <path d="M3.75 9h16.5" />
                                    </svg>
                                </span>
                                <?php if (trim((string) $program['program_code']) !== ''): ?>
                                    <span class="curriculum-program-code"><?= dashboardEscape($program['program_code']) ?></span>
                                <?php endif; ?>
                                <span class="curriculum-program-status<?= strtolower((string) $program['status']) === 'active' ? ' is-active' : '' ?>">
                                    <?= dashboardEscape($program['status']) ?>
                                </span>
                            </div>
                            <h3 id="curriculum-program-title-<?= dashboardEscape($programId) ?>">
                                <?= dashboardEscape($program['program_name']) ?>
                            </h3>
                            <p class="curriculum-program-subtitle">Curriculum structure</p>
                            <dl class="curriculum-program-stats">
                                <div>
                                    <dt>Subjects</dt>
                                    <dd><?= dashboardEscape(number_format((int) $program['subject_count'])) ?></dd>
                                </div>
                                <div>
                                    <dt>Year levels</dt>
                                    <dd><?= dashboardEscape(number_format((int) $program['year_level_count'])) ?></dd>
                                </div>
                                <div>
                                    <dt>Semesters</dt>
                                    <dd><?= dashboardEscape(number_format((int) $program['semester_count'])) ?></dd>
                                </div>
                            </dl>
                            <div class="curriculum-program-card-footer">
                                <a class="curriculum-folder-link" href="admin-section.php?section=academic-management&amp;academic_view=curriculum&amp;program_id=<?= dashboardEscape($programId) ?>&amp;curriculum_program_id=<?= dashboardEscape($programId) ?>">Open curriculum folder</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($sectionKey === 'academic-management' && $academicView === 'subjects' && is_array($subjectsCatalog)): ?>
        <?php
        $subjectGroups = [];
        $unassignedSubjects = [];
        foreach ($subjectsCatalog['rows'] as $subject) {
            if ($subject['program_id'] === null) {
                $unassignedSubjects[] = $subject;
                continue;
            }

            $programId = (int) $subject['program_id'];
            $yearLevel = (int) $subject['year_level'];
            $semesterKey = $subject['semester_number'] === null
                ? 'unspecified'
                : (string) (int) $subject['semester_number'];
            if (!isset($subjectGroups[$programId])) {
                $subjectGroups[$programId] = [
                    'code' => $subject['program_code'],
                    'name' => $subject['program_name'],
                    'subjectCount' => 0,
                    'years' => [],
                ];
            }
            $subjectGroups[$programId]['subjectCount']++;
            $subjectGroups[$programId]['years'][$yearLevel][$semesterKey][] = $subject;
        }
        $subjectOrdinal = static function (int $number): string {
            $lastTwoDigits = $number % 100;
            if ($lastTwoDigits >= 11 && $lastTwoDigits <= 13) {
                $suffix = 'th';
            } else {
                $suffix = match ($number % 10) {
                    1 => 'st',
                    2 => 'nd',
                    3 => 'rd',
                    default => 'th',
                };
            }
            return $number . $suffix;
        };
        ?>
        <section class="subject-catalog" aria-labelledby="subject-catalog-title">
            <header class="admin-panel subject-catalog-heading">
                <div>
                    <h2 id="subject-catalog-title">Browse subjects</h2>
                    <p class="admin-panel-description">Explore the catalog by program, year level, and semester.</p>
                </div>
                <a class="admin-button" href="#admin-form-subjects">Add subject</a>
            </header>

            <div class="admin-panel subject-catalog-filters">
                <form class="admin-form" method="get" action="admin-section.php" data-auto-submit-on-change>
                    <input type="hidden" name="section" value="academic-management">
                    <input type="hidden" name="academic_view" value="subjects">
                    <p class="subject-catalog-filter-hint" id="subject-catalog-filter-hint">
                        Changing any filter applies it immediately and reloads the catalog.
                    </p>
                    <div class="subject-catalog-filter-grid">
                        <div class="admin-field">
                            <label for="subject-program-filter">Program</label>
                            <select id="subject-program-filter" name="program_id" aria-describedby="subject-catalog-filter-hint">
                                <option value="">All programs</option>
                                <?php foreach ($subjectsCatalog['programOptions'] as $program): ?>
                                    <?php
                                    $programId = (int) $program['program_id'];
                                    $programLabel = trim((string) $program['program_code']) !== ''
                                        ? $program['program_code'] . ' — ' . $program['program_name']
                                        : $program['program_name'];
                                    ?>
                                    <option value="<?= dashboardEscape($programId) ?>"<?= $subjectsCatalog['selectedProgramId'] === $programId ? ' selected' : '' ?>>
                                        <?= dashboardEscape($programLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-field">
                            <label for="subject-year-filter">Year level</label>
                            <select id="subject-year-filter" name="year_level" aria-describedby="subject-catalog-filter-hint">
                                <option value="">All year levels</option>
                                <?php foreach ($subjectsCatalog['yearOptions'] as $yearLevel): ?>
                                    <?php $yearLevel = (int) $yearLevel; ?>
                                    <option value="<?= dashboardEscape($yearLevel) ?>"<?= $subjectsCatalog['selectedYearLevel'] === $yearLevel ? ' selected' : '' ?>>
                                        <?= dashboardEscape($subjectOrdinal($yearLevel) . ' Year') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-field">
                            <label for="subject-semester-filter">Semester</label>
                            <select id="subject-semester-filter" name="semester_number" aria-describedby="subject-catalog-filter-hint">
                                <option value="">All semesters</option>
                                <?php foreach ($subjectsCatalog['semesterOptions'] as $semesterNumber): ?>
                                    <?php if ($semesterNumber === null): ?>
                                        <option value="unspecified"<?= $subjectsCatalog['selectedSemester'] === 'unspecified' ? ' selected' : '' ?>>Semester not specified</option>
                                    <?php else: ?>
                                        <?php $semesterNumber = (int) $semesterNumber; ?>
                                        <option value="<?= dashboardEscape($semesterNumber) ?>"<?= $subjectsCatalog['selectedSemester'] === $semesterNumber ? ' selected' : '' ?>>
                                            <?= dashboardEscape($subjectOrdinal($semesterNumber) . ' semester') ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="admin-form-actions">
                        <button class="admin-button" type="submit" data-auto-submit-fallback>Apply filters</button>
                    </div>
                </form>
            </div>

            <div class="subject-catalog-results">
                <p><?= dashboardEscape(number_format(count($subjectsCatalog['rows']))) ?>
                    <?= count($subjectsCatalog['rows']) === 1 ? 'subject shown' : 'subjects shown' ?></p>
            </div>

            <?php if ($subjectsCatalog['rows'] === []): ?>
                <p class="admin-empty subject-catalog-empty">No subjects match these filters. Add a subject to start building the catalog.</p>
            <?php else: ?>
                <div class="subject-catalog-groups">
                    <?php foreach ($subjectGroups as $program): ?>
                        <details class="subject-catalog-program">
                            <summary>
                                <span class="subject-catalog-group-name">
                                    <?php if (trim((string) $program['code']) !== ''): ?>
                                        <span><?= dashboardEscape($program['code']) ?></span>
                                    <?php endif; ?>
                                    <?= dashboardEscape($program['name']) ?>
                                </span>
                                <span class="subject-catalog-count"><?= dashboardEscape(number_format($program['subjectCount'])) ?>
                                    <?= $program['subjectCount'] === 1 ? 'subject' : 'subjects' ?></span>
                            </summary>
                            <h3 class="sr-only"><?= dashboardEscape($program['name']) ?></h3>
                            <div class="subject-catalog-years">
                                <?php foreach ($program['years'] as $yearLevel => $semesters): ?>
                                    <?php $yearSubjectCount = array_sum(array_map('count', $semesters)); ?>
                                    <details class="subject-catalog-year">
                                        <summary>
                                            <span><?= dashboardEscape($subjectOrdinal((int) $yearLevel) . ' Year') ?></span>
                                            <span class="subject-catalog-count"><?= dashboardEscape(number_format($yearSubjectCount)) ?>
                                                <?= $yearSubjectCount === 1 ? 'subject' : 'subjects' ?></span>
                                        </summary>
                                        <h4 class="sr-only"><?= dashboardEscape($subjectOrdinal((int) $yearLevel) . ' Year') ?></h4>
                                        <div class="subject-catalog-semesters">
                                            <?php foreach ($semesters as $semesterKey => $subjects): ?>
                                                <?php
                                                $semesterLabel = $semesterKey === 'unspecified'
                                                    ? 'Semester not specified'
                                                    : $subjectOrdinal((int) $semesterKey) . ' semester';
                                                ?>
                                                <details class="subject-catalog-semester">
                                                    <summary>
                                                        <span><?= dashboardEscape($semesterLabel) ?></span>
                                                        <span class="subject-catalog-count"><?= dashboardEscape(number_format(count($subjects))) ?>
                                                            <?= count($subjects) === 1 ? 'subject' : 'subjects' ?></span>
                                                    </summary>
                                                    <h5 class="sr-only"><?= dashboardEscape($semesterLabel) ?></h5>
                                                    <div class="subject-catalog-card-grid">
                                                        <?php foreach ($subjects as $subject): ?>
                                                            <?php
                                                            $subjectId = (int) $subject['subject_id'];
                                                            $subjectLabel = trim((string) $subject['subject_code']) !== ''
                                                                ? $subject['subject_code'] . ' — ' . $subject['subject_name']
                                                                : $subject['subject_name'];
                                                            ?>
                                                            <article class="subject-catalog-card">
                                                                <div class="subject-catalog-card-topline">
                                                                    <span class="subject-catalog-code"><?= dashboardEscape($subject['subject_code']) ?></span>
                                                                    <?php if (trim((string) $subject['subject_type']) !== ''): ?>
                                                                        <span class="subject-catalog-type"><?= dashboardEscape($subject['subject_type']) ?></span>
                                                                    <?php endif; ?>
                                                                    <span class="subject-catalog-status<?= strtolower((string) $subject['status']) === 'active' ? ' is-active' : '' ?>">
                                                                        <?= dashboardEscape($subject['status']) ?>
                                                                    </span>
                                                                </div>
                                                                <h6><?= dashboardEscape($subject['subject_name']) ?></h6>
                                                                <?php if (trim((string) $subject['description']) !== ''): ?>
                                                                    <p class="subject-catalog-description"><?= dashboardEscape($subject['description']) ?></p>
                                                                <?php endif; ?>
                                                                <dl class="subject-catalog-metrics">
                                                                    <div><dt>Units</dt><dd><?= dashboardEscape($subject['units']) ?></dd></div>
                                                                    <div><dt>Classes</dt><dd><?= dashboardEscape(number_format((int) $subject['class_count'])) ?></dd></div>
                                                                    <div><dt>Curriculum entries</dt><dd><?= dashboardEscape(number_format((int) $subject['curriculum_count'])) ?></dd></div>
                                                                </dl>
                                                                <div class="subject-catalog-card-footer">
                                                                    <span class="subject-catalog-requirement"><?= (int) $subject['is_required'] === 1 ? 'Required' : 'Elective' ?></span>
                                                                    <div class="admin-row-actions">
                                                                        <a class="admin-button admin-button--secondary" aria-label="Edit <?= dashboardEscape($subjectLabel) ?>" href="admin-section.php?section=academic-management&amp;academic_view=subjects&amp;edit=subjects:<?= dashboardEscape($subjectId) ?>">Edit</a>
                                                                        <form method="post" data-confirm-delete data-record-label="<?= dashboardEscape($subjectLabel) ?>" data-delete-label="<?= dashboardEscape($entityDefinitions['subjects']['delete']['label']) ?>">
                                                                            <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                                            <input type="hidden" name="action" value="delete">
                                                                            <input type="hidden" name="entity" value="subjects">
                                                                            <input type="hidden" name="record_id" value="<?= dashboardEscape($subjectId) ?>">
                                                                            <input type="hidden" name="academic_view" value="subjects">
                                                                            <button class="admin-button admin-button--danger" type="submit" aria-label="<?= dashboardEscape($entityDefinitions['subjects']['delete']['label'] . ' ' . $subjectLabel) ?>"><?= dashboardEscape($entityDefinitions['subjects']['delete']['label']) ?></button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </article>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </details>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>

                    <?php if ($unassignedSubjects !== []): ?>
                        <details class="subject-catalog-program subject-catalog-unassigned">
                            <summary>
                                <span class="subject-catalog-group-name">Not assigned to a curriculum</span>
                                <span class="subject-catalog-count"><?= dashboardEscape(number_format(count($unassignedSubjects))) ?>
                                    <?= count($unassignedSubjects) === 1 ? 'subject' : 'subjects' ?></span>
                            </summary>
                            <h3 class="sr-only">Not assigned to a curriculum</h3>
                            <div class="subject-catalog-card-grid subject-catalog-unassigned-grid">
                                <?php foreach ($unassignedSubjects as $subject): ?>
                                    <?php
                                    $subjectId = (int) $subject['subject_id'];
                                    $subjectLabel = trim((string) $subject['subject_code']) !== ''
                                        ? $subject['subject_code'] . ' — ' . $subject['subject_name']
                                        : $subject['subject_name'];
                                    ?>
                                    <article class="subject-catalog-card">
                                        <div class="subject-catalog-card-topline">
                                            <span class="subject-catalog-code"><?= dashboardEscape($subject['subject_code']) ?></span>
                                            <?php if (trim((string) $subject['subject_type']) !== ''): ?>
                                                <span class="subject-catalog-type"><?= dashboardEscape($subject['subject_type']) ?></span>
                                            <?php endif; ?>
                                            <span class="subject-catalog-status<?= strtolower((string) $subject['status']) === 'active' ? ' is-active' : '' ?>">
                                                <?= dashboardEscape($subject['status']) ?>
                                            </span>
                                        </div>
                                        <h4><?= dashboardEscape($subject['subject_name']) ?></h4>
                                        <?php if (trim((string) $subject['description']) !== ''): ?>
                                            <p class="subject-catalog-description"><?= dashboardEscape($subject['description']) ?></p>
                                        <?php endif; ?>
                                        <dl class="subject-catalog-metrics">
                                            <div><dt>Units</dt><dd><?= dashboardEscape($subject['units']) ?></dd></div>
                                            <div><dt>Classes</dt><dd>0</dd></div>
                                            <div><dt>Curriculum entries</dt><dd>0</dd></div>
                                        </dl>
                                        <div class="subject-catalog-card-footer">
                                            <span class="subject-catalog-requirement">Not assigned</span>
                                            <div class="admin-row-actions">
                                                <a class="admin-button admin-button--secondary" aria-label="Edit <?= dashboardEscape($subjectLabel) ?>" href="admin-section.php?section=academic-management&amp;academic_view=subjects&amp;edit=subjects:<?= dashboardEscape($subjectId) ?>">Edit</a>
                                                <form method="post" data-confirm-delete data-record-label="<?= dashboardEscape($subjectLabel) ?>" data-delete-label="<?= dashboardEscape($entityDefinitions['subjects']['delete']['label']) ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="entity" value="subjects">
                                                    <input type="hidden" name="record_id" value="<?= dashboardEscape($subjectId) ?>">
                                                    <input type="hidden" name="academic_view" value="subjects">
                                                    <button class="admin-button admin-button--danger" type="submit" aria-label="<?= dashboardEscape($entityDefinitions['subjects']['delete']['label'] . ' ' . $subjectLabel) ?>"><?= dashboardEscape($entityDefinitions['subjects']['delete']['label']) ?></button>
                                                </form>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($sectionKey === 'reports'): ?>
        <div class="admin-panel">
            <h2>School overview</h2>
            <p class="admin-panel-description">Live totals from the school database. Amounts are shown in Philippine pesos (PHP).</p>
            <dl class="admin-status-list">
                <div><dt>Student records</dt><dd><?= dashboardEscape(number_format((int) $reportData['students'])) ?></dd></div>
                <div><dt>Teacher records</dt><dd><?= dashboardEscape(number_format((int) $reportData['teachers'])) ?></dd></div>
                <div><dt>Enrollment records</dt><dd><?= dashboardEscape(number_format((int) $reportData['enrollments'])) ?></dd></div>
                <div><dt>Attendance records</dt><dd><?= dashboardEscape(number_format((int) $reportData['attendance'])) ?></dd></div>
                <div><dt>Assessments</dt><dd><?= dashboardEscape(number_format((int) $reportData['assessments'])) ?></dd></div>
                <div><dt>Completed payments</dt><dd><?= dashboardEscape(number_format((int) $reportData['payments'])) ?></dd></div>
                <div><dt>Completed-payment collections</dt><dd><?= dashboardEscape(dashboardFormatCurrency((float) $reportData['collections'])) ?></dd></div>
            </dl>
        </div>
        <div class="admin-panel">
            <h2>Generated report history</h2>
            <?php if ($reportData['generatedReports'] === []): ?>
                <p class="admin-empty">No generated reports are recorded.</p>
            <?php else: ?>
                <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                <div class="admin-table-scroll" role="group" tabindex="0" aria-label="Generated report history; scroll horizontally to view all columns">
                    <table class="admin-table">
                        <caption>Reports recorded by the system</caption>
                        <thead><tr><th scope="col">Report</th><th scope="col">Type</th><th scope="col">Generated</th></tr></thead>
                        <tbody>
                            <?php foreach ($reportData['generatedReports'] as $report): ?>
                                <tr>
                                    <th scope="row"><?= dashboardEscape($report['report_name']) ?></th>
                                    <td><?= dashboardEscape($report['report_type']) ?></td>
                                    <td><?= dashboardEscape($report['generated_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <p class="admin-panel-description">This page reports current database totals; it does not create downloadable report files.</p>
        </div>
    <?php else: ?>
        <?php if ($sectionKey === 'finance-payments' && $financeView === 'payment-records'): ?>
            <p class="admin-notice admin-notice--info">Payment records are retained for audit. The row action cancels a payment instead of deleting its financial history.</p>
        <?php elseif ($sectionKey === 'students' || $sectionKey === 'teachers'): ?>
            <p class="admin-notice admin-notice--info">Deactivate records instead of permanently deleting them, so linked enrollments, grades, attendance, and history remain intact.</p>
        <?php elseif ($sectionKey === 'announcements'): ?>
            <p class="admin-notice admin-notice--info">Published announcements appear on the dashboards of signed-in school users. Draft announcements remain unpublished.</p>
        <?php endif; ?>

        <?php foreach ($entitiesForSection as $entityKey => $entity): ?>
            <?php
            $isEditing = $editingEntity !== null
                && $editingEntity['table'] === $entity['table']
                && is_array($editingRecord);
            $entityFormValues = $isEditing ? $editingRecord : [];
            if (
                !$isEditing
                && $entityKey === 'curriculum'
                && is_array($academicOverview)
                && $academicOverview['selectedProgramId'] !== null
            ) {
                $entityFormValues['program_id'] = $academicOverview['selectedProgramId'];
            }
            if ($oldInputEntity === $entityKey) {
                $entityFormValues = array_merge($entityFormValues, $oldInput);
            }
            ?>
            <?php
            $modalOpenOnLoad = $isEditing || (
                $_SERVER['REQUEST_METHOD'] === 'POST'
                && ($_POST['action'] ?? '') === 'save'
                && ($_POST['entity'] ?? '') === $entityKey
                && $errorMessage !== ''
            );
            $modalTriggerLabel = $isEditing
                ? 'Edit ' . strtolower($entity['label'])
                : 'Add ' . strtolower(rtrim($entity['label'], 's'));
            ?>
            <div class="admin-panel" data-form-modal data-modal-trigger-label="<?= dashboardEscape($modalTriggerLabel) ?>" data-modal-trigger-class="admin-button" data-modal-open-on-load="<?= $modalOpenOnLoad ? 'true' : 'false' ?>"<?= $entityKey === 'curriculum' ? ' id="admin-form-curriculum"' : ($entityKey === 'subjects' ? ' id="admin-form-subjects"' : '') ?>>
                <h2><?= $isEditing ? 'Edit ' . dashboardEscape(strtolower($entity['label'])) : 'Add ' . dashboardEscape(strtolower(rtrim($entity['label'], 's'))) ?></h2>
                <form class="admin-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="entity" value="<?= dashboardEscape($entityKey) ?>">
                    <?php if ($sectionKey === 'academic-management'): ?>
                        <input type="hidden" name="academic_view" value="<?= dashboardEscape($academicView) ?>">
                        <?php if ($entityKey === 'curriculum' && $academicOverview['selectedCurriculumProgramId'] !== null): ?>
                            <input type="hidden" name="return_program_id" value="<?= dashboardEscape($academicOverview['selectedCurriculumProgramId']) ?>">
                        <?php endif; ?>
                    <?php elseif ($sectionKey === 'attendance-scheduling'): ?>
                        <input type="hidden" name="attendance_view" value="<?= dashboardEscape($attendanceView) ?>">
                    <?php elseif ($sectionKey === 'assessment-grading'): ?>
                        <input type="hidden" name="assessment_view" value="<?= dashboardEscape($assessmentView) ?>">
                    <?php elseif ($sectionKey === 'finance-payments'): ?>
                        <input type="hidden" name="finance_view" value="<?= dashboardEscape($financeView) ?>">
                    <?php endif; ?>
                    <?php if ($isEditing): ?>
                        <input type="hidden" name="record_id" value="<?= dashboardEscape((int) $editingRecord[$entity['primaryKey']]) ?>">
                    <?php endif; ?>
                    <p class="admin-field-required-note"><span class="admin-required-marker">Required</span> fields must be completed.</p>
                    <div class="admin-form-grid">
                        <?php foreach ($entity['fields'] as $name => $definition): ?>
                            <?php if ($entityKey === 'payments' && $name === 'student_fee_id'): ?>
                                <fieldset class="admin-form-fieldset admin-field--wide">
                                    <legend>Fee and term assignment</legend>
                                    <p class="admin-field-hint" id="admin-payments-tuition-hint">Choose a fee to link this payment to it. Tuition fees also require a term; leave the term blank for other fee categories or unassigned payments.</p>
                                    <div class="admin-form-grid">
                            <?php endif; ?>
                            <?php
                            $inputId = 'admin-' . $entityKey . '-' . $name;
                            $errorId = $inputId . '-error';
                            $rawValue = $entityFormValues[$name] ?? '';
                            if ($definition['type'] === 'datetime-local' && is_string($rawValue)) {
                                $rawValue = str_replace(' ', 'T', substr($rawValue, 0, 16));
                            } elseif ($definition['type'] === 'time' && is_string($rawValue)) {
                                $rawValue = substr($rawValue, 0, 5);
                            }
                            $value = is_scalar($rawValue) ? (string) $rawValue : '';
                            $error = $fieldError($name);
                            $descriptionIds = [];
                            if ($entityKey === 'payments' && in_array($name, ['student_fee_id', 'payment_term'], true)) {
                                $descriptionIds[] = 'admin-payments-tuition-hint';
                            }
                            if ($error !== '') {
                                $descriptionIds[] = $errorId;
                            }
                            $describedBy = $descriptionIds !== []
                                ? ' aria-describedby="' . dashboardEscape(implode(' ', $descriptionIds)) . '"'
                                : '';
                            $invalidAttribute = $error !== '' ? ' aria-invalid="true"' : '';
                            ?>
                            <div class="admin-field<?= $definition['type'] === 'textarea' ? ' admin-field--wide' : '' ?>">
                                <label for="<?= dashboardEscape($inputId) ?>">
                                    <?= dashboardEscape($definition['label']) ?>
                                    <?php if ($definition['required']): ?><span class="admin-required-marker">Required</span><?php endif; ?>
                                </label>
                                <?php if ($definition['type'] === 'textarea'): ?>
                                    <textarea id="<?= dashboardEscape($inputId) ?>" name="<?= dashboardEscape($name) ?>" rows="3"<?= $definition['required'] ? ' required' : '' ?><?= $invalidAttribute ?><?= $describedBy ?><?= $error !== '' ? ' autofocus' : '' ?>><?= dashboardEscape($value) ?></textarea>
                                <?php elseif ($definition['type'] === 'select'): ?>
                                    <?php
                                    $options = is_array($definition['options'])
                                        ? $definition['options']
                                        : ($lookupLabels[$definition['options']] ?? []);
                                    ?>
                                    <select id="<?= dashboardEscape($inputId) ?>" name="<?= dashboardEscape($name) ?>"<?= $definition['required'] ? ' required' : '' ?><?= $invalidAttribute ?><?= $describedBy ?><?= $error !== '' ? ' autofocus' : '' ?>>
                                        <option value="">Select <?= dashboardEscape(strtolower($definition['label'])) ?></option>
                                        <?php foreach ($options as $optionValue => $optionLabel): ?>
                                            <option value="<?= dashboardEscape((string) $optionValue) ?>"<?= $value === (string) $optionValue ? ' selected' : '' ?>><?= dashboardEscape((string) $optionLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input
                                        id="<?= dashboardEscape($inputId) ?>"
                                        name="<?= dashboardEscape($name) ?>"
                                        type="<?= dashboardEscape($definition['type']) ?>"
                                        value="<?= dashboardEscape($value) ?>"
                                        <?= $definition['required'] ? 'required' : '' ?>
                                        <?= $definition['type'] === 'number' ? 'step="any"' : '' ?>
                                        <?= $invalidAttribute ?>
                                        <?= $describedBy ?>
                                        <?= $error !== '' ? 'autofocus' : '' ?>
                                    >
                                <?php endif; ?>
                                <?php if ($error !== ''): ?>
                                    <span class="admin-field-error" id="<?= dashboardEscape($errorId) ?>"><?= dashboardEscape($error) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($entityKey === 'payments' && $name === 'payment_term'): ?>
                                    </div>
                                </fieldset>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="admin-form-actions">
                        <button class="admin-button" type="submit"><?= $isEditing ? 'Save changes' : 'Add record' ?></button>
                        <?php if ($isEditing): ?>
                            <a class="admin-button admin-button--secondary" href="admin-section.php?section=<?= dashboardEscape(rawurlencode($sectionKey)) ?><?= $sectionKey === 'academic-management' ? '&amp;academic_view=' . dashboardEscape(rawurlencode($academicView)) . ($entityKey === 'curriculum' && $returnProgramId !== null ? '&amp;program_id=' . $returnProgramId . '&amp;curriculum_program_id=' . $returnProgramId : '') : ($sectionKey === 'attendance-scheduling' ? '&amp;attendance_view=' . dashboardEscape(rawurlencode($attendanceView)) : ($sectionKey === 'assessment-grading' ? '&amp;assessment_view=' . dashboardEscape(rawurlencode($assessmentView)) : ($sectionKey === 'finance-payments' ? '&amp;finance_view=' . dashboardEscape(rawurlencode($financeView)) : ''))) ?>">Cancel editing</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if ($entityKey !== 'subjects' && $entityKey !== 'curriculum'): ?>
                <div class="admin-panel">
                    <h2><?= dashboardEscape($entity['label']) ?></h2>
                    <?php if (($entityRows[$entityKey] ?? []) === []): ?>
                        <p class="admin-empty">No records are available yet.</p>
                    <?php else: ?>
                        <p class="admin-table-scroll-hint">If needed, scroll horizontally to view all columns.</p>
                        <div class="admin-table-scroll" role="group" tabindex="0" aria-label="<?= dashboardEscape($entity['label']) ?> table; scroll horizontally to view all columns">
                        <table class="admin-table">
                            <caption><?= dashboardEscape($entity['label']) ?>; showing up to 100 most recent records</caption>
                            <thead>
                                <tr>
                                    <?php foreach ($entity['fields'] as $definition): ?>
                                        <th scope="col"><?= dashboardEscape($definition['label']) ?></th>
                                    <?php endforeach; ?>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entityRows[$entityKey] as $record): ?>
                                    <?php
                                    $recordLabel = '';
                                    foreach (['student_number', 'employee_number', 'year_name', 'department_name', 'program_name', 'section_name', 'subject_name', 'assessment_title', 'receipt_number', 'title'] as $labelField) {
                                        if (isset($record[$labelField]) && is_string($record[$labelField]) && $record[$labelField] !== '') {
                                            $recordLabel = $record[$labelField];
                                            break;
                                        }
                                    }
                                    if ($recordLabel === '') {
                                        $recordLabel = $entity['label'] . ' ' . (int) $record[$entity['primaryKey']];
                                    }
                                    ?>
                                    <tr>
                                        <?php foreach ($entity['fields'] as $name => $definition): ?>
                                            <?php
                                            $cellValue = $record[$name] ?? '';
                                            if (is_string($definition['options']) && $cellValue !== '' && isset($lookupLabels[$definition['options']][(string) $cellValue])) {
                                                $cellValue = $lookupLabels[$definition['options']][(string) $cellValue];
                                            }
                                            if ($name === 'total_amount' && is_numeric($cellValue)) {
                                                $cellValue = dashboardFormatCurrency((float) $cellValue);
                                            }
                                            ?>
                                            <td><?= dashboardEscape(is_scalar($cellValue) ? (string) $cellValue : '') ?></td>
                                        <?php endforeach; ?>
                                        <td>
                                            <div class="admin-row-actions">
                                                <a class="admin-button admin-button--secondary" href="admin-section.php?section=<?= dashboardEscape(rawurlencode($sectionKey)) ?><?= $sectionKey === 'academic-management' ? '&amp;academic_view=' . dashboardEscape(rawurlencode($academicView)) : ($sectionKey === 'attendance-scheduling' ? '&amp;attendance_view=' . dashboardEscape(rawurlencode($attendanceView)) : ($sectionKey === 'assessment-grading' ? '&amp;assessment_view=' . dashboardEscape(rawurlencode($assessmentView)) : ($sectionKey === 'finance-payments' ? '&amp;finance_view=' . dashboardEscape(rawurlencode($financeView)) : ''))) ?>&amp;edit=<?= dashboardEscape($entityKey . ':' . (int) $record[$entity['primaryKey']]) ?>">Edit</a>
                                                <form method="post" data-confirm-delete data-record-label="<?= dashboardEscape($recordLabel) ?>" data-delete-label="<?= dashboardEscape($entity['delete']['label']) ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= dashboardEscape($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="entity" value="<?= dashboardEscape($entityKey) ?>">
                                                    <input type="hidden" name="record_id" value="<?= dashboardEscape((int) $record[$entity['primaryKey']]) ?>">
                                                    <?php if ($sectionKey === 'academic-management'): ?>
                                                        <input type="hidden" name="academic_view" value="<?= dashboardEscape($academicView) ?>">
                                                    <?php elseif ($sectionKey === 'attendance-scheduling'): ?>
                                                        <input type="hidden" name="attendance_view" value="<?= dashboardEscape($attendanceView) ?>">
                                                    <?php elseif ($sectionKey === 'assessment-grading'): ?>
                                                        <input type="hidden" name="assessment_view" value="<?= dashboardEscape($assessmentView) ?>">
                                                    <?php elseif ($sectionKey === 'finance-payments'): ?>
                                                        <input type="hidden" name="finance_view" value="<?= dashboardEscape($financeView) ?>">
                                                    <?php endif; ?>
                                                    <button class="admin-button admin-button--danger" type="submit"><?= dashboardEscape($entity['delete']['label']) ?></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
