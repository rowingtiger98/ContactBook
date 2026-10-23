<?php
include('session.php');
require_once 'bookFunctions.php';
require_once 'userFunctions.php';

// Admin-level page (admins and overlords). Admins only see the books with
// their email in CB_Book_Admins; overlords see every book.
if (!user_is_admin($login_user_role)) {
    header('Location: admin.php');
    exit();
}

$books = list_books_for_admin($login_username, user_is_overlord($login_user_role));

// The chosen book (manage_book.php?book=<uuid>) - only if it's one of this user's books.
$selected_uuid = is_string($_GET['book'] ?? null) ? $_GET['book'] : '';
$selected_book = null;
foreach ($books as $book) {
    if ($book['CB_Book_UUID'] === $selected_uuid) {
        $selected_book = $book;
    }
}
$error = ($selected_uuid !== '' && $selected_book === null)
    ? 'That book could not be found, or you are not one of its admins.'
    : '';
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>ContactBook - Manage a Book</title>
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
							<?php if ($error !== '') { ?>
							<div class="12u$">
								<span class="login_error_message"><?php echo htmlspecialchars($error); ?></span>
							</div>
							<?php } ?>
							<div class="6u 12u$(small)">
								<input type="submit" class="button special fit" value="Manage" />
							</div>
							<div class="6u$ 12u$(small)">
								<a href="admin.php" class="button fit">Cancel</a>
							</div>
						</div>
					</form>

					<?php if ($selected_book !== null) { ?>
					<hr />
					<h3><?php echo htmlspecialchars($selected_book['CB_Book_Name_About']); ?></h3>
					<?php if (($selected_book['CB_Book_Description'] ?? '') !== '') { ?>
					<p><?php echo nl2br(htmlspecialchars($selected_book['CB_Book_Description'])); ?></p>
					<?php } ?>
					<p class="field_note">Book admins: <?php echo htmlspecialchars(implode(', ', split_book_admins($selected_book['CB_Book_Admins']))); ?></p>
					<?php } ?>
					<?php } ?>
				</div>
			</section>

		<!-- Footer -->
			<footer id="footer">
				<div class="inner">
					<p class="copyright">&copy; <?php echo date('Y'); ?> ContactBook. All rights reserved.</p>
				</div>
			</footer>

		<!-- Scripts -->
			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/jquery.dropotron.min.js"></script>
			<script src="assets/js/jquery.scrollex.min.js"></script>
			<script src="assets/js/skel.min.js"></script>
			<script src="assets/js/util.js"></script>
			<script src="assets/js/main.js"></script>

	</body>
</html>
