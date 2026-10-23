<?php
/**
 * SessionBootstrap.php
 *
 * Centralized session configuration + lifecycle, adapted from NSXCommon.
 *
 * Usage:
 *   require_once 'SessionBootstrap.php';
 *   app_session_start();          // on every page that touches $_SESSION
 *   app_require_login();          // on pages that require an active login (redirects if idle/absent)
 *   app_login_success($username); // call right after password_verify() succeeds
 *   app_logout();                 // full teardown; used by logout.php
 */
require_once __DIR__ . '/config.php';

const APP_SESSION_NAME   = 'contactbook_session';
const APP_GC_MAXLIFETIME = 7200;  // 2 hours
const APP_IDLE_TIMEOUT   = 1800;  // 30 min idle timeout for a logged-in user
const APP_GC_PROBABILITY = 1;
const APP_GC_DIVISOR     = 100;   // 1% chance/request - set explicitly rather than left to host default

/**
 * Configures session storage/GC/cookie behavior consistently and starts the session.
 * Safe to call multiple times per request.
 */
function app_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // already started elsewhere this request - avoid double-start warnings
    }

    if (APP_SESSION_PATH !== '') {
        session_save_path(APP_SESSION_PATH);
    }
    ini_set('session.gc_maxlifetime', APP_GC_MAXLIFETIME);
    ini_set('session.gc_probability', APP_GC_PROBABILITY);
    ini_set('session.gc_divisor', APP_GC_DIVISOR);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $params['lifetime'],
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name(APP_SESSION_NAME);
    session_start();
}

/**
 * Call immediately after credentials are verified in login.php.
 * Regenerates the session ID (fixation protection) before writing authenticated state.
 */
function app_login_success(string $username): void
{
    session_regenerate_id(true);
    $_SESSION['login_user']    = $username;
    $_SESSION['login_error']   = false;
    $_SESSION['last_activity'] = time();
}

/**
 * Call at the top of any page that requires an authenticated user.
 * Enforces the idle timeout and bounces anonymous/expired sessions.
 */
function app_require_login(string $redirectTo = 'index.php'): void
{
    if (empty($_SESSION['login_user'])) {
        header('Location: ' . $redirectTo);
        exit();
    }

    if (app_session_idle_expired()) {
        app_logout($redirectTo, 'Your session has expired due to inactivity.');
    }

    $_SESSION['last_activity'] = time();
}

/**
 * True if the logged-in user has been idle longer than APP_IDLE_TIMEOUT.
 */
function app_session_idle_expired(): bool
{
    return isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > APP_IDLE_TIMEOUT);
}

/**
 * Full teardown: clears session data, expires the cookie client-side, and
 * destroys the server-side session file.
 */
function app_logout(string $redirectTo = APP_HOME_URL, ?string $message = null): void
{
    $_SESSION = [];

    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );

    session_destroy();

    if ($message !== null) {
        // Stash on a fresh session so the login form can show it once.
        session_start();
        $_SESSION['login_error_description'] = $message;
        $_SESSION['login_error'] = true;
    }

    header('Location: ' . $redirectTo);
    exit();
}

/**
 * Returns this session's CSRF token, creating it on first use. Put it in a hidden
 * "csrf_token" field on every form that changes data, and check it with
 * app_csrf_valid() when the form is posted, so other sites can't submit the form
 * using a logged-in user's session.
 */
function app_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function app_csrf_valid(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
