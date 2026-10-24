<?php
include('login.php'); // Handles the ADMIN login panel (and starts the session)
require_once 'entryFunctions.php';
$admin_link = empty($_SESSION['login_user']) ? '#loginPanel' : 'admin.php';

// Public page: the person who made an entry edits it from the link in their
// thank-you email (edit_my_entry.php?entry=<uuid>&key=<edit code>). The code
// only opens that one entry. No delete here - only admins can delete entries.

// The address holds the secret edit code: don't send it to other sites, and
// don't let browsers, proxies or search engines keep it.
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$entry_uuid = is_string($_GET['entry'] ?? null) ? $_GET['entry'] : '';
$edit_key   = is_string($_GET['key'] ?? null) ? $_GET['key'] : '';
$found      = find_entry_by_edit_token($entry_uuid, $edit_key);

$form_error = '';
$saved      = false;

if ($found === null) {
    http_response_code(404);
} else {
    $book      = $found['book'];
    $book_uuid = $book['CB_Book_UUID'];
    $entry     = $found['entry'];
    $page_url  = 'edit_my_entry.php?entry=' . rawurlencode($entry_uuid) . '&key=' . rawurlencode($edit_key);

    // Set just before the redirect below, so "saved" shows once (Post/Redirect/Get).
    $saved = (($_SESSION['my_entry_saved'] ?? '') === $entry_uuid);
    unset($_SESSION['my_entry_saved']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            // Bigger than the server's post_max_size - PHP throws the whole form away.
            $form_error = 'Your photos are too large all together. Please choose fewer or smaller photos.';
        } elseif (!app_csrf_valid($_POST['csrf_token'] ?? null)) {
            $form_error = 'This page was open too long. Please press Save Changes again.';
            $entry      = clean_entry_form($_POST);  // nothing saved - just keeps what they typed
        } elseif (isset($_POST['save_entry'])) {
            $remove = is_array($_POST['remove_photos'] ?? null) ? array_filter($_POST['remove_photos'], 'is_string') : [];
            // Flags the entry for the book's manager, who may need to copy the changes.
            $result = update_entry($book_uuid, $entry_uuid, $_POST, $_FILES['entry_photos'] ?? null, $remove, ENTRY_STATUS_EDITED);
            if ($result['success']) {
                $_SESSION['my_entry_saved'] = $entry_uuid;
                header('Location: ' . $page_url);
                exit();
            }
            $form_error = $result['error'];
            $entry      = $result['entry'];
        }
    }

    $photos = list_entry_photos($book_uuid, $entry_uuid);
    $emails = $entry['emails'] ?: [''];  // always at least one email box
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Contact Book - Edit Your Entry</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="referrer" content="no-referrer" />
		<meta name="robots" content="noindex, nofollow" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<link rel="stylesheet" href="<?php echo asset_url('assets/css/contactbook.css'); ?>" />
	</head>
	<body>

		<!-- Header -->
			<header id="header">
				<h1><a href="index.php">Contact Book</a></h1>
				<nav id="nav">
					<ul>
						<li><a href="index.php">Home</a></li>
						<li><a href="<?php echo $admin_link; ?>">Admin</a></li>
					</ul>
				</nav>
			</header>

		<!-- Edit Your Entry -->
			<section id="main" class="wrapper">
				<div class="inner form_panel">
				<?php if ($found === null) { ?>
					<span class="bold_heading">Link Not Found</span>
					<p>This edit link isn't valid. It may be incomplete - some email programs split long links over two lines, so try
					copying the whole link from your email into your browser. If the entry was removed, the link no longer works.</p>
					<a href="index.php" class="button special">Back to the Contact Books</a>

				<?php } else { ?>
					<span class="bold_heading">Edit Your Entry</span>
					<?php if ($saved) { ?>
						<p class="flash_message">Your changes have been saved.</p>
					<?php } ?>
					<p>Update your information in the book for <strong><?php echo htmlspecialchars($book['CB_Book_Name_About']); ?></strong>,
					then press <strong>Save Changes</strong>. Your name and email address are required.</p>

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
								<a href="index.php" class="button fit">Back to the Contact Books</a>
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
					<p>Saving your changes&hellip;</p>
					<p class="field_note">Photos can take a minute to upload. Please keep this page open.</p>
				</div>
			</div>

		<!-- Admin Login Panel -->
			<?php include 'login_modal.php'; ?>

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
			<script src="<?php echo asset_url('assets/js/photo_resize.js'); ?>"></script>
			<script src="<?php echo asset_url('assets/js/add_entry.js'); ?>"></script>

	</body>
</html>
