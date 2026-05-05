<?php
/**
 * UKLOOLE — Q&A Question Submission
 * Called via fetch() from community-dashboard.php
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['community_member_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn || $conn->connect_error) {
    echo json_encode(['ok' => false, 'error' => 'Server error']);
    exit;
}
$conn->set_charset('utf8mb4');

$input     = json_decode(file_get_contents('php://input'), true) ?: [];
$question  = htmlspecialchars(trim($input['question'] ?? ''));
$member_id = intval($_SESSION['community_member_id']);

if (strlen($question) < 5) {
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid question.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO community_questions (member_id, question) VALUES (?,?)");
$stmt->bind_param("is", $member_id, $question);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'message' => 'Question submitted! We will reply soon.']);
