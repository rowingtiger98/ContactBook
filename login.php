<?php
/**
 * login.php
 *
 * Included at the top of any page that shows the ADMIN login panel. Handles the
 * login form POST and sets $error / $loginerrorclass for login_modal.php.
 */
require_once 'SessionBootstrap.php';
require_once 'databaseFunctions.php';
app_session_start();

$error = ''; // Variable To Store Error Message

// A message left by app_logout() (e.g. idle timeout) - show it once.
if (!empty($_SESSION['login_error_description'])) {
    $error = $_SESSION['login_error_description'];
    unset($_SESSION['login_error_description']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $connection = Create_SQL_PHP_DataObject();

        $stmt = $connection->prepare('SELECT CB_Password FROM CB_User_Accounts WHERE CB_Username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        // CB_Password is NULL for a new user who hasn't chosen a password yet.
        if ($row && is_string($row['CB_Password']) && password_verify($password, $row['CB_Password'])) {
            app_login_success($username); // Initializing Session (regenerates session ID)
            header('Location: admin.php');
            exit();
        }

        // Same message for unknown user and bad password so usernames can't be probed.
        $error = 'Invalid username or password.';
    }
}

$_SESSION['login_error'] = ($error !== '');
$loginerrorclass = ($error !== '') ? ' errorevent' : ''; // keeps the login panel open to show the error
