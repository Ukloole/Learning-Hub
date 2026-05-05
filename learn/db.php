<?php
/**
 * UKLOOLE — Database Connection
 * Shared DB helper — include this in all PHP files.
 *
 * Credentials are read from environment variables in production.
 * The fallback values below are used by the bundled cPanel install
 * (matching `ukloolec_learninghub` / `ukloolec_lbuser`).  Update them in
 * cPanel BEFORE going live, or set DB_HOST / DB_USER / DB_PASS / DB_NAME
 * as environment variables.
 */

require_once __DIR__ . '/security.php';

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

// Throw mysqli errors as exceptions so silent failures can't leak details.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    error_log('[ukloole-db] connect failed: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed.']));
}
