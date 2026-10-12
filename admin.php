<?php
include('session.php');

$is_admin = ($login_user_role === 'admin');
$panel_name = $is_admin ? 'Administrative Panel' : 'Account Panel';
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>ContactBook - Admin</title>
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
									<h3>Manage Contacts</h3>
									<p>Add, view, edit and delete entries in the ContactBook database.</p>
									<a href="#" class="button fit special">Coming Soon...</a>
								</div>
							</section>
						</div>
						<div class="4u 12u$(medium)">
							<section class="post">
								<div class="content">
									<h3>Download Contacts</h3>
									<p>Download all contact information as a CSV file.</p>
									<a href="#" class="button fit special">Coming Soon...</a>
								</div>
							</section>
						</div>
						<div class="4u$ 12u$(medium)">
							<section class="post">
								<div class="content">
									<h3>User Accounts</h3>
									<p>Create and manage the users that can log in to ContactBook.</p>
									<a href="#" class="button fit special">Coming Soon...</a>
								</div>
							</section>
						</div>
					</div>
					<?php } else { ?>
					<p>Your account does not have admin rights. Contact a ContactBook administrator if you need access to the admin tools.</p>
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
			<script src="assets/js/admin.js"></script>

	</body>
</html>
