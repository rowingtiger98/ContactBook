<?php
include('session.php');
require_once 'entryFunctions.php';
require_once 'userFunctions.php';

// Admin-level page: download a book's entries as a CSV file
// (download_book.php?book=<uuid>). Admins only see the books with their email
// in CB_Book_Admins; overlords see every book.
if (!user_is_admin($login_user_role)) {
    header('Location: admin.php');
    exit();
}

$books = list_books_for_admin($login_username, user_is_overlord($login_user_role));

$selected_uuid = is_string($_GET['book'] ?? null) ? $_GET['book'] : '';
$error = '';
if ($selected_uuid !== '') {
    foreach ($books as $book) {
        if ($book['CB_Book_UUID'] === $selected_uuid) {
            send_book_csv($book);
            exit();
        }
    }
    $error = 'That book could not be found, or you are not one of its admins.';
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Contact Book - Download a Book</title>
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

		<!-- Download a Book -->
			<section id="main" class="wrapper">
				<div class="inner form_panel">
					<span class="bold_heading">Download a Book</span>
					<?php if (empty($books)) { ?>
					<p>You are not an admin of any books yet. Books you create are added here automatically;
					for other books, ask one of their admins to add you.</p>
					<div class="row uniform">
						<div class="6u 12u$(small)">
							<a href="admin.php" class="button fit">Back to Dashboard</a>
						</div>
					</div>
					<?php } else { ?>
					<p>Choose a book to download its entries as a CSV file. It opens in Excel, Numbers or Google Sheets,
					with one row per entry: name, emails, phone, address, notes and the number of photos.</p>

					<form action="download_book.php" method="get">
						<div class="row uniform">
							<div class="12u$">
								<label for="book">Book</label>
								<div class="select-wrapper">
									<select name="book" id="book" required>
										<option value="" selected disabled>Choose a book…</option>
										<?php foreach ($books as $book) { ?>
										<option value="<?php echo htmlspecialchars($book['CB_Book_UUID']); ?>"><?php echo htmlspecialchars($book['CB_Book_Name_About']); ?></option>
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
								<button type="submit" class="button special fit"><i class="fa fa-download" aria-hidden="true"></i> Download CSV</button>
							</div>
							<div class="6u$ 12u$(small)">
								<a href="admin.php" class="button fit">Back to Dashboard</a>
							</div>
						</div>
					</form>
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

	</body>
</html>
