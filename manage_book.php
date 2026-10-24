<?php
include('session.php');
require_once 'bookFunctions.php';
require_once 'entryFunctions.php';
require_once 'userFunctions.php';

// Admin-level page (admins and overlords). Admins only see the books with
// their email in CB_Book_Admins; overlords see every book.
if (!user_is_admin($login_user_role)) {
    header('Location: admin.php');
    exit();
}
$is_overlord = user_is_overlord($login_user_role);

$books = list_books_for_admin($login_username, $is_overlord);

// The chosen book (manage_book.php?book=<uuid>) - only if it's one of this user's books.
// The edit form posts back to the same address, so the book always comes from the URL.
$selected_uuid = is_string($_GET['book'] ?? null) ? $_GET['book'] : '';
$selected_book = null;
foreach ($books as $book) {
    if ($book['CB_Book_UUID'] === $selected_uuid) {
        $selected_book = $book;
    }
}
$choose_error = ($selected_uuid !== '' && $selected_book === null)
    ? 'That book could not be found, or you are not one of its admins.'
    : '';

// One-time message after saving (Post/Redirect/Get).
$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

$save_error = '';
if ($selected_book !== null) {
    $name        = $selected_book['CB_Book_Name_About'];
    $description = (string) $selected_book['CB_Book_Description'];
    $admins      = implode("\n", split_book_admins($selected_book['CB_Book_Admins']));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            // Upload bigger than the server's post_max_size - PHP discards the whole form.
            $save_error = 'The photo is too large. Please choose one under ' . (BOOK_PHOTO_MAX_BYTES / 1048576) . ' MB.';
        } elseif (!app_csrf_valid($_POST['csrf_token'] ?? null)) {
            $save_error = 'Your session has changed since this page was opened. Please try again.';
        } else {
            $result = update_book(
                $selected_uuid,
                is_string($_POST['book_name'] ?? null) ? $_POST['book_name'] : '',
                is_string($_POST['book_description'] ?? null) ? $_POST['book_description'] : '',
                is_string($_POST['book_admins'] ?? null) ? $_POST['book_admins'] : '',
                $_FILES['book_photo'] ?? null,
                isset($_POST['remove_photo']),
                $login_username,
                $is_overlord
            );
            if ($result['success']) {
                $_SESSION['flash_message'] = 'Saved the changes to ' . $result['name'] . '\'s book.';
                header('Location: manage_book.php?book=' . rawurlencode($selected_uuid));
                exit();
            }
            $save_error  = $result['error'];
            $name        = $result['name'];
            $description = $result['description'];
            $admins      = $result['admins'];
        }
    }
    $cover_url = book_cover_url($selected_uuid, $selected_book['CB_Book_Cover_Picture']);
    $entries   = list_book_entries($selected_uuid);
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Contact Book - Manage a Book</title>
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

		<!-- Manage a Book -->
			<section id="main" class="wrapper">
				<div class="inner form_panel">
					<span class="bold_heading">Manage a Book</span>
					<?php if ($flash_message !== '') { ?>
						<p class="flash_message"><?php echo htmlspecialchars($flash_message); ?></p>
					<?php } ?>
					<?php if (empty($books)) { ?>
					<p>You are not an admin of any books yet. Books you create are added here automatically;
					for other books, ask one of their admins to add you.</p>
					<div class="row uniform">
						<div class="6u 12u$(small)">
							<a href="admin.php" class="button fit">Back to Dashboard</a>
						</div>
					</div>
					<?php } else { ?>
					<p>Choose the book you want to work on.</p>

					<form action="manage_book.php" method="get">
						<div class="row uniform">
							<div class="12u$">
								<label for="book">Book</label>
								<div class="select-wrapper">
									<select name="book" id="book" required>
										<option value=""<?php if ($selected_book === null) { ?> selected<?php } ?> disabled>Choose a book…</option>
										<?php foreach ($books as $book) { ?>
										<option value="<?php echo htmlspecialchars($book['CB_Book_UUID']); ?>"<?php if ($book === $selected_book) { ?> selected<?php } ?>><?php echo htmlspecialchars($book['CB_Book_Name_About']); ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<?php if ($choose_error !== '') { ?>
							<div class="12u$">
								<span class="login_error_message"><?php echo htmlspecialchars($choose_error); ?></span>
							</div>
							<?php } ?>
							<div class="6u 12u$(small)">
								<input type="submit" class="button special fit" value="Manage" />
							</div>
							<div class="6u$ 12u$(small)">
								<a href="admin.php" class="button fit">Back to Dashboard</a>
							</div>
						</div>
					</form>

					<?php if ($selected_book !== null) { ?>
					<hr />
					<!-- Collapsed to start; opened again if saving failed, so the error shows. -->
					<details class="collapsible_section"<?php if ($save_error !== '') { ?> open<?php } ?>>
						<summary><h3>Book Details</h3><i class="fa fa-chevron-down" aria-hidden="true"></i></summary>
						<form action="manage_book.php?book=<?php echo rawurlencode($selected_uuid); ?>" method="post" enctype="multipart/form-data">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(app_csrf_token()); ?>" />
							<div class="row uniform">
								<div class="12u$">
									<label for="book_name">Book is for</label>
									<input type="text" name="book_name" id="book_name" value="<?php echo htmlspecialchars($name); ?>"
										maxlength="<?php echo BOOK_NAME_MAX_LENGTH; ?>" autocomplete="off" required />
								</div>
								<div class="12u$">
									<label for="book_description">Description</label>
									<textarea name="book_description" id="book_description" rows="5"
										maxlength="<?php echo BOOK_DESCRIPTION_MAX_LENGTH; ?>" placeholder="What is this book about?"><?php echo htmlspecialchars($description); ?></textarea>
								</div>
								<div class="12u$">
									<label for="book_admins">Admins</label>
									<textarea name="book_admins" id="book_admins" rows="4" autocomplete="off" required><?php echo htmlspecialchars($admins); ?></textarea>
									<p class="field_note">Email addresses of the users who can manage this book, separated by commas or
									on separate lines.<?php if (!$is_overlord) { ?> You can't remove yourself.<?php } ?></p>
								</div>
								<div class="12u$">
									<label for="book_photo">Photo</label>
									<input type="file" name="book_photo" id="book_photo" accept="image/jpeg,image/png,image/webp" />
									<p class="field_note">JPEG, PNG or WebP, up to <?php echo BOOK_PHOTO_MAX_BYTES / 1048576; ?> MB.
									<?php if ($cover_url !== '') { ?>Choosing a photo replaces the current one.<?php } ?>
									<?php if ($save_error !== '') { ?>If you chose a photo, please choose it again.<?php } ?></p>
									<label for="book_photo" id="book_photo_placeholder" class="book_photo_placeholder" title="Choose a photo"<?php if ($cover_url !== '') { ?> hidden<?php } ?>>
										<i class="fa fa-camera" aria-hidden="true"></i>
										<span>No photo chosen</span>
									</label>
									<img id="book_photo_preview" class="book_photo_preview" alt="Book photo"
										<?php if ($cover_url !== '') { ?>src="<?php echo htmlspecialchars($cover_url); ?>" data-current-src="<?php echo htmlspecialchars($cover_url); ?>"<?php } else { ?>hidden<?php } ?> />
									<?php if ($cover_url !== '') { ?>
									<div class="book_photo_remove">
										<input type="checkbox" name="remove_photo" id="remove_photo" />
										<label for="remove_photo">Remove this photo</label>
									</div>
									<?php } ?>
								</div>
								<?php if ($save_error !== '') { ?>
								<div class="12u$">
									<span class="login_error_message"><?php echo htmlspecialchars($save_error); ?></span>
								</div>
								<?php } ?>
								<div class="6u 12u$(small)">
									<input type="submit" class="button special fit" value="Save Changes" />
								</div>
								<div class="6u$ 12u$(small)">
									<a href="manage_book.php?book=<?php echo rawurlencode($selected_uuid); ?>" class="button fit">Undo Changes</a>
								</div>
							</div>
						</form>
					</details>

					<details class="collapsible_section" open>
						<summary><h3>Entries (<?php echo count($entries); ?>)</h3><i class="fa fa-chevron-down" aria-hidden="true"></i></summary>
						<div class="collapsible_body">
							<?php if (empty($entries)) { ?>
							<p>No one has added their information to this book yet.</p>
							<?php } else { ?>
							<div class="table-wrapper">
								<table class="entries_table">
									<thead>
										<tr>
											<th>Name</th>
											<th>Contact</th>
											<th>Message</th>
											<th class="entries_photos">Photos</th>
											<th class="entries_actions"><span class="sr_only">Actions</span></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($entries as $entry) { ?>
										<tr>
											<td><?php echo htmlspecialchars($entry['ENTRY_Name']); ?></td>
											<td>
												<?php foreach (array_filter(array_map('trim', explode(',', (string) $entry['ENTRY_Emails']))) as $email) { ?>
												<?php echo htmlspecialchars($email); ?><br />
												<?php } ?>
												<?php if (($entry['ENTRY_Phone'] ?? '') !== '') { echo htmlspecialchars($entry['ENTRY_Phone']); } ?>
											</td>
											<td><?php echo htmlspecialchars(summarize_entry_notes($entry['ENTRY_Notes'])); ?></td>
											<td class="entries_photos"><?php echo $entry['photo_count']; ?></td>
											<?php $entry_url = 'edit_entry.php?book=' . rawurlencode($selected_uuid) . '&entry=' . rawurlencode($entry['ENTRY_UUID']); ?>
											<td class="entries_actions">
												<a href="<?php echo htmlspecialchars($entry_url); ?>" class="button small" title="Edit this entry"
													aria-label="Edit <?php echo htmlspecialchars($entry['ENTRY_Name']); ?>'s entry"><i class="fa fa-pencil" aria-hidden="true"></i></a>
												<a href="<?php echo htmlspecialchars($entry_url . '&delete=1'); ?>" class="button small" title="Delete this entry"
													aria-label="Delete <?php echo htmlspecialchars($entry['ENTRY_Name']); ?>'s entry"><i class="fa fa-trash" aria-hidden="true"></i></a>
											</td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
							<div class="entries_download">
								<a href="download_book.php?book=<?php echo rawurlencode($selected_uuid); ?>" class="button special small">
									<i class="fa fa-download" aria-hidden="true"></i> Download CSV
								</a>
							</div>
							<?php } ?>
						</div>
					</details>
					<?php } ?>
					<?php } ?>
				</div>
			</section>

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
			<script src="assets/js/create_book.js"></script>

	</body>
</html>
