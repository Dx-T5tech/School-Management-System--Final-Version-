<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Core/auth.php';

function requiredEnvironmentValue(string $name): string
{
    $value = getenv($name);
    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException($name . ' must be set in the process environment.');
    }

    return trim($value);
}

try {
    $firstName = requiredEnvironmentValue('SUPER_ADMIN_FIRST_NAME');
    $lastName = requiredEnvironmentValue('SUPER_ADMIN_LAST_NAME');
    $email = requiredEnvironmentValue('SUPER_ADMIN_EMAIL');
    $username = requiredEnvironmentValue('SUPER_ADMIN_USERNAME');
    $password = getenv('SUPER_ADMIN_PASSWORD');

    if ($password === false || $password === '') {
        throw new RuntimeException('SUPER_ADMIN_PASSWORD must be set in the process environment.');
    }

    if (strlen($firstName) > 100 || strlen($lastName) > 100) {
        throw new RuntimeException('Super Admin first and last names must be at most 100 bytes each.');
    }

    if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('SUPER_ADMIN_EMAIL must be a valid email address no longer than 150 bytes.');
    }

    if (!preg_match('/\A[A-Za-z0-9._-]{3,100}\z/', $username)) {
        throw new RuntimeException('SUPER_ADMIN_USERNAME must be 3–100 letters, numbers, periods, underscores, or hyphens.');
    }

    if (strlen($password) < 12 || strlen($password) > appPasswordMaxBytes()) {
        throw new RuntimeException(
            'SUPER_ADMIN_PASSWORD must be 12–' . appPasswordMaxBytes() . ' bytes long.'
        );
    }

    $connection = getDatabaseConnection();
    $lockName = 'isms_database_super_admin_provision';
    $lockStatement = $connection->prepare('SELECT GET_LOCK(:lock_name, 10)');
    $lockStatement->execute(['lock_name' => $lockName]);

    if ((int) $lockStatement->fetchColumn() !== 1) {
        throw new RuntimeException('Could not acquire the Super Admin provisioning lock. Try again.');
    }

    $alreadyExists = false;
    try {
        $connection->beginTransaction();
        $existingAdmin = $connection->query(
            "SELECT user_id FROM users
             WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'Super Admin')
             LIMIT 1
             FOR UPDATE"
        )->fetchColumn();

        if ($existingAdmin !== false) {
            $connection->rollBack();
            $alreadyExists = true;
        } else {
            $roleStatement = $connection->prepare(
                'SELECT role_id FROM roles WHERE role_name = :role_name LIMIT 1'
            );
            $roleStatement->execute(['role_name' => 'Super Admin']);
            $roleId = $roleStatement->fetchColumn();

            if ($roleId === false) {
                throw new RuntimeException('The Super Admin role is missing. Apply the default-roles migration first.');
            }

            $insertUser = $connection->prepare(
                'INSERT INTO users
                    (role_id, first_name, last_name, email, username, password, account_status)
                 VALUES
                    (:role_id, :first_name, :last_name, :email, :username, :password, :account_status)'
            );
            $insertUser->execute([
                'role_id' => (int) $roleId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'username' => $username,
                'password' => appPasswordHash($password),
                'account_status' => 'Active',
            ]);
            $connection->commit();
        }
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        throw $exception;
    } finally {
        $releaseStatement = $connection->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $releaseStatement->execute(['lock_name' => $lockName]);
    }

    if ($alreadyExists) {
        fwrite(STDOUT, "A Super Admin account already exists; no account was changed.\n");
        exit(0);
    }

    fwrite(STDOUT, "Super Admin account created for username: " . $username . "\n");
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "A user with that username or email already exists. No account was created.\n");
        exit(1);
    }

    fwrite(STDERR, "Super Admin provisioning failed: " . $exception->getMessage() . "\n");
    exit(1);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
