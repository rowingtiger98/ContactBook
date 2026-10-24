<?php
include('login.php'); // Handles the ADMIN login panel (and starts the session)
require_once 'entryFunctions.php';
$admin_link = empty($_SESSION['login_user']) ? '#loginPanel' : 'admin.php';

$book_uuid = is_string($_GET['book'] ?? null) ? $_GET['book'] : '';
$book      = find_book_by_uuid($book_uuid);

$form_error = '';
$entry      = ['name' => '', 'street' => '', 'city' => '', 'state' => '', 'zip' => '', 'phone' => '', 'emails' => [], 'notes' => ''];

// Set just before the redirect below, so the thank-you shows once (Post/Redirect/Get).
$entry_added = ($book !== null && ($_SESSION['entry_added'] ?? '') === $book['CB_Book_UUID']);
unset($_SESSION['entry_added']);

if ($book === null) {
    http_response_code(404);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    // Bigger than the server's post_max_size - PHP throws the whole form away.
    $form_error = 'Your photos are too large all together. Please choose fewer or smaller photos.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entry'])) {
    if (!app_csrf_valid($_POST['csrf_token'] ?? null)) {
        $form_error = 'This page was open too long. Please press Save again.';
        $entry      = clean_entry_form($_POST);  // nothing saved - just keeps what they typed
    } elseif (($_POST['entry_website'] ?? '') !== '') {
        // Hidden "website" box - only spam bots fill it in. Pretend it worked.
        $_SESSION['entry_added'] = $book['CB_Book_UUID'];
        header('Location: add_entry.php?book=' . urlencode($book['CB_Book_UUID']));
        exit();
    } else {
        $result = create_entry($book['CB_Book_UUID'], $_POST, $_FILES['entry_photos'] ?? null);
        if ($result['success']) {
            $_SESSION['entry_added'] = $book['CB_Book_UUID'];
            header('Location: add_entry.php?book=' . urlencode($book['CB_Book_UUID']));
            exit();
        }
        $form_error = $result['error'];
        $entry      = $result['entry'];
    }
}

$emails = $entry['emails'] ?: [''];  // always at least one email box
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Contact Book - Add Your Information</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<link rel="stylesheet" href="assets/css/contactbook.css" />
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

		<!-- Add Your Information -->
			<section id="main" class="wrapper">
				<div class="inner form_panel">
				<?php if ($book === null) { ?>
					<span class="bold_heading">Book Not Found</span>
					<p>That contact book could not be found. It may have been removed, or the link may be incomplete.</p>
					<a href="index.php" class="button special">Back to the Contact Books</a>

				<?php } elseif ($entry_added) { ?>
					<span class="bold_heading">Thank You!</span>
					<p>Your information has been added to the book for <?php echo htmlspecialchars($book['CB_Book_Name_About']); ?>.</p>
					<a href="index.php" class="button special">Back to the Contact Books</a>

				<?php } else { ?>
					<span class="bold_heading">Add Your Information</span>
					<p>For the book for <strong><?php echo htmlspecialchars($book['CB_Book_Name_About']); ?></strong>.
					Your name and email address are required.</p>

					<form action="add_entry.php?book=<?php echo urlencode($book['CB_Book_UUID']); ?>" method="post" enctype="multipart/form-data" id="entry_form">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(app_csrf_token()); ?>" />
						<!-- Spam trap: hidden from people, but bots fill it in. Styled inline too, so it
						     stays hidden even if contactbook.css is missing or an old copy is cached. -->
						<div class="entry_website" aria-hidden="true" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;">
							<label for="entry_website">Website (leave blank)</label>
							<input type="text" name="entry_website" id="entry_website" value="" tabindex="-1" autocomplete="off" />
						</div>
						<div class="row uniform">
							<div class="12u$">
								<label for="entry_name">Name</label>
								<input type="text" name="entry_name" id="entry_name" value="<?php echo htmlspecialchars($entry['name']); ?>"
									maxlength="<?php echo ENTRY_NAME_MAX_LENGTH; ?>" autocomplete="name" required autofocus />
							</div>
							<div class="12u$">
								<label for="entry_street">Street Address</label>
								<input type="text" name="entry_street" id="entry_street" value="<?php echo htmlspecialchars($entry['street']); ?>"
									maxlength="<?php echo ENTRY_STREET_MAX_LENGTH; ?>" autocomplete="street-address" />
							</div>
							<div class="6u 12u$(small)">
								<label for="entry_city">City</label>
								<input type="text" name="entry_city" id="entry_city" value="<?php echo htmlspecialchars($entry['city']); ?>"
									maxlength="<?php echo ENTRY_CITY_MAX_LENGTH; ?>" autocomplete="address-level2" />
							</div>
							<div class="3u 6u(small)">
								<label for="entry_state">State</label>
								<input type="text" name="entry_state" id="entry_state" value="<?php echo htmlspecialchars($entry['state']); ?>"
									maxlength="<?php echo ENTRY_STATE_MAX_LENGTH; ?>" autocomplete="address-level1" />
							</div>
							<div class="3u$ 6u$(small)">
								<label for="entry_zip">Zip</label>
								<input type="text" name="entry_zip" id="entry_zip" value="<?php echo htmlspecialchars($entry['zip']); ?>"
									maxlength="<?php echo ENTRY_ZIP_MAX_LENGTH; ?>" autocomplete="postal-code" />
							</div>
							<div class="12u$">
								<label for="entry_phone">Phone</label>
								<input type="tel" name="entry_phone" id="entry_phone" value="<?php echo htmlspecialchars($entry['phone']); ?>"
									maxlength="<?php echo ENTRY_PHONE_MAX_LENGTH; ?>" placeholder="(555) 123-4567" autocomplete="tel" />
							</div>
							<div class="12u$">
								<label for="entry_email_1">Email</label>
								<div id="entry_email_list" data-max="<?php echo ENTRY_EMAILS_MAX_COUNT; ?>">
								<?php foreach ($emails as $i => $email) { ?>
									<div class="entry_email_row">
										<input type="email" name="entry_emails[]" id="entry_email_<?php echo $i + 1; ?>"
											value="<?php echo htmlspecialchars($email); ?>" autocomplete="<?php echo $i === 0 ? 'email' : 'off'; ?>"
											<?php if ($i === 0) { ?>required<?php } else { ?>aria-label="Email <?php echo $i + 1; ?>"<?php } ?> />
										<button type="button" class="button small entry_email_remove" title="Remove this email"
											aria-label="Remove this email"<?php if ($i === 0) { ?> hidden<?php } ?>><i class="fa fa-minus" aria-hidden="true"></i></button>
									</div>
								<?php } ?>
								</div>
								<button type="button" id="entry_email_add" class="button small" title="Add another email address" hidden>
									<i class="fa fa-plus" aria-hidden="true"></i> Add another email
								</button>
							</div>
							<div class="12u$">
								<label for="entry_photos">Photos</label>
								<input type="file" name="entry_photos[]" id="entry_photos" accept="image/jpeg,image/png,image/webp" multiple
									data-max-count="<?php echo ENTRY_PHOTOS_MAX_COUNT; ?>" data-max-bytes="<?php echo BOOK_PHOTO_MAX_BYTES; ?>" />
								<p class="field_note">Optional. Up to <?php echo ENTRY_PHOTOS_MAX_COUNT; ?> photos (JPEG, PNG or WebP), each up to <?php echo BOOK_PHOTO_MAX_BYTES / 1048576; ?> MB.
								<?php if ($form_error !== '') { ?>If you chose photos, please choose them again.<?php } ?></p>
								<div id="entry_photo_previews" class="entry_photo_previews"></div>
							</div>
							<div class="12u$">
								<label for="entry_notes">Notes</label>
								<textarea name="entry_notes" id="entry_notes" rows="5" maxlength="<?php echo ENTRY_NOTES_MAX_LENGTH; ?>"
									placeholder="Anything you'd like to leave in the book"><?php echo htmlspecialchars($entry['notes']); ?></textarea>
							</div>
							<?php if ($form_error !== '') { ?>
							<div class="12u$">
								<span class="login_error_message"><?php echo htmlspecialchars($form_error); ?></span>
							</div>
							<?php } ?>
							<div class="6u 12u$(small)">
								<input type="submit" name="save_entry" class="button special fit" value="Save" />
							</div>
							<div class="6u$ 12u$(small)">
								<a href="index.php" class="button fit">Cancel</a>
							</div>
						</div>
					</form>
				<?php } ?>
				</div>
			</section>

		<!-- Shown by add_entry.js while the entry (and any photos) uploads -->
			<div id="entry_saving" class="entry_saving" role="status" aria-live="polite" hidden>
				<div>
					<i class="fa fa-spinner fa-pulse fa-3x" aria-hidden="true"></i>
					<p>Saving your information&hellip;</p>
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
			<script src="assets/js/add_entry.js"></script>

	</body>
</html>
