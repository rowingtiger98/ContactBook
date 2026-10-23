# ContactBook

PHP web application hosted at Bluehost.

## Setup

1. Create a MySQL database and user in Bluehost cPanel.
2. Create the tables below in phpMyAdmin. The SQL scripts are kept locally, not in the repository.
3. Copy `config.example.php` to `config.php` and fill in the database credentials and session path.
   `config.php` is git-ignored — upload it to the server separately.
4. Set `APP_BASE_URL` and `MAIL_FROM` in `config.php` (used for password reset emails).
5. Create the first overlord login over SSH: `php tools/create_user.php <email> overlord` — usernames are email
   addresses. After that, overlords can add users from the dashboard's **Create a New User** tool.

## Database tables

| Table | Holds | Key columns |
|---|---|---|
| `CB_User_Accounts` | Logins | `CB_Username` (an email, unique), `CB_User_UUID`, `CB_User_Name`, `CB_Password` (VARCHAR(255) for `password_hash()`; NULL until a new user sets it), `CB_User_Role` (`admin` / `overlord`), `CB_Password_Reset_ID`, `CB_Password_Reset_Timer` (when the emailed link expires) |
| `CB_Created_Books` | One row per contact book | `CB_Book_UUID`, `CB_Book_Name_About`, `CB_Base_Directory`, `CB_Book_Cover_Picture`, `CB_Book_Description`, `CB_Book_Admins` (comma-separated emails of the users who can manage the book; the creator is added automatically, along with any additional admins entered on the Create a New Book form) |
| `CB_Book_Entry` | One row per entry added to a book | `ENTRY_UUID`, `ENTRY_Book_Parent_UUID` (= `CB_Book_UUID`), `ENTRY_Name`, `ENTRY_Emails`, `ENTRY_Street_Address`, `ENTRY_City`, `ENTRY_State`, `ENTRY_Zip`, `ENTRY_Phone`, `ENTRY_Notes` |

Each table also has an `AUTO_INCREMENT` primary key (`CB_User_ID`, `CB_Book_ID`, `ENTRY_ID`).

## Books and photos

Each book gets a folder at `books/<CB_Book_UUID>/` with its cover photo and a `pictures/` folder; photos
added with an entry go in `books/<CB_Book_UUID>/pictures/<ENTRY_UUID>/`. These folders are created on the
server and are not in the repository. Upload `books/.htaccess` and `.user.ini` (both dot-files) — they block
folder listings and raise PHP's upload limits.

## Admin login

The **Admin** link on the home page opens the login panel. After logging in, users go to `admin.php`;
admins see the admin tools, and overlords also see overlord-level tools (such as **Create a New User**).
**Manage a Book** lists the books whose `CB_Book_Admins` include the logged-in user's email; overlords see every book.
Sessions time out after 30 minutes idle.

## Forgot password

The login panel's **Forgot your password?** link goes to `account_reset.php`. The user enters their email and
gets a one-time reset link (valid 1 hour); following it lets them set a new password.

## AJAX handlers

Never call `$_POST['method']();` directly — it lets a request run any PHP function by name. AJAX handler
files use `dispatch_post_method()` from `ajaxDispatch.php` with an explicit list of public and logged-in-only
methods (see the comment at the top of that file).
