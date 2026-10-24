<?php
include('session.php');
require_once 'entryFunctions.php';
require_once 'userFunctions.php';

// Admin-level: the Mark Entered button in the entries table on Manage a Book posts
// here (book, entry, csrf_token). Only for admins of the entry's book, and overlords.
// manage_book.js sends it in the background and asks for JSON - {ok, status_html,
// message} - to refresh just that row; without JavaScript the form posts normally
// and this goes back to the book's page with a message.
if (!user_is_admin($login_user_role)) {
    header('Location: admin.php');
    exit();
}

$wants_json = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
$book_uuid  = is_string($_POST['book'] ?? null) ? $_POST['book'] : '';
$entry_uuid = is_string($_POST['entry'] ?? null) ? $_POST['entry'] : '';

$book = null;
foreach (list_books_for_admin($login_username, user_is_overlord($login_user_role)) as $row) {
    if ($row['CB_Book_UUID'] === $book_uuid) {
        $book = $row;
    }
}
$entry = ($book !== null) ? find_entry($book_uuid, $entry_uuid) : null;

$ok = false;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !app_csrf_valid($_POST['csrf_token'] ?? null)) {
    $message = 'Your session has changed since this page was opened. Please reload the page and try again.';
} elseif ($entry === null) {
    $message = 'That entry could not be found. It may have been deleted.';
} elseif (!mark_entry_entered($book_uuid, $entry_uuid)) {
    $message = 'The entry could not be marked as entered. Please try again.';
} else {
    $ok = true;
    $message = 'Marked ' . $entry['name'] . '\'s entry as entered.';
}

if ($wants_json) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    if (!$ok) {
        http_response_code(400);
    }
    echo json_encode([
        'ok'          => $ok,
        'status_html' => $ok ? entry_status_icon(ENTRY_STATUS_ENTERED) : '',
        'message'     => $message,
    ]);
    exit();
}

$_SESSION['flash_message'] = $message;
header('Location: ' . (($book !== null) ? 'manage_book.php?book=' . rawurlencode($book_uuid) : 'manage_book.php'));
exit();
