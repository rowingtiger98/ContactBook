<?php
include('session.php');
require_once 'entryFunctions.php';
require_once 'userFunctions.php';

// Admin-level page: edit or delete one entry (edit_entry.php?book=<uuid>&entry=<uuid>).
// Only for admins of the entry's book, and overlords. Add &delete=1 to ask before deleting.
if (!user_is_admin($login_user_role)) {
    header('Location: admin.php');
    exit();
}

$book_uuid  = is_string($_GET['book'] ?? null) ? $_GET['book'] : '';
$entry_uuid = is_string($_GET['entry'] ?? null) ? $_GET['entry'] : '';

$book = null;
foreach (list_books_for_admin($login_username, user_is_overlord($login_user_role)) as $row) {
    if ($row['CB_Book_UUID'] === $book_uuid) {
        $book = $row;
    }
}
$stored = ($book !== null) ? find_entry($book_uuid, $entry_uuid) : null;
if ($stored === null) {
    $_SESSION['flash_message'] = 'That entry could not be found. It may have already been deleted.';
    header('Location: ' . (($book !== null) ? 'manage_book.php?book=' . rawurlencode($book_uuid) : 'manage_book.php'));
    exit();
}

$manage_url = 'manage_book.php?book=' . rawurlencode($book_uuid);
$page_url   = 'edit_entry.php?book=' . rawurlencode($book_uuid) . '&entry=' . rawurlencode($entry_uuid);
$confirm_delete = isset($_GET['delete']);

$entry      = $stored;
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        // Bigger than the server's post_max_size - PHP throws the whole form away.
        $form_error = 'The photos are too large all together. Please choose fewer or smaller photos.';
    } elseif (!app_csrf_valid($_POST['csrf_token'] ?? null)) {
        $form_error = 'Your session has changed since this page was opened. Please try again.';
        if (isset($_POST['save_entry'])) {
            $entry = clean_entry_form($_POST);  // nothing saved - just keeps what was typed
        }
    } elseif (isset($_POST['delete_entry'])) {
        if (delete_entry($book_uuid, $entry_uuid)) {
            $_SESSION['flash_message'] = 'Deleted ' . $stored['name'] . '\'s entry.';
        } else {
            $_SESSION['flash_message'] = 'The entry could not be deleted. Please try again.';
        }
        header('Location: ' . $manage_url);
        exit();
    } elseif (isset($_POST['save_entry'])) {
        $remove = is_array($_POST['remove_photos'] ?? null) ? array_filter($_POST['remove_photos'], 'is_string') : [];
        $result = update_entry($book_uuid, $entry_uuid, $_POST, $_FILES['entry_photos'] ?? null, $remove);
        if ($result['success']) {
            $_SESSION['flash_message'] = 'Saved the changes to ' . $result['entry']['name'] . '\'s entry.';
            header('Location: ' . $manage_url);
            exit();
        }
        $form_error = $result['error'];
        $entry      = $result['entry'];
    }
}

$photos = list_entry_photos($book_uuid, $entry_uuid);
$emails = $entry['emails'] ?: [''];  // always at least one email box
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Contact Book - <?php echo $confirm_delete ? 'Delete Entry' : 'Edit Entry'; ?></title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<link rel="stylesheet" href="assets/css/contactbook.css" />
	</head>
	<body>

		<!-- Header -->
			<header id="header">
				<?php include 'admin_header.php'; ?>
			</header>

			<section id="main" class="wrapper">
				<div class="inner form_panel">
				<?php if ($confirm_delete) { ?>
				<!-- Delete Entry - confirmation -->
					<span class="bold_heading">Delete Entry</span>
					<p>Delete <strong><?php echo htmlspecialchars($stored['name']); ?></strong>'s entry from the book for
					<?php echo htmlspecialchars($book['CB_Book_Name_About']); ?>?
					<?php if ($photos) { ?>Its <?php echo count($photos); ?> photo<?php echo count($photos) === 1 ? '' : 's'; ?> will be deleted too.<?php } ?>
					This can't be undone.</p>
					<?php if ($form_error !== '') { ?>
					<p><span class="login_error_message"><?php echo htmlspecialchars($form_error); ?></span></p>
					<?php } ?>
					<form action="<?php echo htmlspecialchars($page_url . '&delete=1'); ?>" method="post">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(app_csrf_token()); ?>" />
						<div class="row uniform">
							<div class="6u 12u$(small)">
								<input type="submit" name="delete_entry" class="button special fit" value="Delete Entry" />
							</div>
							<div class="6u$ 12u$(small)">
								<a href="<?php echo htmlspecialchars($manage_url); ?>" class="button fit">Cancel</a>
							</div>
						</div>
					</form>

				<?php } else { ?>
				<!-- Edit Entry -->
					<span class="bold_heading">Edit Entry</span>
					<p>In the book for <strong><?php echo htmlspecialchars($book['CB_Book_Name_About']); ?></strong>. The name and an email address are required.</p>

					<form action="<?php echo htmlspecialchars($page_url); ?>" method="post" enctype="multipart/form-data" id="entry_form">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(app_csrf_token()); ?>" />
						<div class="row uniform">
							<?php include 'entry_form_fields.php'; ?>
							<?php if ($form_error !== '') { ?>
							<div class="12u$">
								<span class="login_error_message"><?php echo htmlspecialchars($form_error); ?></span>
							</div>
							<?php } ?>
							<div class="6u 12u$(small)">
								<input type="submit" name="save_entry" class="button special fit" value="Save Changes" />
							</div>
							<div class="6u$ 12u$(small)">
								<a href="<?php echo htmlspecialchars($manage_url); ?>" class="button fit">Cancel</a>
							</div>
							<div class="12u$">
								<a href="<?php echo htmlspecialchars($page_url . '&delete=1'); ?>" class="button small entry_delete_link">
									<i class="fa fa-trash" aria-hidden="true"></i> Delete this entry
								</a>
							</div>
						</div>
					</form>
				<?php } ?>
				</div>
			</section>

		<!-- Shown by add_entry.js while the changes (and any photos) upload -->
			<div id="entry_saving" class="entry_saving" role="status" aria-live="polite" hidden>
				<div>
					<i class="fa fa-spinner fa-pulse fa-3x" aria-hidden="true"></i>
					<p>Saving the changes&hellip;</p>
					<p class="field_note">Photos can take a minute to upload. Please keep this page open.</p>
				</div>
			</div>

		<!-- Footer -->
			<footer id="footer">
				<div class="inner">
					<p class="copyright">&copy; <?php echo date('Y'); ?> Contact Book. All rights reserved.</p>
				</div>
			</footer>

		<!-- Scripts -->
			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/jquery.dropotron.min.js"></script>
			<script src="assets/js/jquery.scrollex.min.js"></script>
			<script src="assets/js/skel.min.js"></script>
			<script src="assets/js/util.js"></script>
			<script src="assets/js/main.js"></script>
			<script src="assets/js/add_entry.js"></script>

	</body>
</html>
