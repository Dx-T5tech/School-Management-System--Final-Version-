CREATE TABLE room_monitor_layout (
    room_id INT NOT NULL,
    layout_row TINYINT UNSIGNED NOT NULL,
    layout_column TINYINT UNSIGNED NOT NULL,
    column_span TINYINT UNSIGNED NOT NULL DEFAULT 2,
    PRIMARY KEY (room_id),
    CONSTRAINT fk_room_monitor_layout_room
        FOREIGN KEY (room_id) REFERENCES rooms (room_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO room_monitor_layout (room_id, layout_row, layout_column, column_span)
SELECT room_id, 1, 1, 2
FROM rooms
WHERE room_code = 'DEMO-LAB-01'
ON DUPLICATE KEY UPDATE
    layout_row = VALUES(layout_row),
    layout_column = VALUES(layout_column),
    column_span = VALUES(column_span);

INSERT INTO room_monitor_layout (room_id, layout_row, layout_column, column_span)
SELECT room_id, 3, 1, 2
FROM rooms
WHERE room_code = 'DEMO-ROOM-01'
ON DUPLICATE KEY UPDATE
    layout_row = VALUES(layout_row),
    layout_column = VALUES(layout_column),
    column_span = VALUES(column_span);
