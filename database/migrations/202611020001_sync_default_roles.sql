START TRANSACTION;

INSERT INTO roles (role_name, description)
VALUES
    ('Super Admin', 'Full system access'),
    ('Admin', 'School administration'),
    ('Teacher', 'Teaching, attendance, and academic management'),
    ('Student', 'Student portal access')
ON DUPLICATE KEY UPDATE
    description = VALUES(description);

DELETE FROM roles
WHERE role_name IN ('Registrar', 'Finance Officer', 'Parent');

COMMIT;
