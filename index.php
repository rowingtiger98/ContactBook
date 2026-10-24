<?php
include('login.php'); // Handles the ADMIN login panel
require_once 'bookFunctions.php';
$admin_link = empty($_SESSION['login_user']) ? '#loginPanel' : 'admin.php';

$books = list_books();
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Contact Book</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
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

		<!-- Welcome Banner (photo: assets/images/banner-karola.jpg, from Pexels) -->
			<section id="welcome" class="welcome_banner">
				<div class="welcome_banner_text">
					<span class="bold_heading">Welcome to Contact Book</span>
					<p>Choose the book you'd like to add your information to.</p>
					<?php if (!empty($books)) { ?>
					<a href="#books" class="button special">Choose a Book</a>
					<?php } ?>
				</div>
			</section>

		<!-- One card per book in CB_Created_Books -->
			<section id="books" class="wrapper style2">
				<div class="inner">
					<?php if (empty($books)) { ?>
					<p>There are no contact books yet. Please check back soon.</p>
					<?php } else { ?>
					<!-- Filters the cards as you type (index.js). Hidden until the script shows it. -->
					<div id="book_search_box" class="book_search" hidden>
						<label for="book_search">Find a book</label>
						<input type="search" id="book_search" placeholder="Start typing a name…" autocomplete="off" />
						<p id="book_search_none" class="book_search_none" hidden>No books match &ldquo;<span></span>&rdquo;.</p>
					</div>
					<div class="row uniform tools_row" id="book_cards">
						<?php foreach ($books as $i => $book) {
							$summary = summarize_book_description($book['CB_Book_Description']);
							$last_in_row = ($i % 3 === 2) ? '$' : ''; ?>
						<div class="4u<?php echo $last_in_row; ?> 12u$(medium) book_card_cell" data-name="<?php echo htmlspecialchars($book['CB_Book_Name_About']); ?>">
							<section class="post book_card">
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
			<script src="<?php echo asset_url('assets/js/index.js'); ?>"></script>

	</body>
</html>
