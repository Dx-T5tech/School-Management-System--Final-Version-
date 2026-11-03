ALTER TABLE rooms
    MODIFY room_type ENUM(
        'Classroom',
        'Laboratory',
        'Computer Laboratory',
        'Library',
        'Auditorium',
        'Other',
        'Facility'
    ) DEFAULT 'Classroom';

INSERT INTO rooms (room_code, room_name, building, floor_number, room_type, capacity, status) VALUES
    ('A-1-LAB-1', 'Lab 1', 'A', 1, 'Laboratory', 40, 'Available'),
    ('A-1-CASHIER', 'Cashier', 'A', 1, 'Facility', NULL, 'Available'),
    ('A-1-MIS', 'MIS', 'A', 1, 'Facility', NULL, 'Available'),
    ('A-1-MENS-CR', 'Men''s Restroom', 'A', 1, 'Facility', NULL, 'Available'),
    ('A-1-LAB-2', 'Lab 2', 'A', 1, 'Laboratory', 40, 'Available'),
    ('A-1-ADMIN', 'Admin', 'A', 1, 'Facility', NULL, 'Available'),
    ('A-2-LIBRARY', 'Library', 'A', 2, 'Facility', NULL, 'Available'),
    ('A-2-ROOM-101', 'Room 101', 'A', 2, 'Classroom', 40, 'Available'),
    ('A-2-WOMENS-CR', 'Women CR', 'A', 2, 'Facility', NULL, 'Available'),
    ('A-2-LAB-3', 'Lab 3', 'A', 2, 'Laboratory', 40, 'Available'),
    ('A-2-ROOM-102', 'Room 102', 'A', 2, 'Classroom', 40, 'Available'),
    ('A-2-ROOM-103', 'Room 103', 'A', 2, 'Classroom', 40, 'Available'),
    ('B-1-MEN-CR', 'Men CR', 'B', 1, 'Facility', NULL, 'Available'),
    ('B-1-WOMEN-CR', 'Women CR', 'B', 1, 'Facility', NULL, 'Available'),
    ('B-1-FACULTY-1', 'Faculty Room', 'B', 1, 'Facility', NULL, 'Available'),
    ('B-1-ROOM-103', 'Room 103', 'B', 1, 'Classroom', 40, 'Available'),
    ('B-1-ROOM-105', 'Room 105', 'B', 1, 'Classroom', 40, 'Available'),
    ('B-1-ROOM-107', 'Room 107', 'B', 1, 'Classroom', 40, 'Available'),
    ('B-1-FACULTY-2', 'Faculty Room', 'B', 1, 'Facility', NULL, 'Available'),
    ('B-1-CLINIC', 'Clinic', 'B', 1, 'Facility', NULL, 'Available'),
    ('B-1-ROOM-102', 'Room 102', 'B', 1, 'Classroom', 40, 'Available'),
    ('B-1-ROOM-104', 'Room 104', 'B', 1, 'Classroom', 40, 'Available'),
    ('B-1-ROOM-106', 'Room 106', 'B', 1, 'Classroom', 40, 'Available'),
    ('C-1-ROOM-A', 'Room A', 'C', 1, 'Classroom', 40, 'Available'),
    ('C-1-ROOM-B', 'Room B', 'C', 1, 'Classroom', 40, 'Available'),
    ('C-1-ROOM-C', 'Room C', 'C', 1, 'Classroom', 40, 'Available'),
    ('C-1-MEN-CR', 'Men CR', 'C', 1, 'Facility', NULL, 'Available'),
    ('C-1-ROOM-D', 'Room D', 'C', 1, 'Classroom', 40, 'Available'),
    ('C-1-ROOM-E', 'Room E', 'C', 1, 'Classroom', 40, 'Available'),
    ('C-1-ROOM-F', 'Room F', 'C', 1, 'Classroom', 40, 'Available'),
    ('C-1-WOMEN-CR', 'Women CR', 'C', 1, 'Facility', NULL, 'Available'),
    ('C-2-ROOM-G', 'Room G', 'C', 2, 'Classroom', 40, 'Available'),
    ('C-2-ROOM-H', 'Room H', 'C', 2, 'Classroom', 40, 'Available'),
    ('C-2-ROOM-I', 'Room I', 'C', 2, 'Classroom', 40, 'Available'),
    ('C-2-ROOM-J', 'Room J', 'C', 2, 'Classroom', 40, 'Available'),
    ('C-2-MEN-CR', 'Men CR', 'C', 2, 'Facility', NULL, 'Available'),
    ('C-2-ROOM-K', 'Room K', 'C', 2, 'Classroom', 40, 'Available'),
    ('C-2-ROOM-L', 'Room L', 'C', 2, 'Classroom', 40, 'Available'),
    ('C-2-ROOM-M', 'Room M', 'C', 2, 'Classroom', 40, 'Available'),
    ('C-2-WOMEN-CR', 'Women CR', 'C', 2, 'Facility', NULL, 'Available'),
    ('C-3-ROOM-N', 'Room N', 'C', 3, 'Classroom', 40, 'Available'),
    ('C-3-ROOM-O', 'Room O', 'C', 3, 'Classroom', 40, 'Available'),
    ('C-3-ROOM-P', 'Room P', 'C', 3, 'Classroom', 40, 'Available'),
    ('C-3-ROOM-Q', 'Room Q', 'C', 3, 'Classroom', 40, 'Available'),
    ('C-3-MEN-CR', 'Men CR', 'C', 3, 'Facility', NULL, 'Available'),
    ('C-3-ROOM-R', 'Room R', 'C', 3, 'Classroom', 40, 'Available'),
    ('C-3-ROOM-S', 'Room S', 'C', 3, 'Classroom', 40, 'Available'),
    ('C-3-ROOM-T', 'Room T', 'C', 3, 'Classroom', 40, 'Available'),
    ('C-3-WOMEN-CR', 'Women CR', 'C', 3, 'Facility', NULL, 'Available'),
    ('D-1-FACULTY', 'Faculty Room', 'D', 1, 'Facility', NULL, 'Available'),
    ('D-1-ROOM-101', 'Room 101', 'D', 1, 'Classroom', 40, 'Available'),
    ('D-1-ROOM-102', 'Room 102', 'D', 1, 'Classroom', 40, 'Available'),
    ('D-1-ROOM-103', 'Room 103', 'D', 1, 'Classroom', 40, 'Available'),
    ('D-1-MEN-CR', 'Men CR', 'D', 1, 'Facility', NULL, 'Available'),
    ('D-1-ROOM-104', 'Room 104', 'D', 1, 'Classroom', 40, 'Available'),
    ('D-1-ROOM-105', 'Room 105', 'D', 1, 'Classroom', 40, 'Available'),
    ('D-1-ROOM-106', 'Room 106', 'D', 1, 'Classroom', 40, 'Available'),
    ('D-2-FACULTY', 'Faculty Room', 'D', 2, 'Facility', NULL, 'Available'),
    ('D-2-ROOM-201', 'Room 201', 'D', 2, 'Classroom', 40, 'Available'),
    ('D-2-ROOM-202', 'Room 202', 'D', 2, 'Classroom', 40, 'Available'),
    ('D-2-ROOM-203', 'Room 203', 'D', 2, 'Classroom', 40, 'Available'),
    ('D-2-WOMEN-CR', 'Women CR', 'D', 2, 'Facility', NULL, 'Available'),
    ('D-2-ROOM-204', 'Room 204', 'D', 2, 'Classroom', 40, 'Available'),
    ('D-2-ROOM-205', 'Room 205', 'D', 2, 'Classroom', 40, 'Available'),
    ('D-2-ROOM-206', 'Room 206', 'D', 2, 'Classroom', 40, 'Available'),
    ('D-3-FACULTY', 'Faculty Room', 'D', 3, 'Facility', NULL, 'Available'),
    ('D-3-ROOM-301', 'Room 301', 'D', 3, 'Classroom', 40, 'Available'),
    ('D-3-ROOM-302', 'Room 302', 'D', 3, 'Classroom', 40, 'Available'),
    ('D-3-ROOM-303', 'Room 303', 'D', 3, 'Classroom', 40, 'Available'),
    ('D-3-MEN-CR', 'Men CR', 'D', 3, 'Facility', NULL, 'Available'),
    ('D-3-ROOM-304', 'Room 304', 'D', 3, 'Classroom', 40, 'Available'),
    ('D-3-ROOM-305', 'Room 305', 'D', 3, 'Classroom', 40, 'Available'),
    ('D-3-ROOM-306', 'Room 306', 'D', 3, 'Classroom', 40, 'Available'),
    ('D-3-WOMEN-CR', 'Women CR', 'D', 3, 'Facility', NULL, 'Available')
ON DUPLICATE KEY UPDATE
    room_name = VALUES(room_name),
    building = VALUES(building),
    floor_number = VALUES(floor_number),
    room_type = VALUES(room_type),
    capacity = VALUES(capacity),
    status = VALUES(status);

UPDATE class_schedules cs
INNER JOIN rooms old_room ON old_room.room_id = cs.room_id
INNER JOIN rooms replacement_room ON replacement_room.room_code = CASE old_room.room_code
    WHEN 'DEMO-LAB-01' THEN 'A-1-LAB-1'
    WHEN 'DEMO-ROOM-01' THEN 'A-2-ROOM-101'
END
SET cs.room_id = replacement_room.room_id
WHERE old_room.room_code IN ('DEMO-LAB-01', 'DEMO-ROOM-01');

DELETE FROM rooms
WHERE room_code IN ('DEMO-LAB-01', 'DEMO-ROOM-01');

INSERT INTO room_monitor_layout (room_id, layout_row, layout_column, column_span)
SELECT room.room_id, layout_seed.layout_row, layout_seed.layout_column, layout_seed.column_span
FROM rooms room
INNER JOIN (
    SELECT 'A-1-LAB-1' AS room_code, 1 AS layout_row, 1 AS layout_column, 4 AS column_span
    UNION ALL SELECT 'A-1-CASHIER', 1, 5, 2
    UNION ALL SELECT 'A-1-MIS', 1, 7, 2
    UNION ALL SELECT 'A-1-MENS-CR', 1, 9, 4
    UNION ALL SELECT 'A-1-LAB-2', 3, 1, 4
    UNION ALL SELECT 'A-1-ADMIN', 3, 7, 4
    UNION ALL SELECT 'A-2-LIBRARY', 1, 1, 4
    UNION ALL SELECT 'A-2-ROOM-101', 1, 5, 4
    UNION ALL SELECT 'A-2-WOMENS-CR', 1, 9, 4
    UNION ALL SELECT 'A-2-LAB-3', 3, 1, 4
    UNION ALL SELECT 'A-2-ROOM-102', 3, 5, 3
    UNION ALL SELECT 'A-2-ROOM-103', 3, 8, 3
    UNION ALL SELECT 'B-1-MEN-CR', 1, 1, 2
    UNION ALL SELECT 'B-1-WOMEN-CR', 1, 3, 2
    UNION ALL SELECT 'B-1-FACULTY-1', 1, 5, 2
    UNION ALL SELECT 'B-1-ROOM-103', 1, 7, 2
    UNION ALL SELECT 'B-1-ROOM-105', 1, 9, 2
    UNION ALL SELECT 'B-1-ROOM-107', 1, 11, 2
    UNION ALL SELECT 'B-1-FACULTY-2', 3, 1, 2
    UNION ALL SELECT 'B-1-CLINIC', 3, 3, 2
    UNION ALL SELECT 'B-1-ROOM-102', 3, 7, 2
    UNION ALL SELECT 'B-1-ROOM-104', 3, 9, 2
    UNION ALL SELECT 'B-1-ROOM-106', 3, 11, 2
    UNION ALL SELECT 'C-1-ROOM-A', 1, 1, 3
    UNION ALL SELECT 'C-1-ROOM-B', 1, 4, 3
    UNION ALL SELECT 'C-1-ROOM-C', 1, 7, 3
    UNION ALL SELECT 'C-1-MEN-CR', 1, 10, 3
    UNION ALL SELECT 'C-1-ROOM-D', 3, 1, 3
    UNION ALL SELECT 'C-1-ROOM-E', 3, 6, 2
    UNION ALL SELECT 'C-1-ROOM-F', 3, 8, 2
    UNION ALL SELECT 'C-1-WOMEN-CR', 3, 10, 3
    UNION ALL SELECT 'C-2-ROOM-G', 2, 1, 2
    UNION ALL SELECT 'C-2-ROOM-H', 1, 1, 3
    UNION ALL SELECT 'C-2-ROOM-I', 1, 4, 3
    UNION ALL SELECT 'C-2-ROOM-J', 1, 7, 3
    UNION ALL SELECT 'C-2-MEN-CR', 1, 10, 3
    UNION ALL SELECT 'C-2-ROOM-K', 3, 1, 3
    UNION ALL SELECT 'C-2-ROOM-L', 3, 6, 2
    UNION ALL SELECT 'C-2-ROOM-M', 3, 8, 2
    UNION ALL SELECT 'C-2-WOMEN-CR', 3, 10, 3
    UNION ALL SELECT 'C-3-ROOM-N', 2, 1, 2
    UNION ALL SELECT 'C-3-ROOM-O', 1, 1, 3
    UNION ALL SELECT 'C-3-ROOM-P', 1, 4, 3
    UNION ALL SELECT 'C-3-ROOM-Q', 1, 7, 3
    UNION ALL SELECT 'C-3-MEN-CR', 1, 10, 3
    UNION ALL SELECT 'C-3-ROOM-R', 3, 1, 3
    UNION ALL SELECT 'C-3-ROOM-S', 3, 6, 2
    UNION ALL SELECT 'C-3-ROOM-T', 3, 8, 2
    UNION ALL SELECT 'C-3-WOMEN-CR', 3, 10, 3
    UNION ALL SELECT 'D-1-FACULTY', 1, 1, 2
    UNION ALL SELECT 'D-1-ROOM-101', 1, 3, 2
    UNION ALL SELECT 'D-1-ROOM-102', 1, 5, 2
    UNION ALL SELECT 'D-1-ROOM-103', 1, 7, 2
    UNION ALL SELECT 'D-1-MEN-CR', 1, 9, 4
    UNION ALL SELECT 'D-1-ROOM-104', 3, 2, 3
    UNION ALL SELECT 'D-1-ROOM-105', 3, 5, 3
    UNION ALL SELECT 'D-1-ROOM-106', 3, 8, 3
    UNION ALL SELECT 'D-2-FACULTY', 1, 1, 2
    UNION ALL SELECT 'D-2-ROOM-201', 1, 3, 2
    UNION ALL SELECT 'D-2-ROOM-202', 1, 5, 2
    UNION ALL SELECT 'D-2-ROOM-203', 1, 7, 2
    UNION ALL SELECT 'D-2-WOMEN-CR', 1, 9, 4
    UNION ALL SELECT 'D-2-ROOM-204', 3, 2, 3
    UNION ALL SELECT 'D-2-ROOM-205', 3, 5, 3
    UNION ALL SELECT 'D-2-ROOM-206', 3, 8, 3
    UNION ALL SELECT 'D-3-FACULTY', 1, 1, 2
    UNION ALL SELECT 'D-3-ROOM-301', 1, 3, 2
    UNION ALL SELECT 'D-3-ROOM-302', 1, 5, 2
    UNION ALL SELECT 'D-3-ROOM-303', 1, 7, 2
    UNION ALL SELECT 'D-3-MEN-CR', 1, 9, 4
    UNION ALL SELECT 'D-3-ROOM-304', 3, 1, 3
    UNION ALL SELECT 'D-3-ROOM-305', 3, 6, 3
    UNION ALL SELECT 'D-3-ROOM-306', 3, 9, 2
    UNION ALL SELECT 'D-3-WOMEN-CR', 3, 11, 2
) layout_seed ON layout_seed.room_code = room.room_code
ON DUPLICATE KEY UPDATE
    layout_row = VALUES(layout_row),
    layout_column = VALUES(layout_column),
    column_span = VALUES(column_span);
