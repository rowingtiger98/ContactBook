<?php
include('session.php');
require_once 'userFunctions.php';

// Overlord-only page.
if (!user_is_overlord($login_user_role)) {
    header('Location: admin.php');
    exit();
}

$error = '';
$name  = '';
$email = '';
$role  = USER_ROLE_ADMIN;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = is_string($_POST['user_name'] ?? null) ? $_POST['user_name'] : '';
    $email = is_string($_POST['user_email'] ?? null) ? $_POST['user_email'] : '';
    $role  = is_string($_POST['user_role'] ?? null) ? $_POST['user_role'] : '';

    if (!app_csrf_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Your session has changed since this page was opened. Please try again.';
    } else {
        $result = create_user_account($name, $email, $role);
        if ($result['success']) {
            // Post/Redirect/Get: refreshing the dashboard won't create the user twice.
            $_SESSION['flash_message'] = $result['email_sent']
                ? 'Created ' . USER_ROLES[$result['role']] . ' ' . $result['name'] . ' and emailed a link to ' . $result['email'] . ' to set their password.'
                : 'Created ' . USER_ROLES[$result['role']] . ' ' . $result['name'] . ', but the email to ' . $result['email'] . ' could not be sent. '
                  . 'Ask them to use "Forgot your password?" on the login panel to set their password.';
            header('Location: admin.php');
            exit();
        }
        $error = $result['error'];
        $name  = $result['name'];
        $email = $result['email'];
    }
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>ContactBook - Create a New User</title>
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

		<!-- Create a New User -->
			<section id="main" class="wrapper">
				<div class="inner form_panel">
					<span class="bold_heading">Create a New User</span>
					<p>The new user will be emailed a link to choose their password. The link is good for <?php echo INVITE_TOKEN_LIFETIME / 86400; ?> days.</p>

					<form action="create_user.php" method="post">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(app_csrf_token()); ?>" />
						<div class="row uniform">
							<div class="12u$">
								<label for="user_name">Name</label>
								<input type="text" name="user_name" id="user_name" value="<?php echo htmlspecialchars($name); ?>"
									maxlength="<?php echo USER_NAME_MAX_LENGTH; ?>" autocomplete="off" required autofocus />
							</div>
							<div class="12u$">
								<label for="user_email">Email</label>
								<input type="email" name="user_email" id="user_email" value="<?php echo htmlspecialchars($email); ?>"
									maxlength="<?php echo USER_EMAIL_MAX_LENGTH; ?>" autocomplete="off" required />
								<p class="field_note">This is also their username for logging in.</p>
							</div>
							<div class="12u$">
								<label for="user_role">Role</label>
								<div class="select-wrapper">
									<select name="user_role" id="user_role" required>
										<?php foreach (USER_ROLES as $value => $label) { ?>
										<option value="<?php echo $value; ?>"<?php if ($value === $role) { ?> selected<?php } ?>><?php echo $label; ?></option>
										<?php } ?>
									</select>
								</div>
								<p class="field_note">Admins get the admin tools. Overlords also get overlord-level tools, such as creating users.</p>
							</div>
							<?php if ($error !== '') { ?>
							<div class="12u$">
								<span class="login_error_message"><?php echo htmlspecialchars($error); ?></span>
							</div>
							<?php } ?>
							<div class="6u 12u$(small)">
								<input type="submit" class="button special fit" value="Create User" />
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

	</body>
</html>
