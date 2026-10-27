# ContactBook

PHP web application hosted at Bluehost.

## Setup

1. Create a MySQL database and user in Bluehost cPanel.
2. Create the tables below in phpMyAdmin. The SQL scripts are kept locally, not in the repository.
3. Copy `config.example.php` to `config.php` and fill in the database credentials and session path.
   `config.php` is git-ignored — upload it to the server separately.
4. Set `APP_BASE_URL`, `MAIL_FROM` and `MAIL_FROM_NAME` in `config.php`. Every email the site sends (password
   resets, new-user invites, thank-you emails) goes through `send_site_email()` in `mailFunctions.php` and comes
   from `MAIL_FROM`, which must be an address on the site's own domain (e.g. `no-reply@kirbypark.net`, created in
   cPanel > Email Accounts). Check cPanel > Email Deliverability shows SPF and DKIM as valid for the domain.
5. Create the first overlord login over SSH: `php tools/create_user.php <email> overlord` — usernames are email
   addresses. After that, overlords can add users from the dashboard's **Create a New User** tool.

## Database tables

| Table | Holds | Key columns |
|---|---|---|
| `CB_User_Accounts` | Logins | `CB_Username` (an email, unique), `CB_User_UUID`, `CB_User_Name`, `CB_Password` (VARCHAR(255) for `password_hash()`; NULL until a new user sets it), `CB_User_Role` (`admin` / `overlord`), `CB_Password_Reset_ID`, `CB_Password_Reset_Timer` (when the emailed link expires) |
| `CB_Created_Books` | One row per contact book | `CB_Book_UUID`, `CB_Book_Name_About`, `CB_Base_Directory`, `CB_Book_Cover_Picture`, `CB_Book_Description`, `CB_Book_Admins` (comma-separated emails of the users who can manage the book; the creator is added automatically, along with any additional admins entered on the Create a New Book form) |
| `CB_Book_Entry` | One row per entry added to a book | `ENTRY_UUID`, `ENTRY_Book_Parent_UUID` (= `CB_Book_UUID`), `ENTRY_Name`, `ENTRY_Emails`, `ENTRY_Street_Address`, `ENTRY_City`, `ENTRY_State`, `ENTRY_Zip`, `ENTRY_Phone`, `ENTRY_Notes`, `ENTRY_Edit_Token` (SHA-256 hash of the entry's secret edit code), `ENTRY_Status` (`NEW`, `EDITED` or `ENTERED` - see Manage a Book) |

Each table also has an `AUTO_INCREMENT` primary key (`CB_User_ID`, `CB_Book_ID`, `ENTRY_ID`).

## Books and photos

Each book gets a folder at `books/<CB_Book_UUID>/` with its cover photo and a `pictures/` folder; photos
added with an entry go in `books/<CB_Book_UUID>/pictures/<ENTRY_UUID>/`. These folders are created on the
server and are not in the repository. Upload `books/.htaccess` and `.user.ini` (both dot-files) — they block
folder listings and raise PHP's upload limits.

## Thank-you email and edit links

After someone adds an entry, they're emailed (at the first address they gave) a thank-you with two links:
**Click Here** opens `edit_my_entry.php`, where they can edit that one entry - fields and photos, but not delete
it - and a share link to the book's Add Your Information page. The edit link holds a random code (only its hash
is stored) and doesn't expire. Entries made before edit links existed don't have one.

## Admin login

The **Admin** link on the home page opens the login panel. After logging in, users go to `admin.php`;
admins see the admin tools, and overlords also see overlord-level tools (such as **Create a New User**).
**Manage a Book** lists the books whose `CB_Book_Admins` include the logged-in user's email; overlords see every book. Choosing a book opens
its **Book Details** (name, description, admins and cover photo) and a table of its **Entries**: state, name, contact,
the start of the message and the number of photos. Each entry has **Edit**, **Mark Entered** and **Delete** buttons:
`edit_entry.php` changes any field and removes or adds photos (up to 5 in all), and delete asks first,
then removes the entry and its photos.

The **State** column (`ENTRY_Status`) helps the book's manager keep track of copying entries into their contacts:
a green **+** is a new entry (`NEW`), a yellow warning triangle means it was changed - by its author with their
edit link, or by an admin on `edit_entry.php` (`EDITED`), and a black check means the manager has entered it (`ENTERED`). **Mark Entered**
(`mark_entry_entered.php`, sent in the background by `assets/js/manage_book.js`) sets `ENTERED` and refreshes the row.
Any edit sets `EDITED` again, even after it was entered, so **Mark Entered** is available again for the changes.
Entries with no state (from before the column was added) show as new.

**Download a Book** (`download_book.php`) downloads a book's entries as a CSV file (UTF-8, one row per entry,
with the number of photos, the book and entry UUIDs and the photo filenames; photos are in
`books/<Book UUID>/pictures/<Entry UUID>/`). It lists the same books as Manage a Book,
which also has a **Download CSV** button under the Entries table. Admins can't remove themselves from a book;
overlords can.
Sessions time out after 30 minutes idle.

## Forgot password

The login panel's **Forgot your password?** link goes to `account_reset.php`. The user enters their email and
gets a one-time reset link (valid 1 hour); following it lets them set a new password.

## AJAX handlers

Never call `$_POST['method']();` directly — it lets a request run any PHP function by name. AJAX handler
files use `dispatch_post_method()` from `ajaxDispatch.php` with an explicit list of public and logged-in-only
methods (see the comment at the top of that file).
