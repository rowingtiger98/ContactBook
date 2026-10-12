<?php
/**
 * ajaxDispatch.php
 *
 * Safe way for an AJAX handler file to run the function named in $_POST['method'].
 * Never call `$_POST['method']();` directly - that lets a request run ANY PHP
 * function by name. Instead, list the functions each handler allows:
 *
 *   require_once __DIR__ . '/ajaxDispatch.php';
 *
 *   dispatch_post_method(__FILE__,
 *       [],                                   // public - anyone may call
 *       ['DownloadContactsCSV']               // logged-in only
 *   );
 *
 *   function DownloadContactsCSV() { ... }
 */
require_once __DIR__ . '/SessionBootstrap.php';

/**
 * Runs $_POST['method'] only if it's on one of the handler's allowlists.
 *   $handlerFile - pass __FILE__. The dispatch only happens when this file is the
 *                  script the browser requested, so including a handler from
 *                  another page never triggers it.
 *   $public      - methods anyone may call.
 *   $loggedIn    - methods that require an active (not idle-expired) login.
 * Unknown methods get a 400; logged-in methods without a login get a 401 with
 * JSON ['failed', message] so the page's JavaScript can tell the user to log in.
 */
function dispatch_post_method(string $handlerFile, array $public, array $loggedIn = []): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['method'])) {
        return;
    }
    if (realpath($_SERVER['SCRIPT_FILENAME']) !== realpath($handlerFile)) {
        return; // handler was included by another page, not requested directly
    }

    $method = (string) $_POST['method'];

    if (in_array($method, $loggedIn, true)) {
        app_session_start();
        if (empty($_SESSION['login_user']) || app_session_idle_expired()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['failed', 'Your session has expired. Please log in again.']);
            exit();
        }
        $_SESSION['last_activity'] = time();
    } elseif (!in_array($method, $public, true)) {
        http_response_code(400);
        exit();
    }

    $method();
    exit();
}
