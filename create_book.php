<?php
include('session.php');
require_once 'bookFunctions.php';

// Admin-only page.
if ($login_user_role !== 'admin') {
    header('Location: admin.php');
    exit();
}

$error       = '';
$name        = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $_POST['book_name'] ?? '';
    $description = $_POST['book_description'] ?? '';

    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        // Upload bigger than the server's post_max_size - PHP discards the whole form.
        $error = 'The photo is too large. Please choose one under ' . (BOOK_PHOTO_MAX_BYTES / 1048576) . ' MB.';
    } elseif (!app_csrf_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Your session has changed since this page was opened. Please try again.';
    } else {
        $result = create_book($name, $description, $_FILES['book_photo'] ?? null);
        if ($result['success']) {
            // Post/Redirect/Get: refreshing the dashboard won't create a second book.
            $_SESSION['flash_message'] = 'Created a new book for ' . $result['name'] . '.';
            header('Location: admin.php');
            exit();
        }
        $error       = $result['error'];
        $name        = $result['name'];
        $description = $result['description'];
    }
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>ContactBook - Create a New Book</title>
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

		<!-- Create a New Book -->
			<section id="main" class="wrapper">
				<div class="inner form_panel">
					<span class="bold_heading">Create a New Book</span>
					<p>Enter the name of the person this contact book is for. The description and photo are optional.</p>

					<form action="create_book.php" method="post" enctype="multipart/form-data">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(app_csrf_token()); ?>" />
						<div class="row uniform">
							<div class="12u$">
								<label for="book_name">Book is for</label>
								<input type="text" name="book_name" id="book_name" value="<?php echo htmlspecialchars($name); ?>"
									maxlength="<?php echo BOOK_NAME_MAX_LENGTH; ?>" placeholder="e.g. Mary O'Brien" autocomplete="off" required autofocus />
							</div>
							<div class="12u$">
								<label for="book_description">Description</label>
								<textarea name="book_description" id="book_description" rows="5"
									maxlength="<?php echo BOOK_DESCRIPTION_MAX_LENGTH; ?>" placeholder="What is this book about?"><?php echo htmlspecialchars($description); ?></textarea>
							</div>
							<div class="12u$">
								<label for="book_photo">Photo</label>
								<input type="file" name="book_photo" id="book_photo" accept="image/jpeg,image/png,image/webp" />
								<p class="field_note">JPEG, PNG or WebP, up to <?php echo BOOK_PHOTO_MAX_BYTES / 1048576; ?> MB.
								<?php if ($error !== '') { ?>If you chose a photo, please choose it again.<?php } ?></p>
								<label for="book_photo" id="book_photo_placeholder" class="book_photo_placeholder" title="Choose a photo">
									<i class="fa fa-camera" aria-hidden="true"></i>
									<span>No photo chosen</span>
								</label>
								<img id="book_photo_preview" class="book_photo_preview" alt="Selected photo preview" hidden />
							</div>
							<?php if ($error !== '') { ?>
							<div class="12u$">
								<span class="login_error_message"><?php echo htmlspecialchars($error); ?></span>
							</div>
							<?php } ?>
							<div class="6u 12u$(small)">
								<input type="submit" class="button special fit" value="Save" />
							</div>
							<div class="6u$ 12u$(small)">
								<a href="admin.php" class="button fit">Cancel</a>
							</div>
						</div>
					</form>
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
			<script src="assets/js/create_book.js"></script>

	</body>
</html>
