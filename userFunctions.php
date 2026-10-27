<?php
/*-------------------------------------------------------------*/
/*                                                             */
/*  USER FUNCTIONS                                             */
/*                                                             */
/*  Logins live in CB_User_Accounts. CB_User_Role is one of:   */
/*    overlord - every admin tool, including overlord-level    */
/*               tools such as Create a New User.              */
/*    admin    - the admin tools, minus overlord-level ones.   */
/*                                                             */
/*  New users are created without a password: they're emailed  */
/*  a link to account_reset.php to choose one.                 */
/*                                                             */
/*-------------------------------------------------------------*/
require_once __DIR__ . '/databaseFunctions.php';
require_once __DIR__ . '/passwordReset.php';
require_once __DIR__ . '/bookFunctions.php';  // generate_uuid_v4()

const USER_ROLE_OVERLORD = 'overlord';
const USER_ROLE_ADMIN    = 'admin';
const USER_ROLES         = [USER_ROLE_ADMIN => 'Admin', USER_ROLE_OVERLORD => 'Overlord'];  // value => label

const USER_NAME_MAX_LENGTH  = 64;   // CB_User_Name VARCHAR(64)
const USER_EMAIL_MAX_LENGTH = 128;  // CB_Username VARCHAR(128)

/* *********************************************************** */
/* Function:    user_is_admin                                  */
/* Description: Whether a role may use the admin dashboard.    */
/*              Overlords can do everything admins can.        */
/* Inputs:      $role - the user's CB_User_Role.               */
/* Returns:     true for admin and overlord.                   */
/* *********************************************************** */
function user_is_admin(?string $role): bool {
    return $role === USER_ROLE_ADMIN || $role === USER_ROLE_OVERLORD;
}

/* *********************************************************** */
/* Function:    user_is_overlord                               */
/* Description: Whether a role may use overlord-level tools.   */
/* Inputs:      $role - the user's CB_User_Role.               */
/* Returns:     true for overlord only.                        */
/* *********************************************************** */
function user_is_overlord(?string $role): bool {
    return $role === USER_ROLE_OVERLORD;
}

/* *********************************************************** */
/* Function:    validate_new_user                              */
/* Description: Checks the Create a New User form fields.      */
/* Inputs:      $name, $email, $role - the cleaned fields.     */
/* Returns:     An error message, or '' if everything is OK.   */
/* *********************************************************** */
function validate_new_user(string $name, string $email, string $role): string {
    if ($name === '') {
        return 'Please enter the new user\'s name.';
    }
    if (mb_strlen($name, 'UTF-8') > USER_NAME_MAX_LENGTH) {
        return 'The name can be at most ' . USER_NAME_MAX_LENGTH . ' characters.';
    }
    // Same rule as book and entry names: letters in any language, digits and name punctuation.
    if (!preg_match("/^[\p{L}\p{M}0-9 .,'’()&-]+$/u", $name)) {
        return 'The name can only contain letters, numbers, spaces and . , \' - ( ) &';
    }
    if ($email === '') {
        return 'Please enter the new user\'s email address.';
    }
    if (strlen($email) > USER_EMAIL_MAX_LENGTH || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return 'Please enter a valid email address.';
    }
    if (!array_key_exists($role, USER_ROLES)) {
        return 'Please choose a role.';
    }
    return '';
}

/* *********************************************************** */
/* Function:    create_user_account                            */
/* Description: Adds a login to CB_User_Accounts with a new    */
/*              UUID and no password, then emails the user a   */
/*              link to choose their password. The link lasts  */
/*              INVITE_TOKEN_LIFETIME; after that they can use */
/*              "Forgot your password?" like anyone else.      */
/* Inputs:      $name - the new user's name.                   */
/*              $email - their email (their username).         */
/*              $role - 'admin' or 'overlord'.                 */
/* Returns:     ['success' => bool, 'error' => string,         */
/*               'email_sent' => bool, 'name', 'email',        */
/*               'role' => the cleaned fields]                 */
/* *********************************************************** */
function create_user_account(string $name, string $email, string $role): array {
    $name  = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    $email = trim($email);
    $result = ['success' => false, 'error' => '', 'email_sent' => false, 'name' => $name, 'email' => $email, 'role' => $role];

    $result['error'] = validate_new_user($name, $email, $role);
    if ($result['error'] !== '') {
        return $result;
    }

    $reset = new_reset_token(INVITE_TOKEN_LIFETIME);
    try {
        $connection = Create_SQL_PHP_DataObject();

        // CB_Username is unique, but check first for a friendlier message.
        $stmt = $connection->prepare('SELECT 1 FROM CB_User_Accounts WHERE CB_Username = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $result['error'] = 'There is already an account for ' . $email . '.';
            return $result;
        }

        $stmt = $connection->prepare(
            'INSERT INTO CB_User_Accounts
                (CB_Username, CB_User_Name, CB_User_UUID, CB_User_Role, CB_Password, CB_Password_Reset_ID, CB_Password_Reset_Timer)
             VALUES (?, ?, ?, ?, NULL, ?, ?)'
        );
        $stmt->execute([$email, $name, generate_uuid_v4(), $role, $reset['hash'], $reset['expires']]);
        $stmt = null;
        $connection = null;
    } catch (PDOException $e) {
        error_log('ContactBook: could not create user ' . $email . ': ' . $e->getMessage());
        $result['error'] = 'The user could not be created. Please try again.';
        return $result;
    }

    $result['success']    = true;
    $result['email_sent'] = send_welcome_email($email, $name, $reset['link']);
    if (!$result['email_sent']) {
        error_log('ContactBook: welcome email to ' . $email . ' failed to send');
    }
    return $result;
}
