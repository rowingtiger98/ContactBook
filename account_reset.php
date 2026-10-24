<?php
/**
 * account_reset.php
 *
 * Forgot-password page (adapted from NSXCommon). Without a resetid it asks for the
 * user's email and sends a reset link; the link brings them back here with
 * ?resetid=... to choose a new password.
 */
require_once 'passwordReset.php';

$page_type = 'SendResetLink';  // SendResetLink | LinkSent | SetNewPassword | PasswordChanged | Expired | Invalid
$token     = '';
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_link') {
    reset_request($_POST['email'] ?? '');
    $page_type = 'LinkSent';  // same message whether or not the email has an account

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_password') {
    $token     = $_POST['resetid'] ?? '';
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Your new password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        $page_type = 'SetNewPassword';
    } elseif ($password !== $password2) {
        $error = 'The two passwords don\'t match. Please try again.';
        $page_type = 'SetNewPassword';
    } elseif (reset_complete($token, $password)) {
        $page_type = 'PasswordChanged';
    } else {
        // Token was used, expired, or never existed between showing the form and submitting it.
        $page_type = (reset_lookup($token) === 'expired') ? 'Expired' : 'Invalid';
    }

} elseif (isset($_GET['resetid'])) {
    $token = $_GET['resetid'];
    $state = reset_lookup($token);
    $page_type = ($state === 'valid') ? 'SetNewPassword' : (($state === 'expired') ? 'Expired' : 'Invalid');
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Contact Book - Password Reset</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="referrer" content="no-referrer" /><!-- keep the reset token out of Referer headers -->
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
					</ul>
				</nav>
			</header>

		<!-- Password Reset -->
			<section id="main" class="wrapper">
				<div class="inner reset_panel">
					<span class="bold_heading">Password Reset</span>

					<?php if ($page_type === 'SendResetLink') { ?>
						<p>Enter the email address you use to log in to Contact Book. We'll email you a link to choose a new password.
						The link is good for <?php echo RESET_TOKEN_LIFETIME / 60; ?> minutes and can only be used once.</p>
						<form action="account_reset.php" method="post">
							<input type="hidden" name="action" value="send_link" />
							<div class="row uniform">
								<div class="12u$">
									<input type="email" name="email" id="reset_password_email_address" placeholder="Enter your email address" autocomplete="email" required />
								</div>
								<div class="12u$">
									<input type="submit" class="button special fit" value="Send Reset Link" />
								</div>
							</div>
						</form>

					<?php } elseif ($page_type === 'LinkSent') { ?>
						<p>If that email address belongs to a Contact Book account, a password reset link is on its way. Check your inbox
						(and spam folder). The link expires in <?php echo RESET_TOKEN_LIFETIME / 60; ?> minutes.</p>
						<a href="index.php" class="button fit">Return to the Home Page</a>

					<?php } elseif ($page_type === 'SetNewPassword') { ?>
						<p>Enter your new password in the first box and re-enter it in the second box to confirm.</p>
						<form action="account_reset.php" method="post">
							<input type="hidden" name="action" value="set_password" />
							<input type="hidden" name="resetid" value="<?php echo htmlspecialchars($token); ?>" />
							<div class="row uniform">
								<div class="6u 12u$(small)">
									<input type="password" name="password" id="new_password" placeholder="New Password" autocomplete="new-password" minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required />
								</div>
								<div class="6u$ 12u$(small)">
									<input type="password" name="password2" id="new_password_confirm" placeholder="Confirm New Password" autocomplete="new-password" required />
								</div>
								<div class="12u$">
									<input type="submit" id="reset_password_button" class="button special fit" value="Reset Password" />
								</div>
							</div>
							<?php if ($error !== '') {
								print '<span class="login_error_message">' . htmlspecialchars($error) . '</span>';
							} ?>
						</form>

					<?php } elseif ($page_type === 'PasswordChanged') { ?>
						<p>Your password has been updated. You can now log in with your new password.</p>
						<a href="index.php#loginPanel" class="button special fit">Log In</a>

					<?php } elseif ($page_type === 'Expired') { ?>
						<p>This password reset link is more than <?php echo RESET_TOKEN_LIFETIME / 60; ?> minutes old and has expired.</p>
						<a href="account_reset.php" class="button special fit">Request a New Link</a>

					<?php } else { ?>
						<p>This password reset link isn't valid. It may have already been used, or a newer link may have been sent since.</p>
						<a href="account_reset.php" class="button special fit">Request a New Link</a>
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
			<script src="assets/js/account_reset.js"></script>

	</body>
</html>
