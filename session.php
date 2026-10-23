<?php
/**
 * session.php
 *
 * Included at the top of any page that requires a logged-in user. Redirects to
 * index.php if not logged in / idle-timed-out, then loads the user's details into
 * $login_username, $login_userid and $login_user_role.
 */
require_once 'SessionBootstrap.php';
require_once 'databaseFunctions.php';

app_session_start();
app_require_login(); // Redirects (with exit) if not logged in or idle-timed-out

$connection = Create_SQL_PHP_DataObject();

$stmt = $connection->prepare('SELECT CB_User_ID, CB_Username, CB_User_Role FROM CB_User_Accounts WHERE CB_Username = ?');
$stmt->execute([$_SESSION['login_user']]);
$row = $stmt->fetch();

if (!$row) {
    // Session cookie is valid but the account is gone (e.g. deleted) - treat as logged out.
    app_logout('index.php', 'Your account could not be found. Please log in again.');
}

$login_username  = $row['CB_Username'];
$login_userid    = $row['CB_User_ID'];
$login_user_role = $row['CB_User_Role'];

$stmt = null;       // Release the statement too - it holds a reference to the connection
$connection = null; // Closing Connection
