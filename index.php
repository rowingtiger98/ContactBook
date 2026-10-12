<?php
include('login.php'); // Handles the ADMIN login panel
$admin_link = empty($_SESSION['login_user']) ? '#loginPanel' : 'admin.php';
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
			<section id="main" class="wrapper style5">
				<div class="inner hello">
					<span class="bold_heading"><?php echo 'Hello World'; ?></span>
					<p>Today is <?php echo date('l, F j, Y'); ?>.</p>
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
