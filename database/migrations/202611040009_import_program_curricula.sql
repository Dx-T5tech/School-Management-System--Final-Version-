-- Import the supplied program curricula for the active 2026-2027 academic year.
-- BSCOE source entries are mapped to the existing BSCE program; course title and
-- unit differences are stored on each curriculum row, not overwritten globally.
-- BSCE: CMO No. 87 (2017)
-- BSCS: CMO No. 79 (2018)
-- BSELE: CMO No. 301 (2017)
-- BSIT: CMO No. 25 (2015)

SET @curriculum_schema_ddl = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'curriculum'
          AND COLUMN_NAME = 'subject_name_override'
    ) = 0,
    'ALTER TABLE curriculum ADD COLUMN subject_name_override VARCHAR(150) NULL AFTER subject_id',
    'SELECT 1'
);
PREPARE curriculum_schema_statement FROM @curriculum_schema_ddl;
EXECUTE curriculum_schema_statement;
DEALLOCATE PREPARE curriculum_schema_statement;

SET @curriculum_schema_ddl = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'curriculum'
          AND COLUMN_NAME = 'units_override'
    ) = 0,
    'ALTER TABLE curriculum ADD COLUMN units_override DECIMAL(5,2) NULL AFTER subject_name_override',
    'SELECT 1'
);
PREPARE curriculum_schema_statement FROM @curriculum_schema_ddl;
EXECUTE curriculum_schema_statement;
DEALLOCATE PREPARE curriculum_schema_statement;

CREATE TEMPORARY TABLE curriculum_import (
    program_code VARCHAR(10) NOT NULL,
    year_level INT NOT NULL,
    semester_number INT NOT NULL,
    subject_code VARCHAR(50) NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    units DECIMAL(5,2) NOT NULL,
    is_required TINYINT(1) NOT NULL,
    PRIMARY KEY (program_code, year_level, semester_number, subject_code)
) ENGINE=InnoDB;

INSERT INTO curriculum_import (
    program_code, year_level, semester_number, subject_code, subject_name, units, is_required
) VALUES
    ('BSCE', 1, 1, 'FCMATH', 'Fundamental Concepts of Mathematics', 3.00, 1),
    ('BSCE', 1, 1, 'ENGPLU1', 'English Plus', 3.00, 1),
    ('BSCE', 1, 1, 'CALCU1', 'Calculus 1', 3.00, 1),
    ('BSCE', 1, 1, 'COEDS', 'Computer Engineering as a Discipline', 1.00, 1),
    ('BSCE', 1, 1, 'GENCHE', 'Chemistry for Engineers', 4.00, 1),
    ('BSCE', 1, 1, 'GENED1', 'Understanding the Self', 3.00, 1),
    ('BSCE', 1, 1, 'GENED7', 'Science, Technology, Engineering and Society', 3.00, 1),
    ('BSCE', 1, 1, 'GENED4', 'Mathematics for the Modern World', 3.00, 1),
    ('BSCE', 1, 1, 'PROGLO', 'Programming Logic and Design', 2.00, 1),
    ('BSCE', 1, 1, 'PEDUCA', 'Self Testing Activities', 2.00, 1),
    ('BSCE', 1, 1, 'CWTS11', 'NSTP 1 (Civic Welfare Training Service 1)', 3.00, 1),
    ('BSCE', 1, 2, 'CALCU2', 'Calculus 2', 3.00, 1),
    ('BSCE', 1, 2, 'ENPHY1', 'Physics for Engineers', 4.00, 1),
    ('BSCE', 1, 2, 'OOPROG', 'Object Oriented Programming', 3.00, 1),
    ('BSCE', 1, 2, 'GENED2', 'Readings in Philippine History', 3.00, 1),
    ('BSCE', 1, 2, 'ENDATA', 'Engineering Data Analysis', 3.00, 1),
    ('BSCE', 1, 2, 'DISCRT', 'Discrete Mathematics', 3.00, 1),
    ('BSCE', 1, 2, 'PEDUC2', 'Rhythmic Activities', 2.00, 1),
    ('BSCE', 1, 2, 'CWTS12', 'NSTP 2 (Civic Welfare Training Service 2)', 3.00, 1),
    ('BSCE', 2, 1, 'DEQUAT', 'Differential Equations', 3.00, 1),
    ('BSCE', 2, 1, 'GENED5', 'Art Appreciation', 3.00, 1),
    ('BSCE', 2, 1, 'DSALNA', 'Data Structures and Algorithms', 2.00, 1),
    ('BSCE', 2, 1, 'ECONO', 'Engineering Economics', 3.00, 1),
    ('BSCE', 2, 1, 'FELCIR', 'Fundamentals of Electrical Circuits', 4.00, 1),
    ('BSCE', 2, 1, 'GENEL1', 'General Elective (Living in the IT Era)', 3.00, 0),
    ('BSCE', 2, 1, 'CADRA', 'Computer Aided Drafting', 1.00, 1),
    ('BSCE', 2, 1, 'PEDUC3', 'Games and Sports', 2.00, 1),
    ('BSCE', 2, 2, 'NUMETH', 'Numerical Methods', 3.00, 1),
    ('BSCE', 2, 2, 'SDESIG', 'Software Design', 4.00, 1),
    ('BSCE', 2, 2, 'GENED6', 'Purposive Communication', 3.00, 1),
    ('BSCE', 2, 2, 'FLECTRO', 'Fundamentals of Electronics Circuits', 4.00, 1),
    ('BSCE', 2, 2, 'GENED9', 'Rizal''s Life and Works', 3.00, 1),
    ('BSCE', 2, 2, 'GENED3', 'The Contemporary World', 3.00, 1),
    ('BSCE', 2, 2, 'PEDUC4', 'Recreational Activities', 2.00, 1),
    ('BSCE', 3, 1, 'LCDES', 'Logic Circuits and Design', 4.00, 1),
    ('BSCE', 3, 1, 'OESYS', 'Operating Systems', 3.00, 1),
    ('BSCE', 3, 1, 'DATCOM', 'Data and Digital Communications', 3.00, 1),
    ('BSCE', 3, 1, 'INTROL', 'Introduction to HDL', 1.00, 1),
    ('BSCE', 3, 1, 'ECOSYS', 'Feedback and Control Systems', 3.00, 1),
    ('BSCE', 3, 1, 'FAMISS', 'Fundamentals of Mixed Signals and Sensors', 3.00, 1),
    ('BSCE', 3, 1, 'CODECAD', 'Computer Engineering Drafting and Design', 1.00, 1),
    ('BSCE', 3, 1, 'COELE1', 'Cognate/Elective Course 1', 3.00, 0),
    ('BSCE', 3, 2, 'BOHSAF', 'Basic Occupational Health and Safety', 3.00, 1),
    ('BSCE', 3, 2, 'NETSEC', 'Computer Networks and Security', 4.00, 1),
    ('BSCE', 3, 2, 'MSYSTE', 'Microprocessor', 4.00, 1),
    ('BSCE', 3, 2, 'MRESEA', 'Methods of Research', 2.00, 1),
    ('BSCE', 3, 2, 'TECHNO', 'Technopreneurship', 3.00, 1),
    ('BSCE', 3, 2, 'GENED8', 'Ethics', 3.00, 1),
    ('BSCE', 3, 2, 'COELAW', 'CpE Laws and Professional Practice', 2.00, 1),
    ('BSCE', 3, 2, 'COELE2', 'Cognate/Elective Course 2', 3.00, 0),
    ('BSCE', 4, 1, 'EMSYS', 'Embedded Systems', 4.00, 1),
    ('BSCE', 4, 1, 'COMORG', 'Computer Architecture and Organization', 4.00, 1),
    ('BSCE', 4, 1, 'EMTECH', 'Emerging Technologies in CpE', 3.00, 1),
    ('BSCE', 4, 1, 'DPRD01', 'CpE Practice and Design 1', 1.00, 1),
    ('BSCE', 4, 1, 'DSPROG', 'Digital Signal Processing', 4.00, 1),
    ('BSCE', 4, 1, 'GENEL2', 'General Elective 2 (The Entrepreneurial Mind)', 3.00, 0),
    ('BSCE', 4, 1, 'COELE3', 'Cognate/Elective Course 3', 3.00, 0),
    ('BSCE', 4, 2, 'DPRD02', 'CpE Practice and Design 2', 2.00, 1),
    ('BSCE', 4, 2, 'SEMINAR', 'Seminars and Fieldtrips', 1.00, 1),
    ('BSCE', 4, 2, 'PRACUM', 'On the Job Training (240 Hrs)', 3.00, 1),
    ('BSCE', 4, 2, 'GENEL3', 'General Elective 3 (Philippine Popular Culture)', 3.00, 0),
    ('BSCS', 1, 1, 'FCMATH', 'Fundamental Concepts of Mathematics', 3.00, 1),
    ('BSCS', 1, 1, 'ENGPLU1', 'English Plus', 3.00, 1),
    ('BSCS', 1, 1, 'CPROG1', 'Computer Programming 1 (Fundamentals of Programming)', 3.00, 1),
    ('BSCS', 1, 1, 'CWTS11', 'NSTP 1', 3.00, 1),
    ('BSCS', 1, 1, 'GENED5', 'Purposive Communication', 3.00, 1),
    ('BSCS', 1, 1, 'GENED6', 'Art Appreciation', 3.00, 1),
    ('BSCS', 1, 1, 'GENED7', 'Science, Technology, and Society', 3.00, 1),
    ('BSCS', 1, 1, 'GENED8', 'Ethics', 3.00, 1),
    ('BSCS', 1, 1, 'GENEL1', 'General Elective (Living in the IT Era)', 3.00, 0),
    ('BSCS', 1, 1, 'CCFUND', 'Introduction to Computing', 3.00, 1),
    ('BSCS', 1, 1, 'PEDUC1', 'Self Testing Activities', 2.00, 1),
    ('BSCS', 1, 2, 'CWTS12', 'NSTP 2', 3.00, 1),
    ('BSCS', 1, 2, 'CPROG2', 'Computer Programming 2 (Intermediate Programming)', 3.00, 1),
    ('BSCS', 1, 2, 'DISCR1', 'Discrete Structures 1', 3.00, 1),
    ('BSCS', 1, 2, 'GENED1', 'Understanding the Self', 3.00, 1),
    ('BSCS', 1, 2, 'GENED2', 'Readings in Philippine History', 3.00, 1),
    ('BSCS', 1, 2, 'GENED3', 'The Contemporary World', 3.00, 1),
    ('BSCS', 1, 2, 'HUMAN1', 'Human Computer Interaction', 3.00, 1),
    ('BSCS', 1, 2, 'PEDUC2', 'Rhythmic Activities', 2.00, 1),
    ('BSCS', 2, 1, 'ALGCOM', 'Algorithms and Complexity', 3.00, 1),
    ('BSCS', 2, 1, 'DISCR2', 'Discrete Structures 2', 3.00, 1),
    ('BSCS', 2, 1, 'DSALGO', 'Data Structures and Algorithms', 3.00, 1),
    ('BSCS', 2, 1, 'GENEL2', 'General Elective 2', 3.00, 0),
    ('BSCS', 2, 1, 'GENEL3', 'General Elective 3', 3.00, 0),
    ('BSCS', 2, 1, 'OOPROG', 'Object Oriented Programming', 3.00, 1),
    ('BSCS', 2, 1, 'PEDUC3', 'Games and Sports', 2.00, 1),
    ('BSCS', 2, 2, 'GENED4', 'Mathematics in the Modern World', 3.00, 1),
    ('BSCS', 2, 2, 'GENED9', 'Rizal''s Life and Works', 3.00, 1),
    ('BSCS', 2, 2, 'INFORM', 'Information Management 1', 3.00, 1),
    ('BSCS', 2, 2, 'CALCU2', 'Math Elective (Calculus 2)', 3.00, 0),
    ('BSCS', 2, 2, 'PEDUC4', 'Recreational Activities', 2.00, 1),
    ('BSCS', 3, 1, 'ADETEC', 'Application Development and Emerging Technologies', 3.00, 1),
    ('BSCS', 3, 1, 'ARCOIG', 'Architecture and Organization', 3.00, 1),
    ('BSCS', 3, 1, 'CSELE1', 'CS Elective 1 (Graphics and Visual Computing)', 3.00, 0),
    ('BSCS', 3, 1, 'CSO204', 'Automata Theory and Formal Languages', 3.00, 1),
    ('BSCS', 3, 1, 'IASEC1', 'Information Assurance and Security', 3.00, 1),
    ('BSCS', 3, 2, 'CSELE2', 'CS Elective 2 (Parallel and Distributed Computing)', 3.00, 0),
    ('BSCS', 3, 2, 'CSELE3', 'CS Elective 3 (Intelligent Systems)', 3.00, 0),
    ('BSCS', 3, 2, 'CSO203', 'Programming Languages', 3.00, 1),
    ('BSCS', 3, 2, 'CSO207', 'Operating Systems', 3.00, 1),
    ('BSCS', 3, 2, 'SOCPRO', 'Social Issues and Professional Practice 1', 3.00, 1),
    ('BSCS', 3, 2, 'SOFEN1', 'Software Engineering 1', 3.00, 1),
    ('BSCS', 4, 1, 'CSO500', 'Practicum', 3.00, 1),
    ('BSCS', 4, 1, 'CSO501', 'Thesis 1', 3.00, 1),
    ('BSCS', 4, 1, 'SOFEN2', 'Software Engineering 2', 3.00, 1),
    ('BSCS', 4, 2, 'CSO502', 'Thesis 2', 3.00, 1),
    ('BSCS', 4, 2, 'NETCOM', 'Networks and Communications', 3.00, 1),
    ('BSELE', 1, 1, 'FCMATH', 'Fundamental Concepts of Mathematics', 3.00, 1),
    ('BSELE', 1, 1, 'ENGPLU1', 'English Plus', 3.00, 1),
    ('BSELE', 1, 1, 'CALCU1', 'Calculus 1', 3.00, 1),
    ('BSELE', 1, 1, 'CWTS11', 'NSTP 1', 3.00, 1),
    ('BSELE', 1, 1, 'ENDATA', 'Engineering Data Analysis', 3.00, 1),
    ('BSELE', 1, 1, 'GENCHE', 'Chemistry for Engineers', 4.00, 1),
    ('BSELE', 1, 1, 'GENED1', 'Understanding the Self', 3.00, 1),
    ('BSELE', 1, 1, 'GENED4', 'Mathematics for the Modern World', 3.00, 1),
    ('BSELE', 1, 1, 'GENED7', 'Science, Technology, and Society', 3.00, 1),
    ('BSELE', 1, 1, 'PEDUC1', 'Self Testing Activities', 2.00, 1),
    ('BSELE', 1, 2, 'CALCU2', 'Calculus 2', 3.00, 1),
    ('BSELE', 1, 2, 'GENPROG', 'Computer Programming', 2.00, 1),
    ('BSELE', 1, 2, 'CWTS12', 'NSTP 2', 3.00, 1),
    ('BSELE', 1, 2, 'ENPHY1', 'Physics for Engineers 1', 4.00, 1),
    ('BSELE', 1, 2, 'ENPHY2', 'Physics for Engineers 2', 4.00, 1),
    ('BSELE', 1, 2, 'GENED2', 'Readings in Philippine History', 3.00, 1),
    ('BSELE', 1, 2, 'MSCIEN', 'Materials Science and Engineering', 3.00, 1),
    ('BSELE', 1, 2, 'PEDUC2', 'Rhythmic Activities', 2.00, 1),
    ('BSELE', 2, 1, 'CADRAF', 'Computer Aided Drafting', 1.00, 1),
    ('BSELE', 2, 1, 'CALCU3', 'Calculus 3', 4.00, 1),
    ('BSELE', 2, 1, 'DEQUAT', 'Differential Equations', 3.00, 1),
    ('BSELE', 2, 1, 'ECELEC', 'ECE Laws, Contracts, Ethics, Standards and Safety', 3.00, 1),
    ('BSELE', 2, 1, 'ECONOM', 'Engineering Economics', 3.00, 1),
    ('BSELE', 2, 1, 'ELEC1', 'Electronics 1 (Electronic Devices and Circuit)', 4.00, 1),
    ('BSELE', 2, 1, 'GENED5', 'Art Appreciation', 3.00, 1),
    ('BSELE', 2, 1, 'GENEL1', 'General Elective (Living in the IT Era)', 3.00, 0),
    ('BSELE', 2, 1, 'PEDUC3', 'Games and Sports', 2.00, 1),
    ('BSELE', 2, 2, 'ANUMAT', 'Advanced Engineering Mathematics for ECE', 4.00, 1),
    ('BSELE', 2, 2, 'CIRCU2', 'Circuits 2', 4.00, 1),
    ('BSELE', 2, 2, 'ENELAN', 'Electronics 1 (Electronic Circuit Analysis and Design)', 4.00, 1),
    ('BSELE', 2, 2, 'ELECMAG', 'Electromagnetics', 3.00, 1),
    ('BSELE', 2, 2, 'EMANAG', 'Engineering Management', 2.00, 1),
    ('BSELE', 2, 2, 'GENED9', 'Rizal''s Life and Works', 3.00, 1),
    ('BSELE', 2, 2, 'PEDUC4', 'Recreational Activities', 2.00, 1),
    ('BSELE', 2, 2, 'PFCOMM', 'Fundamentals of Communication Systems', 4.00, 1),
    ('BSELE', 3, 1, 'GENED3', 'The Contemporary World', 3.00, 1),
    ('BSELE', 3, 1, 'GENED6', 'Purposive Communication', 3.00, 1),
    ('BSELE', 3, 1, 'GENED8', 'Ethics', 3.00, 1),
    ('BSELE', 3, 1, 'LCTHE', 'Digital Electronics 1 (Logic Circuit and Switching Theory)', 4.00, 1),
    ('BSELE', 3, 1, 'MCOTEC', 'Communication 2 (Modulation and Coding Techniques)', 4.00, 1),
    ('BSELE', 3, 1, 'SSNPRO', 'Signals, Spectra and Signal Processing', 4.00, 1),
    ('BSELE', 3, 2, 'TMASYS', 'Communications 3 (Transmission Media and Antenna System)', 4.00, 1),
    ('BSELE', 3, 2, 'DATCOM', 'Communication 4 (Data Communications)', 4.00, 1),
    ('BSELE', 3, 2, 'ECOSYS', 'Feedback and Control Systems', 4.00, 1),
    ('BSELE', 3, 2, 'GENEL2', 'General Elective 2', 3.00, 0),
    ('BSELE', 3, 2, 'MSYSTE', 'Digital Electronics 2 (Microprocessor and Microcontroller Systems)', 4.00, 1),
    ('BSELE', 3, 2, 'TECHNO', 'Technopreneurship', 3.00, 1),
    ('BSELE', 4, 1, 'CPRD01', 'Design 1 (Capstone Project 1)', 1.00, 1),
    ('BSELE', 4, 1, 'ECELE1', 'ECE Elective 1 (Advanced Communication System and Design/Antenna)', 4.00, 0),
    ('BSELE', 4, 1, 'ENVSCI', 'Environmental Science and Engineering', 3.00, 1),
    ('BSELE', 4, 1, 'EPRBOT', 'ECE Elective (Robotics Technology)', 3.00, 0),
    ('BSELE', 4, 1, 'GENEL3', 'General Elective 3', 3.00, 0),
    ('BSELE', 4, 1, 'MRESEA', 'Methods of Research', 3.00, 1),
    ('BSELE', 4, 2, 'COMCLA', 'Comprehensive Class', 1.00, 1),
    ('BSELE', 4, 2, 'DPRD02', 'Design 2 (Capstone Project 2)', 1.00, 1),
    ('BSELE', 4, 2, 'ECELE2', 'ECE Elective 2 (Advanced Networking)', 4.00, 0),
    ('BSELE', 4, 2, 'PRACUM', 'On the Job Training (240 hrs)', 3.00, 1),
    ('BSELE', 4, 2, 'SEMINR', 'Seminars and Field Trips', 1.00, 1),
    ('BSIT', 1, 1, 'FCMATH', 'Fundamental Concepts of Mathematics', 3.00, 1),
    ('BSIT', 1, 1, 'ENGPLU1', 'English Plus', 3.00, 1),
    ('BSIT', 1, 1, 'CPROG1', 'Computer Programming 1 (Fundamentals of Programming)', 3.00, 1),
    ('BSIT', 1, 1, 'CWTS11', 'NSTP 1', 3.00, 1),
    ('BSIT', 1, 1, 'GENED6', 'Purposive Communication', 3.00, 1),
    ('BSIT', 1, 1, 'GENED5', 'Art Appreciation', 3.00, 1),
    ('BSIT', 1, 1, 'GENED7', 'Science, Technology, and Society', 3.00, 1),
    ('BSIT', 1, 1, 'GENED8', 'Ethics', 3.00, 1),
    ('BSIT', 1, 1, 'GENEL1', 'General Elective (Living in the IT Era)', 3.00, 0),
    ('BSIT', 1, 1, 'CCFUND', 'Introduction to Computing', 3.00, 1),
    ('BSIT', 1, 1, 'PEDUC1', 'Self Testing Activities', 2.00, 1),
    ('BSIT', 1, 2, 'CPROG2', 'Computer Programming 2 (Intermediate Programming)', 3.00, 1),
    ('BSIT', 1, 2, 'CWTS12', 'NSTP 2', 3.00, 1),
    ('BSIT', 1, 2, 'DISCRT', 'Discrete Mathematics', 3.00, 1),
    ('BSIT', 1, 2, 'GENED1', 'Understanding the Self', 3.00, 1),
    ('BSIT', 1, 2, 'GENED2', 'Readings in Philippine History', 3.00, 1),
    ('BSIT', 1, 2, 'GENED3', 'The Contemporary World', 3.00, 1),
    ('BSIT', 1, 2, 'HUMAN1', 'Introduction to Human and Computer Interaction', 3.00, 1),
    ('BSIT', 1, 2, 'PEDUC2', 'Rhythmic Activities', 2.00, 1),
    ('BSIT', 2, 1, 'DSALGO', 'Data Structures and Algorithms', 3.00, 1),
    ('BSIT', 2, 1, 'GENEL2', 'General Elective 2', 3.00, 0),
    ('BSIT', 2, 1, 'GENEL3', 'General Elective 3', 3.00, 0),
    ('BSIT', 2, 1, 'OOPROG', 'Object Oriented Programming', 3.00, 1),
    ('BSIT', 2, 1, 'PEDUC3', 'Games and Sports', 2.00, 1),
    ('BSIT', 2, 1, 'PLTECH', 'Elective 2 (Platform Technologies)', 3.00, 0),
    ('BSIT', 2, 2, 'GENED4', 'Mathematics in the Modern World', 3.00, 1),
    ('BSIT', 2, 2, 'GENED9', 'Rizal''s Life and Works', 3.00, 1),
    ('BSIT', 2, 2, 'INFORM', 'Information Management 1', 3.00, 1),
    ('BSIT', 2, 2, 'INPROG', 'Integrative Programming and Technologies', 3.00, 1),
    ('BSIT', 2, 2, 'NETWO1', 'Networking 1', 3.00, 1),
    ('BSIT', 2, 2, 'PEDUC4', 'Recreational Activities', 2.00, 1),
    ('BSIT', 2, 2, 'QUANM', 'Quantitative Methods (including Modeling and Simulation)', 3.00, 1),
    ('BSIT', 3, 1, 'ADMSYS', 'Advanced Database Systems', 3.00, 1),
    ('BSIT', 3, 1, 'NETWO2', 'Networking 2', 3.00, 1),
    ('BSIT', 3, 1, 'SYSINT1', 'Systems Integration and Architecture 1', 3.00, 1),
    ('BSIT', 3, 1, 'WEBSYS', 'Elective 3 (Web Systems and Technologies)', 3.00, 0),
    ('BSIT', 3, 2, 'ADETEC', 'Application Development and Emerging Technologies', 3.00, 1),
    ('BSIT', 3, 2, 'IASEC1', 'Information Assurance and Security 1', 1.00, 1),
    ('BSIT', 3, 2, 'SYSAD1', 'Systems Administration and Maintenance', 3.00, 1),
    ('BSIT', 3, 2, 'SOCPRO', 'Social and Professional Issues', 3.00, 1),
    ('BSIT', 4, 1, 'IASEC2', 'Information Assurance and Security 2', 3.00, 1),
    ('BSIT', 4, 1, 'THESI1', 'Capstone Project 1', 3.00, 1),
    ('BSIT', 4, 2, 'ITOPRA', 'Practicum', 6.00, 1),
    ('BSIT', 4, 2, 'SYSINT2', 'Elective 4 (Systems Integration and Architecture 2)', 3.00, 0),
    ('BSIT', 4, 2, 'THESI2', 'Capstone Project 2', 3.00, 1);

START TRANSACTION;

INSERT INTO subjects (subject_code, subject_name, units, subject_type, status)
SELECT imported.subject_code,
       imported.subject_name,
       imported.units,
       imported.subject_type,
       'Active'
FROM (
    SELECT subject_code,
           MIN(subject_name) AS subject_name,
           MIN(units) AS units,
           CASE
               WHEN MIN(is_required) = 0 AND MAX(is_required) = 0 THEN 'Elective'
               ELSE 'Core'
           END AS subject_type
    FROM curriculum_import
    GROUP BY subject_code
) AS imported
WHERE NOT EXISTS (
    SELECT 1 FROM subjects AS existing WHERE existing.subject_code = imported.subject_code
);

UPDATE curriculum AS c
INNER JOIN programs AS p ON p.program_id = c.program_id
INNER JOIN academic_years AS ay ON ay.academic_year_id = c.academic_year_id
INNER JOIN subjects AS s ON s.subject_id = c.subject_id
INNER JOIN curriculum_import AS imported
    ON imported.program_code = p.program_code
   AND imported.subject_code = s.subject_code
   AND imported.year_level = c.year_level
   AND imported.semester_number = c.semester_number
SET c.subject_name_override = imported.subject_name,
    c.units_override = imported.units,
    c.is_required = imported.is_required
WHERE ay.year_name = '2026-2027'
  AND ay.status = 'Active';

INSERT INTO curriculum (
    program_id, subject_id, subject_name_override, units_override,
    academic_year_id, year_level, semester_number, is_required
)
SELECT p.program_id,
       s.subject_id,
       imported.subject_name,
       imported.units,
       ay.academic_year_id,
       imported.year_level,
       imported.semester_number,
       imported.is_required
FROM curriculum_import AS imported
INNER JOIN programs AS p ON p.program_code = imported.program_code AND p.status = 'Active'
INNER JOIN subjects AS s ON s.subject_code = imported.subject_code
INNER JOIN academic_years AS ay ON ay.year_name = '2026-2027' AND ay.status = 'Active'
WHERE NOT EXISTS (
    SELECT 1
    FROM curriculum AS existing
    WHERE existing.program_id = p.program_id
      AND existing.subject_id = s.subject_id
      AND existing.academic_year_id = ay.academic_year_id
      AND existing.year_level = imported.year_level
      AND existing.semester_number = imported.semester_number
);

COMMIT;
DROP TEMPORARY TABLE curriculum_import;
