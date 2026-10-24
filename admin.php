<?php
include('session.php');
require_once 'userFunctions.php';

$is_admin    = user_is_admin($login_user_role);     // admin or overlord
$is_overlord = user_is_overlord($login_user_role);  // sees overlord-level tools too
$panel_name = $is_admin ? 'Administrative Panel' : 'Account Panel';

// One-time message from another admin page (e.g. "book created").
$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Contact Book - Admin</title>
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

		<!-- Main -->
			<section id="main" class="wrapper">
				<div class="inner">
					<h3><?php echo $panel_name; ?> - <?php echo htmlspecialchars($login_username); ?> (<?php echo htmlspecialchars($login_user_role); ?>)</h3>
					<?php if ($flash_message !== '') { ?>
						<p class="flash_message"><?php echo htmlspecialchars($flash_message); ?></p>
					<?php } ?>
				</div>
			</section>

		<!-- Tools Section Based upon Role -->
			<section id="tools" class="wrapper style2">
				<div class="inner">
					<?php if ($is_admin) { ?>
					<div class="row uniform tools_row">
						<div class="4u 12u$(medium)">
							<section class="post">
								<div class="content">
									<h3>Create a New Book</h3>
									<p>Start a new contact book for someone. Each book gets its own folder on the server.</p>
									<a href="create_book.php" class="button fit special">Create a Book</a>
								</div>
							</section>
						</div>
						<div class="4u 12u$(medium)">
							<section class="post">
								<div class="content">
									<h3>Manage a Book</h3>
									<p>Choose one of the books you're an admin of to work on it.</p>
									<a href="manage_book.php" class="button fit special">Manage a Book</a>
								</div>
							</section>
						</div>
						<div class="4u$ 12u$(medium)">
							<section class="post">
								<div class="content">
									<h3>Download a Book</h3>
									<p>Download a book's entries as a CSV file, ready for Excel or Google Sheets.</p>
									<a href="download_book.php" class="button fit special">Download a Book</a>
								</div>
							</section>
						</div>
						<?php if ($is_overlord) { ?>
						<!-- Overlord-level tools -->
						<div class="4u 12u$(medium)">
							<section class="post">
								<div class="content">
									<h3>Create a New User</h3>
									<p>Add an admin or overlord. They'll be emailed a link to choose their password.</p>
									<a href="create_user.php" class="button fit special">Create a User</a>
								</div>
							</section>
						</div>
						<?php } ?>
					</div>
					<?php } else { ?>
					<p>Your account does not have admin rights. Contact a Contact Book administrator if you need access to the admin tools.</p>
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
			<script src="assets/js/admin.js"></script>

	</body>
</html>
