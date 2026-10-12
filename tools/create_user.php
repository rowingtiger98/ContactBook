<?php
/**
 * tools/create_user.php
 *
 * Command-line only: creates (or resets the password of) a ContactBook login.
 *
 *   php tools/create_user.php <email> [admin|user]
 *
 * The username must be the user's email address - password reset links are sent to it.
 *
 * You'll be prompted for the password so it doesn't end up in shell history.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

require_once __DIR__ . '/../databaseFunctions.php';

$username = $argv[1] ?? '';
$role     = $argv[2] ?? 'admin';

if ($username === '' || !in_array($role, ['admin', 'user'], true)) {
    fwrite(STDERR, "Usage: php tools/create_user.php <email> [admin|user]\n");
    exit(1);
}

if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "The username must be an email address (password reset links are sent to it).\n");
    exit(1);
}

fwrite(STDOUT, 'Password: ');
system('stty -echo');
$password = trim(fgets(STDIN));
system('stty echo');
fwrite(STDOUT, "\n");

if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

$connection = Create_SQL_PHP_DataObject();
$stmt = $connection->prepare(
    'INSERT INTO CB_User_Accounts (CB_Username, CB_Password, CB_User_Role) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE CB_Password = VALUES(CB_Password), CB_User_Role = VALUES(CB_User_Role)'
);
$stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);

echo "Saved user '$username' with role '$role'.\n";
