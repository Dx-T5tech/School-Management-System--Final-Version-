INSERT INTO rooms (room_code, room_name, building, floor_number, room_type, capacity, status)
VALUES ('A-2-ROOM-101', 'Room 101', 'A', 2, 'Classroom', 40, 'Available')
ON DUPLICATE KEY UPDATE
    room_name = VALUES(room_name),
    building = VALUES(building),
    floor_number = VALUES(floor_number),
    room_type = VALUES(room_type),
    capacity = VALUES(capacity),
    status = VALUES(status);

INSERT INTO room_monitor_layout (room_id, layout_row, layout_column, column_span)
SELECT room_id, 1, 5, 4
FROM rooms
WHERE room_code = 'A-2-ROOM-101'
ON DUPLICATE KEY UPDATE
    layout_row = VALUES(layout_row),
    layout_column = VALUES(layout_column),
    column_span = VALUES(column_span);
