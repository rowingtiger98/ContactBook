<?php
/*-------------------------------------------------------------*/
/*                                                             */
/*  PASSWORD RESET FUNCTIONS                                   */
/*                                                             */
/*  Forgot-password flow, adapted from NSXCommon:              */
/*    1. reset_request()  - user enters their email; a one-   */
/*                          time link is emailed to them.     */
/*    2. reset_lookup()   - checks a link's token is valid.   */
/*    3. reset_complete() - sets the new password and burns   */
/*                          the token so the link only works  */
/*                          once.                             */
/*                                                             */
/*  Only a SHA-256 hash of the token is stored in              */
/*  CB_Password_Reset_ID, so someone who can read the table    */
/*  still can't use a pending reset link.                      */
/*                                                             */
/*-------------------------------------------------------------*/
require_once __DIR__ . '/databaseFunctions.php';

const RESET_TOKEN_LIFETIME = 3600;  // seconds a reset link stays valid (1 hour)
const PASSWORD_MIN_LENGTH  = 8;

/* *********************************************************** */
/* Function:    reset_request                                  */
/* Description: If $email belongs to an account, stores a new   */
/*              reset token and emails the reset link. Does    */
/*              nothing visible otherwise, so the page can't   */
/*              be used to find out which emails have accounts.*/
/* Inputs:      $email - the username (email address) entered. */
/* Returns:     Nothing                                        */
/* *********************************************************** */
function reset_request(string $email): void {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $connection = Create_SQL_PHP_DataObject();

    $stmt = $connection->prepare('SELECT CB_User_ID FROM CB_User_Accounts WHERE CB_Username = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row) {
        return;
    }

    $token = bin2hex(random_bytes(32));
    $stmt = $connection->prepare(
        'UPDATE CB_User_Accounts SET CB_Password_Reset_ID = ?, CB_Password_Reset_Timer = ? WHERE CB_User_ID = ?'
    );
    $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s'), $row['CB_User_ID']]);

    $link = APP_BASE_URL . '/account_reset.php?resetid=' . urlencode($token);
    if (!send_password_reset_email($email, $link)) {
        error_log('ContactBook: password reset email to ' . $email . ' failed to send');
    }
}

/* *********************************************************** */
/* Function:    reset_lookup                                   */
/* Description: Checks a reset token from an emailed link.     */
/* Inputs:      $token - the resetid value from the link.      */
/* Returns:     'valid', 'expired', or 'invalid' (unknown or   */
/*              already used).                                 */
/* *********************************************************** */
function reset_lookup(string $token): string {
    if ($token === '') {
        return 'invalid';
    }

    $connection = Create_SQL_PHP_DataObject();
    $stmt = $connection->prepare('SELECT CB_Password_Reset_Timer FROM CB_User_Accounts WHERE CB_Password_Reset_ID = ?');
    $stmt->execute([hash('sha256', $token)]);
    $row = $stmt->fetch();

    if (!$row) {
        return 'invalid';
    }
    if (strtotime($row['CB_Password_Reset_Timer']) < time() - RESET_TOKEN_LIFETIME) {
        return 'expired';
    }
    return 'valid';
}

/* *********************************************************** */
/* Function:    reset_complete                                 */
/* Description: Sets the new password for the account that     */
/*              owns $token and clears the token. The token    */
/*              check and the update are one statement, so a   */
/*              link can't be used twice or after it expires.  */
/* Inputs:      $token - the resetid value from the link.      */
/*              $password - the new password (plain text).     */
/* Returns:     true if the password was changed.              */
/* *********************************************************** */
function reset_complete(string $token, string $password): bool {
    if ($token === '' || strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }

    $connection = Create_SQL_PHP_DataObject();
    $stmt = $connection->prepare(
        'UPDATE CB_User_Accounts
            SET CB_Password = ?, CB_Password_Reset_ID = NULL, CB_Password_Reset_Timer = NULL
          WHERE CB_Password_Reset_ID = ? AND CB_Password_Reset_Timer >= ?'
    );
    $stmt->execute([
        password_hash($password, PASSWORD_DEFAULT),
        hash('sha256', $token),
        date('Y-m-d H:i:s', time() - RESET_TOKEN_LIFETIME),
    ]);

    return $stmt->rowCount() === 1;
}

/* *********************************************************** */
/* Function:    send_password_reset_email                      */
/* Description: Emails the reset link using PHP mail(), which  */
/*              Bluehost supports out of the box.              */
/* Inputs:      $to - recipient address.                       */
/*              $link - the full reset link.                   */
/* Returns:     true if mail() accepted the message.           */
/* *********************************************************** */
function send_password_reset_email(string $to, string $link): bool {
    $minutes = RESET_TOKEN_LIFETIME / 60;
    $safe_link = htmlspecialchars($link);

    $subject = 'ContactBook password reset';
    $message = '<p>We received a request to reset your ContactBook password. Click the link below to choose a new password. '
             . "This link can be used once and expires in $minutes minutes.</p>"
             . '<p><a href="' . $safe_link . '">' . $safe_link . '</a></p>'
             . '<p>If you didn\'t ask to reset your password, you can ignore this email - your password won\'t change.</p>';

    $headers = 'MIME-Version: 1.0' . "\r\n"
             . 'Content-type: text/html; charset=utf-8' . "\r\n"
             . 'From: ' . MAIL_FROM . "\r\n"
             . 'Reply-To: ' . MAIL_FROM . "\r\n";

    return mail($to, $subject, $message, $headers);
}
