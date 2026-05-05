<?php
/**
 * UKLOOLE — Thread Message Handler
 * Handles: start thread, reply to thread, edit message (within 3 min)
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['community_member_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Not logged in']); exit;
}

$member_id = intval($_SESSION['community_member_id']);

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn || $conn->connect_error) {
    echo json_encode(['ok' => false, 'error' => 'Server error']); exit;
}
$conn->set_charset('utf8mb4');

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

// ---- Start new thread ----
if ($action === 'new_thread') {
    $subject = trim($input['subject'] ?? '');
    $body    = trim($input['body'] ?? '');
    if (strlen($subject) < 3 || strlen($body) < 3) {
        echo json_encode(['ok' => false, 'error' => 'Please enter a subject and message.']); exit;
    }
    $stmt = $conn->prepare("INSERT INTO community_threads (member_id, subject) VALUES (?,?)");
    $stmt->bind_param("is", $member_id, $subject);
    $stmt->execute();
    $thread_id = $conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO community_thread_messages (thread_id, sender, body) VALUES (?,?,?)");
    $s = 'member';
    $stmt->bind_param("iss", $thread_id, $s, $body);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['ok' => true, 'thread_id' => $thread_id]);

// ---- Reply to thread ----
} elseif ($action === 'reply') {
    $thread_id = intval($input['thread_id'] ?? 0);
    $body    = trim($input['body'] ?? '');
    if (!$thread_id || strlen($body) < 1) {
        echo json_encode(['ok' => false, 'error' => 'Empty reply.']); exit;
    }
    // Verify thread belongs to this member
    $stmt = $conn->prepare("SELECT id FROM community_threads WHERE id=? AND member_id=?");
    $stmt->bind_param("ii", $thread_id, $member_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(['ok' => false, 'error' => 'Thread not found.']); exit;
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO community_thread_messages (thread_id, sender, body) VALUES (?,?,?)");
    $s = 'member';
    $stmt->bind_param("iss", $thread_id, $s, $body);
    $stmt->execute();
    $msg_id = $conn->insert_id;
    $stmt->close();

    // Update thread timestamp & reopen if closed
    $conn->query("UPDATE community_threads SET status='open', updated_at=NOW() WHERE id=$thread_id");
    $conn->close();
    echo json_encode(['ok' => true, 'message_id' => $msg_id]);

// ---- Edit message (within 3 minutes) ----
} elseif ($action === 'edit_message') {
    $msg_id = intval($input['message_id'] ?? 0);
    $body    = trim($input['body'] ?? '');
    if (!$msg_id || strlen($body) < 1) {
        echo json_encode(['ok' => false, 'error' => 'Invalid request.']); exit;
    }
    // Check ownership and age
    $stmt = $conn->prepare("
        SELECT m.id, m.created_at, t.member_id
        FROM community_thread_messages m
        JOIN community_threads t ON t.id = m.thread_id
        WHERE m.id=? AND m.sender='member'
    ");
    $stmt->bind_param("i", $msg_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || $row['member_id'] != $member_id) {
        echo json_encode(['ok' => false, 'error' => 'Not allowed.']); exit;
    }
    $age_seconds = time() - strtotime($row['created_at']);
    if ($age_seconds > 180) {
        echo json_encode(['ok' => false, 'error' => 'Edit window has expired (3 minutes).']); exit;
    }
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE community_thread_messages SET body=?, edited_at=? WHERE id=?");
    $stmt->bind_param("ssi", $body, $now, $msg_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['ok' => true]);

} else {
    echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
}