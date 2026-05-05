<?php
/**
 * UKLOOLE — Save Order after Paystack payment
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/mailer.php';

$input     = json_decode(file_get_contents('php://input'), true);
$name      = htmlspecialchars(trim($input['name'] ?? ''));
$email     = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$reference = htmlspecialchars(trim($input['reference'] ?? ''));
$amount    = floatval($input['amount'] ?? 0);
$items     = $input['items'] ?? [];

if (!$email || !$reference || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

// Store item names as JSON so download.php can look up each file individually
$item_names = array_map(function($item) {
    return trim($item['name'] ?? '');
}, $items);
$item_names = array_filter($item_names); // remove blanks
$product = json_encode(array_values($item_names)); // e.g. ["CV Guide","Interview Sheet"]
$product_display = implode(', ', $item_names); // for email display

$conn = new mysqli('localhost', 'ukloolec_lbuser', 'Admin4ukloole', 'ukloolec_learninghub');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$check = $conn->prepare("SELECT id, token FROM orders WHERE reference = ?");
$check->bind_param("s", $reference);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    $conn->close();
    sendOrderEmail($name, $email, $product_display, $existing['token']); // $product_display already set above
    echo json_encode(['success' => true, 'message' => 'Order already recorded.']);
    exit;
}

$token   = bin2hex(random_bytes(20));
$expires = date('Y-m-d H:i:s', strtotime('+7 days'));

$stmt = $conn->prepare(
    "INSERT INTO orders (name, email, product, reference, token, amount, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, 'paid', ?)"
);
$stmt->bind_param("sssssds", $name, $email, $product, $reference, $token, $amount, $expires);
$stmt->execute();
$stmt->close();
$conn->close();

sendOrderEmail($name, $email, $product_display, $token);

echo json_encode([
    'success'      => true,
    'message'      => 'Order saved.',
    'download_url' => 'download.php?token=' . $token,
]);

function sendOrderEmail(string $name, string $email, string $product, string $token): void {
    $link    = 'https://learn.ukloole.com/download.php?token=' . $token;
    $subject = 'Your Ukloole Download is Ready!';

    $plainBody = "Hello " . ($name ?: 'there') . ",\n\n"
               . "Thank you for your purchase of: {$product}\n\n"
               . "Download link:\n{$link}\n\n"
               . "This link expires in 7 days and can only be used once.\n\n"
               . "Issues? Contact learn@ukloole.com\n\n"
               . "— The Ukloole Team";

    $htmlBody = lh_email_template('Your Download is Ready! 🎉', '
      <p style="color:#444;line-height:1.7;">Hello <strong>' . htmlspecialchars($name ?: 'there') . '</strong>,</p>
      <p style="color:#444;line-height:1.7;">
        Thank you for your purchase of:<br>
        <strong>' . htmlspecialchars($product) . '</strong>
      </p>
      <p style="text-align:center;margin:28px 0;">
        <a href="' . htmlspecialchars($link) . '"
           style="background:#1a3c5e;color:#fff;padding:14px 32px;border-radius:6px;
                  text-decoration:none;font-weight:bold;font-size:15px;display:inline-block;">
          Download My File
        </a>
      </p>
      <p style="color:#666;font-size:13px;line-height:1.6;">
        Or copy this link:<br>
        <a href="' . htmlspecialchars($link) . '" style="color:#1a3c5e;word-break:break-all;">' . htmlspecialchars($link) . '</a>
      </p>
      <p style="color:#888;font-size:13px;">
        This link expires in <strong>7 days</strong> and can only be used <strong>once</strong>.<br>
        Having issues? <a href="mailto:learn@ukloole.com" style="color:#1a3c5e;">learn@ukloole.com</a>
      </p>
    ');

    lh_send_email($email, $name, $subject, $htmlBody, $plainBody);
}