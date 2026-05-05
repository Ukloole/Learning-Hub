<?php
/**
 * UKLOOLE - Secure File Download
 * Validates token, checks expiry, serves single file or zips multiple files
 */

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    http_response_code(500);
    exit("Server error. Please contact support.");
}

$token = htmlspecialchars(trim($_GET['token'] ?? ''));

if (!$token) {
    http_response_code(400);
    exit("Invalid request.");
}

// --- Look up token ---
$stmt = $conn->prepare(
    "SELECT product, expires_at, downloaded FROM orders WHERE token = ? LIMIT 1"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    exit("This download link is invalid or does not exist.");
}

if (strtotime($row['expires_at']) < time()) {
    http_response_code(410);
    exit("This download link has expired. Please contact learn@ukloole.com for help.");
}

// --- One-time download check ---
if ((int)$row['downloaded'] >= 1) {
    http_response_code(410);
    exit("This download link has already been used. Each purchase allows one download. Please contact learn@ukloole.com if you need help.");
}

// --- Resolve product field: JSON array (new) or plain string (legacy) ---
$product_field = $row['product'];
$item_names    = json_decode($product_field, true);
if (!is_array($item_names)) {
    $item_names = [$product_field]; // legacy single-item string
}
// Strip quantity suffixes from every item e.g. "Brag File x1" -> "Brag File"
$item_names = array_map(function($n) {
    return trim(preg_replace('/\s+x\d+$/i', '', trim($n)));
}, $item_names);

// --- Helper: resolve one item name to a filepath ---
function resolve_filepath($conn, $name) {
    $base = __DIR__ . '/downloads/';

    // Strip quantity suffixes like " x1", " x2", " X3" etc.
    $clean = trim(preg_replace('/\s+x\d+$/i', '', trim($name)));

    // Static legacy mappings (kept for backward compat)
    $static = [
        'workbook' => $base . 'customer-service-workbook.docx',
        'guide'    => $base . 'interview-clean-sheet.pdf',
    ];
    $key = strtolower($clean);
    if (isset($static[$key])) return $static[$key];

    // 1) Look up resources table by name (case-insensitive)
    $stmt = $conn->prepare(
        "SELECT file_key FROM resources WHERE TRIM(LOWER(name)) = ? LIMIT 1"
    );
    $lower = strtolower($clean);
    $stmt->bind_param("s", $lower);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && $res['file_key']) {
        return $base . ltrim($res['file_key'], '/');
    }

    // 2) Fallback: auto-derive filename from product name
    //    "Brag File"  -> "brag-file"
    //    "CV Template Pro" -> "cv-template-pro"
    $extensions = ['pdf', 'docx', 'xlsx', 'doc', 'zip', 'pptx'];
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $clean));
    $slug = trim($slug, '-');

    // Check if a file with any common extension exists
    foreach ($extensions as $ext) {
        $candidate = $base . $slug . '.' . $ext;
        if (file_exists($candidate)) return $candidate;
    }

    return null;
}

// --- Resolve all items to existing filepaths ---
$filepaths = [];
foreach ($item_names as $item_name) {
    $fp = resolve_filepath($conn, $item_name);
    if ($fp && file_exists($fp)) {
        $filepaths[] = $fp;
    }
}

if (empty($filepaths)) {
    $debug_info = [];
    foreach ($item_names as $item_name) {
        $fp = resolve_filepath($conn, $item_name);
        $debug_info[] = [
            'item'     => $item_name,
            'resolved' => $fp,
            'exists'   => $fp ? file_exists($fp) : false,
        ];
    }
    http_response_code(404);
    exit("File not found. Please contact learn@ukloole.com\n\nDebug: " . json_encode($debug_info, JSON_PRETTY_PRINT));
}

// --- Mark as downloaded BEFORE serving ---
$upd = $conn->prepare("UPDATE orders SET downloaded = downloaded + 1 WHERE token = ?");
$upd->bind_param("s", $token);
$upd->execute();
$upd->close();

// --- Serve: single file direct, multiple files as zip ---
if (count($filepaths) === 1) {
    $filepath = $filepaths[0];
    $filename = basename($filepath);
    $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimes = [
        'pdf'  => 'application/pdf',
        'zip'  => 'application/zip',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc'  => 'application/msword',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, no-store');
    header('Pragma: no-cache');
    readfile($filepath);
    exit;
}

// --- Multiple files: zip them together ---
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit("Could not bundle files. Please contact learn@ukloole.com with your reference.");
}

$zip_name = 'ukloole-downloads-' . substr($token, 0, 8) . '.zip';
$zip_path = sys_get_temp_dir() . '/' . $zip_name;

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit("Could not create download bundle. Please contact learn@ukloole.com");
}
foreach ($filepaths as $fp) {
    $zip->addFile($fp, basename($fp));
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_name . '"');
header('Content-Length: ' . filesize($zip_path));
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');
readfile($zip_path);
@unlink($zip_path);
exit;