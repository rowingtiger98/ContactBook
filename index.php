<?php
include('login.php'); // Handles the ADMIN login panel
require_once 'bookFunctions.php';
$admin_link = empty($_SESSION['login_user']) ? '#loginPanel' : 'admin.php';

$books = list_books();
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>ContactBook</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<link rel="stylesheet" href="assets/css/contactbook.css" />
	</head>
	<body>

		<!-- Header -->
			<header id="header">
				<h1><a href="index.php">ContactBook</a></h1>
				<nav id="nav">
					<ul>
						<li><a href="index.php">Home</a></li>
						<li><a href="<?php echo $admin_link; ?>">Admin</a></li>
					</ul>
				</nav>
			</header>

		<!-- Main -->
			<section id="main" class="wrapper">
				<div class="inner">
					<span class="bold_heading">Contact Books</span>
					<p>Choose the book you'd like to add your information to.</p>
				</div>
			</section>

		<!-- One card per book in CB_Created_Books -->
			<section id="books" class="wrapper style2">
				<div class="inner">
					<?php if (empty($books)) { ?>
					<p>There are no contact books yet. Please check back soon.</p>
					<?php } else { ?>
					<div class="row uniform tools_row">
						<?php foreach ($books as $i => $book) {
							$summary = summarize_book_description($book['CB_Book_Description']);
							$last_in_row = ($i % 3 === 2) ? '$' : ''; ?>
						<div class="4u<?php echo $last_in_row; ?> 12u$(medium)">
							<section class="post">
								<div class="content">
									<h3><?php echo htmlspecialchars($book['CB_Book_Name_About']); ?></h3>
									<?php if ($summary !== '') { ?>
									<p><?php echo htmlspecialchars($summary); ?></p>
									<?php } ?>
									<a href="add_entry.php?book=<?php echo urlencode($book['CB_Book_UUID']); ?>" class="button fit special">Add Your Information</a>
								</div>
							</section>
						</div>
						<?php } ?>
					</div>
					<?php } ?>
				</div>
			</section>

		<!-- Admin Login Panel -->
			<?php include 'login_modal.php'; ?>

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
