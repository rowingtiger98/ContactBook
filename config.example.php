<?php
/**
 * config.example.php
 *
 * Copy this file to config.php and fill in the real values. config.php is
 * git-ignored so database credentials never end up in the repository.
 */

// Database (Bluehost cPanel > MySQL Databases)
const DB_HOST = 'localhost';
const DB_NAME = 'kirbypar_contactbook';
const DB_USER = 'cpaneluser_cbuser';
const DB_PASS = 'change-me';

// Session storage. Point this at a directory OUTSIDE public_html so session
// files can't be fetched over the web, e.g. '/home2/cpaneluser/sessions/contactbook'.
// Leave empty to use PHP's default session path.
const APP_SESSION_PATH = '';

// Where logout sends the user.
const APP_HOME_URL = 'index.php';

// Full public URL of the site (no trailing slash). Used to build password reset
// links in emails - never taken from the request, so a forged Host header can't
// redirect reset links to another site.
const APP_BASE_URL = 'https://www.example.com/contactbook';

// "From" address on password reset emails. Use an address on the site's own
// domain (set up in Bluehost cPanel > Email Accounts) so mail isn't flagged as spam.
const MAIL_FROM = 'no-reply@example.com';
