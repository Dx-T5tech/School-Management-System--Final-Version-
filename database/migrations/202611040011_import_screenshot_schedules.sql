-- Import screenshot schedule slots only when their course belongs to the
-- section's active-year curriculum and the named room exists as a schedulable
-- inventory room. The same active teacher is assigned to imported courses;
-- source timetable conflicts are retained for Conflict Review.
CREATE TEMPORARY TABLE screenshot_schedule_import (
    section_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(50) NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    building VARCHAR(20) NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (section_name, subject_code, day_of_week, start_time, room_name)
) ENGINE=InnoDB;

INSERT INTO screenshot_schedule_import (
    section_name, subject_code, day_of_week, start_time, end_time, building, room_name
) VALUES
    ('IT1A', 'CPROG1', 'Tuesday', '07:30:00', '09:30:00', 'B', 'Room 102'),
    ('IT1A', 'CPROG1', 'Tuesday', '09:30:00', '11:00:00', 'B', 'Room 102'),
    ('IT1A', 'GENED7', 'Tuesday', '11:00:00', '12:30:00', 'B', 'Room 102'),
    ('IT1A', 'GENED6', 'Tuesday', '13:30:00', '15:00:00', 'B', 'Room 102'),
    ('IT1A', 'GENED5', 'Tuesday', '15:00:00', '16:30:00', 'B', 'Room 102'),
    ('IT1A', 'CCFUND', 'Tuesday', '16:30:00', '18:00:00', 'B', 'Room 102'),
    ('IT1A', 'GENEL1', 'Thursday', '09:30:00', '11:00:00', 'B', 'Room 102'),
    ('IT1A', 'CPROG1', 'Thursday', '11:00:00', '12:30:00', 'B', 'Room 102'),
    ('IT1A', 'GENED6', 'Thursday', '13:30:00', '15:00:00', 'B', 'Room 102'),
    ('IT1A', 'GENED5', 'Thursday', '15:00:00', '16:30:00', 'B', 'Room 102'),
    ('IT1A', 'CCFUND', 'Thursday', '16:30:00', '18:00:00', 'B', 'Room 102'),
    ('IT1A', 'CWTS11', 'Saturday', '08:00:00', '09:30:00', 'B', 'Room 102'),
    ('IT1A', 'CWTS11', 'Saturday', '09:30:00', '11:00:00', 'B', 'Room 102'),
    ('IT1A', 'GENED7', 'Saturday', '11:00:00', '12:30:00', 'B', 'Room 102'),
    ('IT1A', 'GENEL1', 'Saturday', '13:30:00', '15:00:00', 'B', 'Room 102'),
    ('IT1A', 'FCMATH', 'Saturday', '15:00:00', '16:30:00', 'B', 'Room 102'),
    ('IT1A', 'ENGPLU1', 'Saturday', '16:30:00', '18:00:00', 'B', 'Room 102'),
    ('IT1B', 'CPROG1', 'Monday', '07:30:00', '09:30:00', 'B', 'Room 102'),
    ('IT1B', 'GENED6', 'Monday', '09:30:00', '11:00:00', 'B', 'Room 102'),
    ('IT1B', 'FCMATH', 'Monday', '11:00:00', '12:30:00', 'B', 'Room 102'),
    ('IT1B', 'GENED5', 'Monday', '13:30:00', '15:00:00', 'B', 'Room 102'),
    ('IT1B', 'CPROG1', 'Monday', '15:00:00', '16:30:00', 'B', 'Room 102'),
    ('IT1B', 'ENGPLU1', 'Monday', '16:30:00', '18:00:00', 'B', 'Room 102'),
    ('IT1B', 'GENEL1', 'Wednesday', '08:00:00', '09:30:00', 'B', 'Room 102'),
    ('IT1B', 'CPROG1', 'Wednesday', '09:30:00', '11:00:00', 'B', 'Room 102'),
    ('IT1B', 'GENED6', 'Wednesday', '11:00:00', '12:30:00', 'B', 'Room 102'),
    ('IT1B', 'CCFUND', 'Wednesday', '13:30:00', '15:00:00', 'B', 'Room 102'),
    ('IT1B', 'GENED5', 'Wednesday', '15:00:00', '16:30:00', 'B', 'Room 102'),
    ('IT1B', 'CPROG1', 'Wednesday', '16:30:00', '18:00:00', 'B', 'Room 102'),
    ('IT1B', 'CWTS11', 'Friday', '08:00:00', '09:30:00', 'B', 'Room 102'),
    ('IT1B', 'CWTS11', 'Friday', '09:30:00', '11:00:00', 'B', 'Room 102'),
    ('IT1B', 'GENEL1', 'Friday', '11:00:00', '12:30:00', 'B', 'Room 102'),
    ('IT1B', 'GENED7', 'Friday', '13:30:00', '15:00:00', 'B', 'Room 102'),
    ('IT1B', 'CCFUND', 'Friday', '15:00:00', '16:30:00', 'B', 'Room 102'),
    ('IT1B', 'GENED7', 'Friday', '16:30:00', '18:00:00', 'B', 'Room 102'),
    ('IT1C', 'GENEL1', 'Tuesday', '08:00:00', '09:30:00', 'B', 'Room 103'),
    ('IT1C', 'ENGPLU1', 'Tuesday', '09:30:00', '11:00:00', 'B', 'Room 103'),
    ('IT1C', 'CCFUND', 'Tuesday', '11:00:00', '12:30:00', 'B', 'Room 103'),
    ('IT1C', 'FCMATH', 'Tuesday', '13:30:00', '15:00:00', 'B', 'Room 103'),
    ('IT1C', 'GENED7', 'Tuesday', '15:00:00', '16:30:00', 'B', 'Room 103'),
    ('IT1C', 'CPROG1', 'Tuesday', '16:30:00', '18:00:00', 'B', 'Room 103'),
    ('IT1C', 'CWTS11', 'Thursday', '08:00:00', '09:30:00', 'B', 'Room 103'),
    ('IT1C', 'GENED6', 'Thursday', '09:30:00', '11:00:00', 'B', 'Room 103'),
    ('IT1C', 'GENED6', 'Thursday', '11:00:00', '12:30:00', 'B', 'Room 103'),
    ('IT1C', 'CWTS11', 'Thursday', '13:30:00', '15:00:00', 'B', 'Room 103'),
    ('IT1C', 'CCFUND', 'Thursday', '15:00:00', '16:30:00', 'B', 'Room 103'),
    ('IT1C', 'CPROG1', 'Thursday', '16:30:00', '18:30:00', 'B', 'Room 103'),
    ('IT1C', 'GENED7', 'Saturday', '08:00:00', '09:30:00', 'B', 'Room 103'),
    ('IT1C', 'GENEL1', 'Saturday', '09:30:00', '11:00:00', 'B', 'Room 103'),
    ('IT1C', 'GENED5', 'Saturday', '11:00:00', '12:30:00', 'B', 'Room 103'),
    ('IT1C', 'GENED5', 'Saturday', '13:30:00', '15:00:00', 'B', 'Room 103'),
    ('IT1C', 'CPROG1', 'Saturday', '15:00:00', '16:30:00', 'B', 'Room 103'),
    ('IT1D', 'CCFUND', 'Monday', '08:00:00', '09:30:00', 'B', 'Room 103'),
    ('IT1D', 'GENEL1', 'Monday', '09:30:00', '11:00:00', 'B', 'Room 103'),
    ('IT1D', 'GENED6', 'Monday', '11:00:00', '12:30:00', 'B', 'Room 103'),
    ('IT1D', 'GENED7', 'Monday', '13:30:00', '15:00:00', 'B', 'Room 103'),
    ('IT1D', 'GENED5', 'Monday', '15:00:00', '16:30:00', 'B', 'Room 103'),
    ('IT1D', 'CPROG1', 'Wednesday', '08:00:00', '09:30:00', 'B', 'Room 103'),
    ('IT1D', 'GENED7', 'Wednesday', '09:30:00', '11:00:00', 'B', 'Room 103'),
    ('IT1D', 'CCFUND', 'Wednesday', '11:00:00', '12:30:00', 'B', 'Room 103'),
    ('IT1D', 'ENGPLU1', 'Wednesday', '13:30:00', '15:00:00', 'B', 'Room 103'),
    ('IT1D', 'GENED5', 'Wednesday', '15:00:00', '16:30:00', 'B', 'Room 103'),
    ('IT1D', 'CPROG1', 'Wednesday', '16:30:00', '18:00:00', 'B', 'Room 103'),
    ('IT1D', 'FCMATH', 'Friday', '08:00:00', '09:30:00', 'B', 'Room 103'),
    ('IT1D', 'GENED6', 'Friday', '09:30:00', '11:00:00', 'B', 'Room 103'),
    ('IT1D', 'CWTS11', 'Friday', '11:00:00', '12:30:00', 'B', 'Room 103'),
    ('IT1D', 'CWTS11', 'Friday', '13:30:00', '15:00:00', 'B', 'Room 103'),
    ('IT1D', 'CPROG1', 'Friday', '15:00:00', '16:30:00', 'B', 'Room 103'),
    ('IT1D', 'GENEL1', 'Friday', '16:30:00', '18:00:00', 'B', 'Room 103'),
    ('IT1E', 'CPROG1', 'Tuesday', '07:30:00', '09:30:00', 'B', 'Room 104'),
    ('IT1E', 'GENED6', 'Tuesday', '09:30:00', '11:00:00', 'B', 'Room 104'),
    ('IT1E', 'CPROG1', 'Tuesday', '11:00:00', '12:30:00', 'B', 'Room 104'),
    ('IT1E', 'CCFUND', 'Tuesday', '13:30:00', '15:00:00', 'B', 'Room 104'),
    ('IT1E', 'GENED5', 'Tuesday', '17:00:00', '18:30:00', 'B', 'Room 104'),
    ('IT1E', 'CWTS11', 'Thursday', '08:00:00', '09:30:00', 'B', 'Room 104'),
    ('IT1E', 'CWTS11', 'Thursday', '09:30:00', '11:00:00', 'B', 'Room 104'),
    ('IT1E', 'GENED6', 'Thursday', '11:00:00', '12:30:00', 'B', 'Room 104'),
    ('IT1E', 'GENED7', 'Thursday', '13:30:00', '15:00:00', 'B', 'Room 104'),
    ('IT1E', 'GENEL1', 'Thursday', '15:00:00', '16:30:00', 'B', 'Room 104'),
    ('IT1E', 'FCMATH', 'Thursday', '16:30:00', '18:00:00', 'B', 'Room 104'),
    ('IT1E', 'GENEL1', 'Saturday', '08:00:00', '09:30:00', 'B', 'Room 104'),
    ('IT1E', 'ENGPLU1', 'Saturday', '09:30:00', '11:00:00', 'B', 'Room 104'),
    ('IT1E', 'CPROG1', 'Saturday', '11:00:00', '12:30:00', 'B', 'Room 104'),
    ('IT1E', 'CCFUND', 'Saturday', '13:30:00', '15:00:00', 'B', 'Room 104'),
    ('IT1E', 'GENED5', 'Saturday', '15:00:00', '16:30:00', 'B', 'Room 104'),
    ('IT1E', 'GENED7', 'Saturday', '16:30:00', '18:00:00', 'B', 'Room 104'),
    ('IT1F', 'GENEL1', 'Monday', '08:00:00', '09:30:00', 'B', 'Room 104'),
    ('IT1F', 'CCFUND', 'Monday', '09:30:00', '11:00:00', 'B', 'Room 104'),
    ('IT1F', 'GENED7', 'Monday', '11:00:00', '12:30:00', 'B', 'Room 104'),
    ('IT1F', 'FCMATH', 'Monday', '15:00:00', '16:30:00', 'B', 'Room 104'),
    ('IT1F', 'GENED6', 'Monday', '16:30:00', '18:00:00', 'B', 'Room 104'),
    ('IT1F', 'GENED6', 'Monday', '18:00:00', '19:30:00', 'B', 'Room 104'),
    ('IT1F', 'CPROG1', 'Wednesday', '08:00:00', '09:30:00', 'B', 'Room 104'),
    ('IT1F', 'ENGPLU1', 'Wednesday', '09:30:00', '11:00:00', 'B', 'Room 104'),
    ('IT1F', 'GENED5', 'Wednesday', '11:00:00', '12:30:00', 'B', 'Room 104'),
    ('IT1F', 'CCFUND', 'Wednesday', '15:00:00', '16:30:00', 'B', 'Room 104'),
    ('IT1F', 'CPROG1', 'Wednesday', '16:30:00', '18:30:00', 'B', 'Room 104'),
    ('IT1F', 'GENED7', 'Friday', '08:00:00', '09:30:00', 'B', 'Room 104'),
    ('IT1F', 'CPROG1', 'Friday', '09:30:00', '11:00:00', 'B', 'Room 104'),
    ('IT1F', 'CWTS11', 'Friday', '11:00:00', '12:30:00', 'B', 'Room 104'),
    ('IT1F', 'CWTS11', 'Friday', '13:30:00', '15:00:00', 'B', 'Room 104'),
    ('IT1F', 'GENED5', 'Friday', '15:00:00', '16:30:00', 'B', 'Room 104'),
    ('IT1F', 'GENEL1', 'Friday', '16:30:00', '18:00:00', 'B', 'Room 104'),
    ('IT1G', 'ENGPLU1', 'Tuesday', '08:00:00', '09:30:00', 'B', 'Room 107'),
    ('IT1G', 'GENED7', 'Tuesday', '09:30:00', '11:00:00', 'B', 'Room 107'),
    ('IT1G', 'CPROG1', 'Tuesday', '11:00:00', '12:30:00', 'B', 'Room 107'),
    ('IT1G', 'CPROG1', 'Tuesday', '13:30:00', '15:00:00', 'B', 'Room 107'),
    ('IT1G', 'CWTS11', 'Tuesday', '15:00:00', '16:30:00', 'B', 'Room 107'),
    ('IT1G', 'GENED5', 'Thursday', '08:00:00', '09:30:00', 'B', 'Room 107'),
    ('IT1G', 'CWTS11', 'Thursday', '09:30:00', '11:00:00', 'B', 'Room 107'),
    ('IT1G', 'CCFUND', 'Thursday', '11:00:00', '12:30:00', 'B', 'Room 107'),
    ('IT1G', 'GENEL1', 'Thursday', '13:30:00', '15:00:00', 'B', 'Room 107'),
    ('IT1G', 'GENED7', 'Thursday', '17:00:00', '18:30:00', 'B', 'Room 107'),
    ('IT1G', 'GENED5', 'Saturday', '08:00:00', '09:30:00', 'B', 'Room 107'),
    ('IT1G', 'GENED6', 'Saturday', '09:30:00', '11:00:00', 'B', 'Room 107'),
    ('IT1G', 'GENED6', 'Saturday', '11:00:00', '12:30:00', 'B', 'Room 107'),
    ('IT1G', 'GENEL1', 'Saturday', '13:30:00', '15:00:00', 'B', 'Room 107'),
    ('IT1G', 'CCFUND', 'Saturday', '15:00:00', '16:30:00', 'B', 'Room 107'),
    ('IT1G', 'FCMATH', 'Saturday', '16:30:00', '18:00:00', 'B', 'Room 107'),
    ('IT2A', 'DSALGO', 'Monday', '07:30:00', '09:30:00', 'B', 'Room 105'),
    ('IT2A', 'GENEL2', 'Monday', '09:30:00', '11:00:00', 'B', 'Room 105'),
    ('IT2A', 'DSALGO', 'Monday', '11:00:00', '12:30:00', 'B', 'Room 105'),
    ('IT2A', 'OOPROG', 'Monday', '13:00:00', '15:00:00', 'B', 'Room 105'),
    ('IT2A', 'GENED8', 'Wednesday', '09:30:00', '11:00:00', 'B', 'Room 105'),
    ('IT2A', 'GENEL3', 'Wednesday', '11:00:00', '12:30:00', 'B', 'Room 105'),
    ('IT2A', 'DSALGO', 'Wednesday', '13:30:00', '15:00:00', 'B', 'Room 105'),
    ('IT2A', 'PLTECH', 'Wednesday', '15:00:00', '16:30:00', 'B', 'Room 105'),
    ('IT2A', 'GENEL3', 'Wednesday', '16:30:00', '18:00:00', 'B', 'Room 105'),
    ('IT2A', 'GENEL2', 'Friday', '08:00:00', '09:30:00', 'B', 'Room 105'),
    ('IT2A', 'OOPROG', 'Friday', '09:30:00', '11:00:00', 'B', 'Room 105'),
    ('IT2A', 'GENED8', 'Friday', '11:00:00', '12:30:00', 'B', 'Room 105'),
    ('IT2A', 'PLTECH', 'Friday', '13:00:00', '15:00:00', 'B', 'Room 105'),
    ('IT2A', 'PLTECH', 'Friday', '15:00:00', '16:30:00', 'B', 'Room 105'),
    ('IT2A', 'OOPROG', 'Friday', '16:30:00', '18:00:00', 'B', 'Room 105'),
    ('IT2B', 'GENEL3', 'Tuesday', '08:00:00', '09:30:00', 'B', 'Room 105'),
    ('IT2B', 'GENEL2', 'Tuesday', '09:30:00', '11:00:00', 'B', 'Room 105'),
    ('IT2B', 'GENED8', 'Tuesday', '11:00:00', '12:30:00', 'B', 'Room 105'),
    ('IT2B', 'PLTECH', 'Tuesday', '13:00:00', '15:00:00', 'B', 'Room 105'),
    ('IT2B', 'OOPROG', 'Tuesday', '15:00:00', '16:30:00', 'B', 'Room 105'),
    ('IT2B', 'DSALGO', 'Tuesday', '16:30:00', '18:00:00', 'B', 'Room 105'),
    ('IT2B', 'DSALGO', 'Thursday', '07:30:00', '09:30:00', 'B', 'Room 105'),
    ('IT2B', 'GENEL2', 'Thursday', '09:30:00', '11:00:00', 'B', 'Room 105'),
    ('IT2B', 'GENED8', 'Thursday', '11:00:00', '12:30:00', 'B', 'Room 105'),
    ('IT2B', 'GENEL3', 'Thursday', '13:30:00', '15:00:00', 'B', 'Room 105'),
    ('IT2B', 'OOPROG', 'Thursday', '15:00:00', '16:30:00', 'B', 'Room 105'),
    ('IT2B', 'DSALGO', 'Thursday', '16:30:00', '18:00:00', 'B', 'Room 105'),
    ('IT2B', 'PLTECH', 'Saturday', '09:30:00', '11:00:00', 'B', 'Room 105'),
    ('IT2B', 'PLTECH', 'Saturday', '11:00:00', '12:30:00', 'B', 'Room 105'),
    ('IT2B', 'OOPROG', 'Saturday', '13:30:00', '15:00:00', 'B', 'Room 105'),
    ('IT2C', 'PLTECH', 'Monday', '11:00:00', '12:30:00', 'B', 'Room 106'),
    ('IT2C', 'PLTECH', 'Monday', '13:30:00', '15:00:00', 'B', 'Room 106'),
    ('IT2C', 'GENED8', 'Monday', '15:00:00', '16:30:00', 'B', 'Room 106'),
    ('IT2C', 'GENEL3', 'Wednesday', '08:00:00', '09:30:00', 'B', 'Room 106'),
    ('IT2C', 'DSALGO', 'Wednesday', '09:30:00', '11:00:00', 'B', 'Room 106'),
    ('IT2C', 'GENEL2', 'Wednesday', '15:00:00', '16:30:00', 'B', 'Room 106'),
    ('IT2C', 'PLTECH', 'Wednesday', '16:30:00', '18:30:00', 'B', 'Room 106'),
    ('IT2C', 'DSALGO', 'Friday', '09:30:00', '11:00:00', 'B', 'Room 106'),
    ('IT2C', 'GENED8', 'Friday', '11:00:00', '12:30:00', 'B', 'Room 106'),
    ('IT2C', 'DSALGO', 'Friday', '13:00:00', '15:00:00', 'B', 'Room 106'),
    ('IT2C', 'GENEL2', 'Friday', '15:00:00', '16:30:00', 'B', 'Room 106'),
    ('IT2C', 'GENEL3', 'Friday', '16:30:00', '18:00:00', 'B', 'Room 106'),
    ('IT2D', 'GENEL3', 'Tuesday', '08:00:00', '09:30:00', 'B', 'Room 106'),
    ('IT2D', 'GENED8', 'Tuesday', '13:30:00', '15:00:00', 'B', 'Room 106'),
    ('IT2D', 'GENEL3', 'Thursday', '08:00:00', '09:30:00', 'B', 'Room 106'),
    ('IT2D', 'GENEL2', 'Thursday', '09:30:00', '11:00:00', 'B', 'Room 106'),
    ('IT2D', 'GENED8', 'Thursday', '11:00:00', '12:30:00', 'B', 'Room 106'),
    ('IT2D', 'GENEL2', 'Thursday', '13:30:00', '15:00:00', 'B', 'Room 106'),
    ('IT2D', 'OOPROG', 'Thursday', '15:00:00', '16:30:00', 'B', 'Room 106'),
    ('IT2D', 'OOPROG', 'Saturday', '09:30:00', '11:00:00', 'B', 'Room 106'),
    ('IT2D', 'OOPROG', 'Saturday', '11:00:00', '12:30:00', 'B', 'Room 106'),
    ('IT2D', 'OOPROG', 'Saturday', '13:30:00', '15:00:00', 'B', 'Room 106'),
    ('IT2D', 'DSALGO', 'Saturday', '15:00:00', '16:30:00', 'B', 'Room 106'),
    ('IT2D', 'DSALGO', 'Saturday', '16:30:00', '18:30:00', 'B', 'Room 106'),
    ('IT2E', 'GENEL3', 'Wednesday', '09:30:00', '11:00:00', 'B', 'Room 107'),
    ('IT2E', 'PLTECH', 'Wednesday', '11:00:00', '12:30:00', 'B', 'Room 107'),
    ('IT2E', 'OOPROG', 'Wednesday', '13:00:00', '15:00:00', 'B', 'Room 107'),
    ('IT2E', 'GENEL2', 'Wednesday', '15:00:00', '16:30:00', 'B', 'Room 107'),
    ('IT2E', 'GENEL2', 'Wednesday', '16:30:00', '18:00:00', 'B', 'Room 107'),
    ('IT2E', 'PLTECH', 'Friday', '08:00:00', '09:30:00', 'B', 'Room 107'),
    ('IT2E', 'GENED8', 'Friday', '13:30:00', '15:00:00', 'B', 'Room 107'),
    ('IT2E', 'DSALGO', 'Friday', '15:00:00', '16:30:00', 'B', 'Room 107'),
    ('IT2E', 'PLTECH', 'Friday', '16:30:00', '18:30:00', 'B', 'Room 107'),
    ('IT3A', 'ADETEC', 'Monday', '09:30:00', '11:00:00', 'C', 'Room S'),
    ('IT3A', 'ADETEC', 'Monday', '11:00:00', '12:30:00', 'C', 'Room S'),
    ('IT3A', 'NETWO2', 'Monday', '13:00:00', '15:00:00', 'C', 'Room S'),
    ('IT3A', 'NETWO2', 'Monday', '15:00:00', '16:30:00', 'C', 'Room S'),
    ('IT3A', 'ADETEC', 'Wednesday', '09:00:00', '11:00:00', 'C', 'Room S'),
    ('IT3A', 'NETWO2', 'Wednesday', '15:00:00', '16:30:00', 'C', 'Room S'),
    ('IT3B', 'ADETEC', 'Thursday', '08:00:00', '09:30:00', 'C', 'Room I'),
    ('IT3B', 'NETWO2', 'Thursday', '09:30:00', '11:00:00', 'C', 'Room I'),
    ('IT3B', 'ADETEC', 'Thursday', '11:00:00', '12:30:00', 'C', 'Room I'),
    ('IT3B', 'NETWO2', 'Thursday', '13:00:00', '15:00:00', 'C', 'Room I'),
    ('IT3B', 'NETWO2', 'Thursday', '15:00:00', '16:30:00', 'C', 'Room I'),
    ('IT3B', 'ADETEC', 'Thursday', '16:30:00', '18:30:00', 'C', 'Room I'),
    ('IT3C', 'ADETEC', 'Monday', '11:00:00', '12:30:00', 'C', 'Room K'),
    ('IT3C', 'ADETEC', 'Monday', '13:00:00', '15:00:00', 'C', 'Room K'),
    ('IT3C', 'NETWO2', 'Friday', '08:00:00', '09:30:00', 'C', 'Room K'),
    ('IT3C', 'ADETEC', 'Friday', '09:30:00', '11:00:00', 'C', 'Room K'),
    ('IT3C', 'NETWO2', 'Friday', '11:00:00', '12:30:00', 'C', 'Room K'),
    ('IT3C', 'NETWO2', 'Friday', '16:30:00', '18:30:00', 'C', 'Room K'),
    ('IT3D', 'ADETEC', 'Tuesday', '11:00:00', '12:30:00', 'C', 'Room M'),
    ('IT3D', 'ADETEC', 'Tuesday', '15:00:00', '16:30:00', 'C', 'Room M'),
    ('IT3D', 'ADETEC', 'Tuesday', '16:30:00', '18:30:00', 'C', 'Room M'),
    ('IT4A', 'IASEC2', 'Friday', '11:00:00', '12:30:00', 'C', 'Room D'),
    ('IT4A', 'IASEC2', 'Friday', '13:00:00', '15:00:00', 'C', 'Room D'),
    ('IT4A', 'IASEC2', 'Friday', '15:00:00', '16:30:00', 'C', 'Room D'),
    ('IT4A', 'THESI1', 'Saturday', '08:00:00', '09:30:00', 'C', 'Room D'),
    ('IT4A', 'THESI1', 'Saturday', '09:30:00', '11:00:00', 'C', 'Room D'),
    ('IT4B', 'THESI1', 'Saturday', '11:00:00', '12:30:00', 'C', 'Room D'),
    ('IT4B', 'THESI1', 'Saturday', '13:00:00', '15:00:00', 'C', 'Room D'),
    ('CS4A', 'CSO501', 'Thursday', '18:00:00', '19:30:00', 'C', 'Room F'),
    ('CS4A', 'CSO501', 'Thursday', '19:30:00', '21:00:00', 'C', 'Room F'),
    ('CS4A', 'CSO500', 'Friday', '18:00:00', '19:30:00', 'C', 'Room F'),
    ('CS4A', 'CSO500', 'Friday', '19:30:00', '21:00:00', 'C', 'Room F'),
    ('CE4A', 'EMTECH', 'Wednesday', '09:30:00', '11:00:00', 'C', 'Room M'),
    ('CE4A', 'COELE3', 'Wednesday', '11:00:00', '12:30:00', 'C', 'Room M'),
    ('CE4A', 'COELE3', 'Wednesday', '13:30:00', '15:00:00', 'C', 'Room M'),
    ('CE4A', 'EMTECH', 'Wednesday', '15:00:00', '16:30:00', 'C', 'Room M'),
    ('CE4A', 'COMORG', 'Thursday', '09:30:00', '11:00:00', 'C', 'Room K'),
    ('CE4A', 'COMORG', 'Thursday', '11:00:00', '12:30:00', 'C', 'Room K'),
    ('CE4A', 'COMORG', 'Thursday', '13:30:00', '15:00:00', 'C', 'Room K'),
    ('CE4A', 'COMORG', 'Thursday', '15:00:00', '16:30:00', 'C', 'Room K'),
    ('EN2A', 'ECONOM', 'Monday', '09:30:00', '11:00:00', 'C', 'Room L'),
    ('EN2A', 'GENED6', 'Wednesday', '09:30:00', '11:00:00', 'C', 'Room L'),
    ('EN2A', 'CADRAF', 'Wednesday', '11:00:00', '12:30:00', 'C', 'Room L'),
    ('EN2A', 'CADRAF', 'Friday', '08:00:00', '09:30:00', 'C', 'Room L'),
    ('EN2A', 'ECONOM', 'Friday', '09:30:00', '11:00:00', 'C', 'Room L'),
    ('EN2A', 'GENED6', 'Friday', '11:00:00', '12:30:00', 'C', 'Room L'),
    ('EN2A', 'GENEL1', 'Friday', '13:30:00', '15:00:00', 'C', 'Room L'),
    ('EN2A', 'GENEL1', 'Saturday', '16:30:00', '18:00:00', 'C', 'Room L'),
    ('EN2B', 'CADRAF', 'Tuesday', '08:00:00', '09:30:00', 'C', 'Room L'),
    ('EN2B', 'ECONOM', 'Tuesday', '09:30:00', '11:00:00', 'C', 'Room L'),
    ('EN2B', 'GENED6', 'Tuesday', '13:30:00', '15:00:00', 'C', 'Room L'),
    ('EN2B', 'GENEL1', 'Tuesday', '15:00:00', '16:30:00', 'C', 'Room L'),
    ('EN2B', 'CADRAF', 'Tuesday', '16:30:00', '18:00:00', 'C', 'Room L'),
    ('EN2B', 'ECONOM', 'Wednesday', '08:00:00', '09:30:00', 'C', 'Room K'),
    ('EN2B', 'GENEL1', 'Wednesday', '09:30:00', '11:00:00', 'C', 'Room K'),
    ('EN2B', 'GENED6', 'Saturday', '15:00:00', '16:30:00', 'C', 'Room J'),
    ('EN4A', 'GENEL3', 'Wednesday', '13:30:00', '15:00:00', 'C', 'Room I'),
    ('EN4A', 'GENEL3', 'Thursday', '15:00:00', '16:30:00', 'C', 'Room M'),
    ('EN4A', 'ECELE1', 'Saturday', '13:30:00', '15:00:00', 'C', 'Room F'),
    ('EN4A', 'ECELE1', 'Saturday', '15:00:00', '16:30:00', 'C', 'Room F'),
    ('EN4A', 'ECELE1', 'Saturday', '16:30:00', '18:00:00', 'C', 'Room F');

CREATE TEMPORARY TABLE matched_screenshot_schedules ENGINE=InnoDB AS
SELECT DISTINCT
       sec.section_id,
       c.subject_id,
       sem.semester_id,
       room.room_id,
       imported.day_of_week,
       imported.start_time,
       imported.end_time
FROM screenshot_schedule_import AS imported
INNER JOIN sections AS sec
    ON sec.section_name = imported.section_name
   AND sec.status = 'Active'
INNER JOIN programs AS p
    ON p.program_id = sec.program_id
   AND p.status = 'Active'
INNER JOIN academic_years AS ay
    ON ay.academic_year_id = sec.academic_year_id
   AND ay.status = 'Active'
INNER JOIN curriculum AS c
    ON c.program_id = sec.program_id
   AND c.academic_year_id = sec.academic_year_id
   AND c.year_level = sec.year_level
INNER JOIN subjects AS subject
    ON subject.subject_id = c.subject_id
   AND subject.subject_code = imported.subject_code
   AND subject.status = 'Active'
INNER JOIN semesters AS sem
    ON sem.academic_year_id = ay.academic_year_id
   AND sem.status IN ('Active', 'Upcoming')
   AND c.semester_number = CASE
       WHEN LOWER(sem.semester_name) LIKE '1st%'
         OR LOWER(sem.semester_name) LIKE 'semester 1%' THEN 1
       WHEN LOWER(sem.semester_name) LIKE '2nd%'
         OR LOWER(sem.semester_name) LIKE 'semester 2%' THEN 2
       ELSE NULL
   END
INNER JOIN rooms AS room
    ON room.building = imported.building
   AND room.room_name = imported.room_name
   AND room.status = 'Available'
   AND room.room_type IN ('Classroom', 'Laboratory', 'Computer Laboratory');

START TRANSACTION;

INSERT INTO teaching_assignments (teacher_id, subject_id, section_id, semester_id)
SELECT DISTINCT
       (SELECT teacher_id
        FROM teachers
        WHERE employment_status = 'Active'
        ORDER BY teacher_id
        LIMIT 1),
       matched.subject_id,
       matched.section_id,
       matched.semester_id
FROM matched_screenshot_schedules AS matched
WHERE NOT EXISTS (
    SELECT 1
    FROM teaching_assignments AS existing
    WHERE existing.subject_id = matched.subject_id
      AND existing.section_id = matched.section_id
      AND existing.semester_id = matched.semester_id
      AND existing.teacher_id = (
          SELECT teacher_id
          FROM teachers
          WHERE employment_status = 'Active'
          ORDER BY teacher_id
          LIMIT 1
      )
);

INSERT INTO class_schedules (
    assignment_id, room_id, day_of_week, start_time, end_time, schedule_status
)
SELECT assignment.assignment_id,
       matched.room_id,
       matched.day_of_week,
       matched.start_time,
       matched.end_time,
       'Active'
FROM matched_screenshot_schedules AS matched
INNER JOIN teaching_assignments AS assignment
    ON assignment.teacher_id = (
        SELECT teacher_id
        FROM teachers
        WHERE employment_status = 'Active'
        ORDER BY teacher_id
        LIMIT 1
    )
   AND assignment.subject_id = matched.subject_id
   AND assignment.section_id = matched.section_id
   AND assignment.semester_id = matched.semester_id
WHERE NOT EXISTS (
    SELECT 1
    FROM class_schedules AS existing
    WHERE existing.assignment_id = assignment.assignment_id
      AND existing.room_id = matched.room_id
      AND existing.day_of_week = matched.day_of_week
      AND existing.start_time = matched.start_time
      AND existing.end_time = matched.end_time
);

COMMIT;
DROP TEMPORARY TABLE matched_screenshot_schedules;
DROP TEMPORARY TABLE screenshot_schedule_import;
