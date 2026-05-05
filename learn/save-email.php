<?php
/**
 * UKLOOLE — Save Email (Free Download + Assessment requests)
 * Saves to emails.txt AND newsletter_subscribers DB table
 */
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$file  = htmlspecialchars(trim($_POST['file'] ?? ''));

if (!$email) { echo "error"; exit; }

// Save to flat file (legacy)
file_put_contents(__DIR__ . "/emails.txt", $email . "," . $file . "\n", FILE_APPEND | LOCK_EX);

// Save to DB
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn && !$conn->connect_error) {
    $conn->set_charset('utf8mb4');
    $conn->query("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(200) NOT NULL UNIQUE,
        name VARCHAR(150) DEFAULT '',
        source VARCHAR(50) DEFAULT 'download',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email)
    ) ENGINE=InnoDB");
    $source = $file ?: 'download';
    $stmt = $conn->prepare("INSERT IGNORE INTO newsletter_subscribers (email, source) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $source);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Send download email
$subject = "Your Ukloole Download Link";
$message = "Hello,\n\nHere is your download link:\nhttps://drive.google.com/drive/folders/1gsWAX0sYff3odoZln4jagqSXbsrR7W9D?usp=drive_link\n\n— The Ukloole Team";
$headers = "From: Ukloole <learn@ukloole.com>\r\nReply-To: learn@ukloole.com\r\nContent-Type: text/plain; charset=UTF-8";
mail($email, $subject, $message, $headers);

echo "success";
?>