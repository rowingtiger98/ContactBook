# ContactBook

PHP web application hosted at Bluehost.

## Setup

1. Create a MySQL database and user in Bluehost cPanel.
2. If the `CB_User_Accounts` table was created by hand in phpMyAdmin, run `sql/001_fix_users_table.sql` once; for a fresh database run `sql/schema.sql`.
3. Copy `config.example.php` to `config.php` and fill in the database credentials and session path.
   `config.php` is git-ignored — upload it to the server separately.
4. Set `APP_BASE_URL` and `MAIL_FROM` in `config.php` (used for password reset emails).
5. Create an admin login over SSH: `php tools/create_user.php <email> admin` — usernames are email addresses.

## Admin login

The **Admin** link on the home page opens the login panel. After logging in, users go to `admin.php`;
tools there are shown only to users with the `admin` role. Sessions time out after 30 minutes idle.

## Forgot password

The login panel's **Forgot your password?** link goes to `account_reset.php`. The user enters their email and
gets a one-time reset link (valid 1 hour); following it lets them set a new password.

## AJAX handlers

Never call `$_POST['method']();` directly — it lets a request run any PHP function by name. AJAX handler
files use `dispatch_post_method()` from `ajaxDispatch.php` with an explicit list of public and logged-in-only
methods (see the comment at the top of that file).
