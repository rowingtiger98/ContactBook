<?php
/*-------------------------------------------------------------*/
/*                                                             */
/*  BOOK FUNCTIONS                                             */
/*                                                             */
/*  Contact books live in CB_Created_Books. Each book gets a   */
/*  UUID and a base directory named after it in the books      */
/*  folder (~/public_html/contactbook/books/<uuid> on          */
/*  Bluehost). CB_Base_Directory stores it relative to         */
/*  public_html, e.g. contactbook/books/<uuid>. Book entries   */
/*  (CB_Book_Entry) use the book's UUID as their parent UUID.  */
/*  An optional photo is saved in the directory as cover.jpg   */
/*  or cover.png, with its filename in CB_Book_Cover_Picture.  */
/*  Each book also gets a pictures/ folder for the photos      */
/*  people add with their entries (pictures/<ENTRY_UUID>/).    */
/*  CB_Book_Admins holds the emails (usernames) of the users   */
/*  who can manage the book, comma-separated. The user who     */
/*  creates a book is added to it.                             */
/*                                                             */
/*-------------------------------------------------------------*/
require_once __DIR__ . '/databaseFunctions.php';

const BOOKS_DIR_NAME = 'books';            // every book's folder goes in <site>/books/
const BOOK_PICTURES_DIR_NAME = 'pictures';  // entry photos go in books/<uuid>/pictures/<entry uuid>/
const BOOK_NAME_MAX_LENGTH = 64;  // CB_Book_Name_About is VARCHAR(64)
const BOOK_DESCRIPTION_MAX_LENGTH = 1000;
const BOOK_PHOTO_MAX_BYTES     = 10 * 1048576;  // 10 MB - keep in step with .user.ini
const BOOK_PHOTO_MAX_PIXELS    = 24000000;      // 24 megapixels - larger images need too much memory to resize
const BOOK_PHOTO_MAX_DIMENSION = 1600;          // saved photos are shrunk to fit 1600 x 1600
const BOOK_ADMINS_SEPARATOR    = ',';           // CB_Book_Admins: "a@example.com,b@example.com"
const BOOK_ADMINS_MAX_COUNT    = 20;            // additional admins entered when creating a book
const BOOK_ADMIN_EMAIL_MAX_LENGTH = 128;        // same as CB_Username

// Written into every book's base directory. Files uploaded to a book must never
// run as code, and folder contents shouldn't be listable.
const BOOK_DIR_HTACCESS = <<<'HTACCESS'
# Created by ContactBook (bookFunctions.php) - protects uploaded files.
Options -Indexes
<FilesMatch "\.(php\d?|phtml|phar|pl|py|cgi|sh|shtml|htaccess)$">
    Require all denied
</FilesMatch>
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>

HTACCESS;

/* *********************************************************** */
/* Function:    generate_uuid_v4                               */
/* Description: Creates a random (version 4) UUID, e.g.        */
/*              3f2504e0-4f89-41d3-9a0c-0305e82c3301.          */
/* Returns:     The 36-character UUID string.                  */
/* *********************************************************** */
function generate_uuid_v4(): string {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);  // version 4
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);  // RFC 4122 variant
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

/* *********************************************************** */
/* Function:    validate_book_name                             */
/* Description: Checks the name of the person a book is for.   */
/*              Allows letters in any language, digits,        */
/*              spaces and the punctuation names use           */
/*              (O'Brien, Mary-Jane, St. John, "Smith, Jr.").  */
/*              Anything else - HTML, quotes, slashes, control */
/*              characters - is rejected. The name is still    */
/*              saved with a prepared statement and escaped    */
/*              with htmlspecialchars() wherever it's shown.   */
/* Inputs:      $name - the trimmed name.                      */
/* Returns:     An error message, or '' if the name is OK.     */
/* *********************************************************** */
function validate_book_name(string $name): string {
    if ($name === '') {
        return 'Please enter the name of the person this book is for.';
    }
    if (mb_strlen($name, 'UTF-8') > BOOK_NAME_MAX_LENGTH) {
        return 'The name can be at most ' . BOOK_NAME_MAX_LENGTH . ' characters.';
    }
    if (!preg_match("/^[\p{L}\p{M}0-9 .,'’()&-]+$/u", $name)) {
        return 'The name can only contain letters, numbers, spaces and . , \' - ( ) &';
    }
    return '';
}

/* *********************************************************** */
/* Function:    clean_book_description                         */
/* Description: Normalizes line endings, removes control       */
/*              characters (keeping newlines and tabs) and     */
/*              trims. The description is free text - it's     */
/*              saved with a prepared statement and must be    */
/*              escaped with htmlspecialchars() when shown.    */
/* Inputs:      $description - the text entered.               */
/* Returns:     The cleaned description.                       */
/* *********************************************************** */
function clean_book_description(string $description): string {
    $description = str_replace(["\r\n", "\r"], "\n", $description);
    $description = preg_replace('/[^\P{Cc}\n\t]/u', '', $description) ?? '';
    return trim($description);
}

/* *********************************************************** */
/* Function:    check_book_photo_upload                        */
/* Description: Checks an optional photo upload from $_FILES.  */
/*              The file's contents decide its type - the      */
/*              browser-supplied name and type are ignored.    */
/* Inputs:      $file - the $_FILES entry, or null.            */
/* Returns:     ['error' => string, 'present' => bool,         */
/*               'tmp' => string, 'type' => IMAGETYPE_*]       */
/* *********************************************************** */
function check_book_photo_upload(?array $file): array {
    $none = ['error' => '', 'present' => false, 'tmp' => '', 'type' => 0];
    $max_mb = BOOK_PHOTO_MAX_BYTES / 1048576;

    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $none;  // photo is optional
    }
    if (is_array($file['error'])) {
        return ['error' => 'Please choose a single photo.'] + $none;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || $file['size'] > BOOK_PHOTO_MAX_BYTES) {
        return ['error' => "The photo is too large. Please choose one under {$max_mb} MB."] + $none;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        error_log('ContactBook: photo upload failed with error code ' . $file['error']);
        return ['error' => 'The photo could not be uploaded. Please try again.'] + $none;
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return ['error' => 'The photo must be a JPEG, PNG or WebP image.'] + $none;
    }
    if ($info[0] * $info[1] > BOOK_PHOTO_MAX_PIXELS) {
        return ['error' => 'The photo\'s dimensions are too large. Please choose a smaller image.'] + $none;
    }

    return ['error' => '', 'present' => true, 'tmp' => $file['tmp_name'], 'type' => $info[2]];
}

/* *********************************************************** */
/* Function:    save_book_photo                                */
/* Description: Re-encodes an uploaded photo into $dir as      */
/*              <$basename>.jpg (or .png for PNGs, to keep     */
/*              transparency). Re-encoding turns               */
/*              phone photos the right way up, shrinks them to */
/*              fit BOOK_PHOTO_MAX_DIMENSION, strips hidden    */
/*              data such as GPS location, and drops anything  */
/*              that isn't image data.                         */
/* Inputs:      $tmp - uploaded temp file. $type - IMAGETYPE_*. */
/*              $dir - the full directory path to save in.     */
/*              $basename - the filename without extension.    */
/* Returns:     The saved filename, or '' on failure.          */
/* *********************************************************** */
function save_book_photo(string $tmp, int $type, string $dir, string $basename = 'cover'): string {
    switch ($type) {
        case IMAGETYPE_JPEG: $image = @imagecreatefromjpeg($tmp); break;
        case IMAGETYPE_PNG:  $image = @imagecreatefrompng($tmp);  break;
        case IMAGETYPE_WEBP: $image = @imagecreatefromwebp($tmp); break;
        default:             $image = false;
    }
    if ($image === false) {
        return '';
    }

    // Phones save photos sideways and record the rotation in EXIF - apply it.
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $orientation = (@exif_read_data($tmp))['Orientation'] ?? 1;
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }
        $angle = [3 => 180, 4 => 180, 5 => 270, 6 => 270, 7 => 90, 8 => 90][$orientation] ?? 0;
        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            imagedestroy($image);
            $image = $rotated;
        }
    }

    $width  = imagesx($image);
    $height = imagesy($image);
    $scale  = min(1, BOOK_PHOTO_MAX_DIMENSION / max($width, $height));
    if ($scale < 1) {
        $new_width  = max(1, (int) round($width * $scale));
        $new_height = max(1, (int) round($height * $scale));
        $resized = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }

    if ($type === IMAGETYPE_PNG) {
        $filename = $basename . '.png';
        imagesavealpha($image, true);
        $saved = imagepng($image, $dir . '/' . $filename, 6);
    } else {
        $filename = $basename . '.jpg';
        $saved = imagejpeg($image, $dir . '/' . $filename, 85);
    }
    imagedestroy($image);

    return $saved ? $filename : '';
}

/* *********************************************************** */
/* Function:    remove_book_directory                          */
/* Description: Removes a book directory create_book() made,   */
/*              along with the files it put there.             */
/* Inputs:      $dir - the book's full directory path.         */
/* Returns:     Nothing                                        */
/* *********************************************************** */
function remove_book_directory(string $dir): void {
    foreach (['.htaccess', 'cover.jpg', 'cover.png'] as $file) {
        if (is_file($dir . '/' . $file)) {
            unlink($dir . '/' . $file);
        }
    }
    if (is_dir($dir . '/' . BOOK_PICTURES_DIR_NAME)) {
        rmdir($dir . '/' . BOOK_PICTURES_DIR_NAME);  // still empty - the book was never saved
    }
    rmdir($dir);
}

/* *********************************************************** */
/* Function:    book_directory_path                            */
/* Description: The full path of a book's base directory.      */
/* Inputs:      $uuid - the book's CB_Book_UUID.               */
/* Returns:     e.g. ~/public_html/contactbook/books/<uuid>    */
/* *********************************************************** */
function book_directory_path(string $uuid): string {
    return __DIR__ . '/' . BOOKS_DIR_NAME . '/' . $uuid;
}

/* *********************************************************** */
/* Function:    create_book                                    */
/* Description: Validates the name, description and optional  */
/*              photo, creates a UUID and the book's base      */
/*              directory, saves the photo there, then saves   */
/*              the row. If anything fails, the directory is   */
/*              removed again so nothing is left half-made.    */
/* Inputs:      $name - name of the person the book is for.    */
/*              $description - optional description.           */
/*              $photo - optional $_FILES entry for the photo. */
/*              $creator_email - username of the user creating */
/*              the book; saved as its first book admin.       */
/*              $other_admins - optional emails of more book   */
/*              admins, separated by commas, semicolons,       */
/*              spaces or new lines.                           */
/* Returns:     ['success' => bool, 'error' => string,         */
/*               'uuid' => string, 'name' => string,           */
/*               'description' => string, 'admins' => string]  */
/* *********************************************************** */
function create_book(string $name, string $description = '', ?array $photo = null, string $creator_email = '',
                     string $other_admins = ''): array {
    // Collapse runs of whitespace (including newlines/tabs) to single spaces.
    $name        = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    $description = clean_book_description($description);
    $fail = function (string $error) use (&$name, &$description, &$other_admins): array {
        return ['success' => false, 'error' => $error, 'uuid' => '', 'name' => $name, 'description' => $description,
                'admins' => $other_admins];
    };

    $error = validate_book_name($name);
    if ($error !== '') {
        return $fail($error);
    }
    if (mb_strlen($description, 'UTF-8') > BOOK_DESCRIPTION_MAX_LENGTH) {
        return $fail('The description can be at most ' . BOOK_DESCRIPTION_MAX_LENGTH . ' characters.');
    }
    $parsed = parse_book_admin_emails($other_admins);
    if ($parsed['error'] !== '') {
        return $fail($parsed['error']);
    }
    $upload = check_book_photo_upload($photo);
    if ($upload['error'] !== '') {
        return $fail($upload['error']);
    }

    $uuid           = generate_uuid_v4();
    $books_path     = __DIR__ . '/' . BOOKS_DIR_NAME;                          // ~/public_html/contactbook/books
    $full_path      = book_directory_path($uuid);                              // ~/public_html/contactbook/books/<uuid>
    $base_directory = basename(__DIR__) . '/' . BOOKS_DIR_NAME . '/' . $uuid;  // contactbook/books/<uuid>, relative to public_html

    if (!is_dir($books_path) && !mkdir($books_path, 0755)) {
        error_log('ContactBook: could not create the books directory ' . $books_path);
        return $fail('The folder for this book could not be created. Please try again or contact the site administrator.');
    }
    if (!mkdir($full_path, 0755)) {
        error_log('ContactBook: could not create book directory ' . $full_path);
        return $fail('The folder for this book could not be created. Please try again or contact the site administrator.');
    }
    // The .htaccess also covers pictures/ and everything below it.
    if (file_put_contents($full_path . '/.htaccess', BOOK_DIR_HTACCESS) === false
        || !mkdir($full_path . '/' . BOOK_PICTURES_DIR_NAME, 0755)) {
        error_log('ContactBook: could not set up book directory ' . $full_path);
        remove_book_directory($full_path);
        return $fail('The folder for this book could not be created. Please try again or contact the site administrator.');
    }

    $photo_filename = null;
    if ($upload['present']) {
        $photo_filename = save_book_photo($upload['tmp'], $upload['type'], $full_path);
        if ($photo_filename === '') {
            error_log('ContactBook: could not save photo for book ' . $uuid);
            remove_book_directory($full_path);
            return $fail('The photo could not be processed. Please try a different image.');
        }
    }

    try {
        $connection = Create_SQL_PHP_DataObject();
        $stmt = $connection->prepare(
            'INSERT INTO CB_Created_Books
                (CB_Book_UUID, CB_Book_Name_About, CB_Base_Directory, CB_Book_Description, CB_Book_Cover_Picture, CB_Book_Admins)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $admins = join_book_admins(array_merge([$creator_email], $parsed['emails']));
        $stmt->execute([$uuid, $name, $base_directory, ($description === '') ? null : $description, $photo_filename,
                        ($admins === '') ? null : $admins]);
    } catch (PDOException $e) {
        error_log('ContactBook: could not save book ' . $uuid . ': ' . $e->getMessage());
        remove_book_directory($full_path);
        return $fail('The book could not be saved. Please try again.');
    }

    return ['success' => true, 'error' => '', 'uuid' => $uuid, 'name' => $name, 'description' => $description,
            'admins' => $admins];
}

/* *********************************************************** */
/* Function:    list_books                                     */
/* Description: All books, in order of the name of the person  */
/*              each one is for (for the landing page).        */
/* Returns:     Rows with CB_Book_UUID, CB_Book_Name_About and */
/*              CB_Book_Description.                           */
/* *********************************************************** */
function list_books(): array {
    $connection = Create_SQL_PHP_DataObject();
    $stmt = $connection->query(
        'SELECT CB_Book_UUID, CB_Book_Name_About, CB_Book_Description
         FROM CB_Created_Books
         ORDER BY CB_Book_Name_About, CB_Book_ID'
    );
    $books = $stmt->fetchAll();
    $stmt = null;
    $connection = null;
    return $books;
}

/* *********************************************************** */
/* Function:    summarize_book_description                     */
/* Description: Shortens a description to fit on a book card:  */
/*              line breaks become spaces and anything past    */
/*              $max_length is cut at a word and given "…".    */
/* Returns:     The summary (not HTML-escaped).                */
/* *********************************************************** */
function summarize_book_description(?string $description, int $max_length = 150): string {
    $text = trim(preg_replace('/\s+/u', ' ', (string) $description));
    if (mb_strlen($text, 'UTF-8') <= $max_length) {
        return $text;
    }
    $text = mb_substr($text, 0, $max_length, 'UTF-8');
    $last_space = mb_strrpos($text, ' ', 0, 'UTF-8');
    if ($last_space !== false && $last_space > $max_length / 2) {
        $text = mb_substr($text, 0, $last_space, 'UTF-8');
    }
    return rtrim($text, " .,;:-") . '…';
}

/* *********************************************************** */
/* Function:    split_book_admins                              */
/* Description: Turns a CB_Book_Admins value into a list of    */
/*              emails - lowercased, trimmed, no blanks or     */
/*              duplicates.                                    */
/* Inputs:      $admins - the column value (may be NULL).      */
/* Returns:     The list of emails.                            */
/* *********************************************************** */
function split_book_admins(?string $admins): array {
    $emails = array_map(fn($email) => strtolower(trim($email)), explode(BOOK_ADMINS_SEPARATOR, (string) $admins));
    return array_values(array_unique(array_filter($emails, fn($email) => $email !== '')));
}

/* *********************************************************** */
/* Function:    join_book_admins                               */
/* Description: The opposite of split_book_admins.             */
/* Inputs:      $emails - the list of emails.                  */
/* Returns:     The CB_Book_Admins value ('' if no emails).    */
/* *********************************************************** */
function join_book_admins(array $emails): string {
    return implode(BOOK_ADMINS_SEPARATOR, split_book_admins(implode(BOOK_ADMINS_SEPARATOR, $emails)));
}

/* *********************************************************** */
/* Function:    parse_book_admin_emails                        */
/* Description: Checks the "additional admins" box on the      */
/*              Create a New Book form. Emails can be          */
/*              separated by commas, semicolons, spaces or new */
/*              lines. The box is optional.                    */
/* Inputs:      $text - what was typed in the box.             */
/* Returns:     ['emails' => lowercased, de-duplicated list,   */
/*               'error' => message, or '' if all are OK]      */
/* *********************************************************** */
function parse_book_admin_emails(string $text): array {
    $emails = array_values(array_unique(array_map('strtolower',
        preg_split('/[\s,;]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [])));
    if (count($emails) > BOOK_ADMINS_MAX_COUNT) {
        return ['emails' => [], 'error' => 'Please enter at most ' . BOOK_ADMINS_MAX_COUNT . ' additional admins.'];
    }
    foreach ($emails as $email) {
        if (strlen($email) > BOOK_ADMIN_EMAIL_MAX_LENGTH || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            // Shown escaped by the page; cut down in case it's very long.
            return ['emails' => [], 'error' => '"' . mb_strimwidth($email, 0, 60, '…', 'UTF-8') . '" is not a valid email address.'];
        }
    }
    return ['emails' => $emails, 'error' => ''];
}

/* *********************************************************** */
/* Function:    list_books_for_admin                           */
/* Description: The books a user can manage - those with their */
/*              email in CB_Book_Admins - in order of the name */
/*              of the person each one is for. Matching is     */
/*              done here rather than in SQL so that "a@x.com" */
/*              never matches "ba@x.com".                      */
/* Inputs:      $email - the user's username.                  */
/*              $all - true to return every book (overlords).  */
/* Returns:     Rows with CB_Book_UUID, CB_Book_Name_About,    */
/*              CB_Book_Description and CB_Book_Admins.        */
/* *********************************************************** */
function list_books_for_admin(string $email, bool $all = false): array {
    $email = strtolower(trim($email));
    $connection = Create_SQL_PHP_DataObject();
    $stmt = $connection->query(
        'SELECT CB_Book_UUID, CB_Book_Name_About, CB_Book_Description, CB_Book_Admins
         FROM CB_Created_Books
         ORDER BY CB_Book_Name_About, CB_Book_ID'
    );
    $books = $stmt->fetchAll();
    if (!$all) {
        $books = array_values(array_filter($books,
            fn($book) => in_array($email, split_book_admins($book['CB_Book_Admins']), true)));
    }
    $stmt = null;
    $connection = null;
    return $books;
}
