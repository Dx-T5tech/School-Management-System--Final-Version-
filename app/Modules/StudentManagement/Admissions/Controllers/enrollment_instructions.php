<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/app/Core/dashboard.php';
require_once dirname(__DIR__, 2) . '/Students/Services/student.php';
require_once dirname(__DIR__) . '/Services/access.php';

$connection = getDatabaseConnection();
admissionsRedirectSignedInUser($connection);

require dirname(__DIR__) . '/Views/enrollment_instructions.php';
