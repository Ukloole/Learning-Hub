<?php
/**
 * UKLOOLE — Payment Verification & Download Link Generator
 * Called by Paystack callback to verify payment and issue download links
 *
 * Required env vars (set in your hosting environment or .env):
 *   PAYSTACK_SECRET_KEY — your Paystack secret key
 *   SITE_URL            — your full domain, e.g. https://ukloole.com
 *
 * Database: MySQL (create with SQL_schema.sql)
 */

header('Content-Type: application/json');

// --- Config ---
$secret_key = getenv('PAYSTACK_SECRET_KEY') ?: 'sk_live_YOUR_SECRET_KEY_HERE';
$site_url   = getenv('SITE_URL') ?: 'https://ukloole.com';

// --- DB connection ---
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'ukloole';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// --- Parse request ---
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$reference = htmlspecialchars(trim($data['reference'] ?? ''));
$product   = htmlspecialchars(trim($data['product'] ?? 'bundle'));
$email     = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$name      = htmlspecialchars(trim($data['name'] ?? ''));

if (!$reference || !$email) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// --- Verify payment with Paystack ---
$url = "https://api.paystack.co/transaction/verify/" . urlencode($reference);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer $secret_key"],
]);
$response = curl_exec($ch);
$curl_err  = curl_error($ch);
curl_close($ch);

if ($curl_err) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Could not reach Paystack']);
    exit;
}

$result = json_decode($response, true);

if (($result['data']['status'] ?? '') !== 'success') {
    echo json_encode(['status' => 'error', 'message' => 'Payment not successful']);
    exit;
}

// --- Generate secure download token ---
$token   = bin2hex(random_bytes(24));
$expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours

// --- Save order ---
$stmt = $conn->prepare(
    "INSERT INTO orders (name, email, product, reference, token, expires_at)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssssss", $name, $email, $product, $reference, $token, $expires);
$stmt->execute();
$stmt->close();

// --- Build download link ---
$download_link = "$site_url/download.php?token=$token";

// --- Send email ---
$subject = "Your Ukloole Download Link";
$body    = "Hello $name,\n\n"
         . "Thank you for your purchase! 🎉\n\n"
         . "Click the link below to download your file:\n"
         . "$download_link\n\n"
         . "⚠ This link expires in 24 hours.\n\n"
         . "— The Ukloole Team";

$headers = implode("\r\n", [
    "From: Ukloole <no-reply@ukloole.com>",
    "Reply-To: learn@ukloole.com",
    "Content-Type: text/plain; charset=UTF-8",
]);

mail($email, $subject, $body, $headers);

echo json_encode([
    'status'        => 'success',
    'download_link' => $download_link,
]);
