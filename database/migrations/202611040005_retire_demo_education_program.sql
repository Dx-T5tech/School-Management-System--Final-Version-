UPDATE programs
SET status = 'Inactive'
WHERE program_code = 'DEMO-BSED'
  AND program_name = 'Demo Bachelor of Secondary Education'
  AND status <> 'Inactive';
