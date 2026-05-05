<?php
/**
 * UKLOOLE — Save Done-For-You Application
 */
session_start();
header('Content-Type: application/json');

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn || $conn->connect_error) {
    echo json_encode(['ok' => false, 'error' => 'Server error.']); exit;
}
$conn->set_charset('utf8mb4');

$input      = json_decode(file_get_contents('php://input'), true) ?: [];
$full_name  = htmlspecialchars(trim($input['full_name']   ?? ''));
$email      = filter_var(trim($input['email']      ?? ''), FILTER_VALIDATE_EMAIL);
$target     = htmlspecialchars(trim($input['target_role'] ?? ''));
$experience = htmlspecialchars(trim($input['experience']  ?? ''));
$has_cv     = htmlspecialchars(trim($input['has_cv']      ?? ''));
$countries  = htmlspecialchars(trim($input['countries']   ?? ''));
$start_when = htmlspecialchars(trim($input['start_when']  ?? ''));
$extra      = htmlspecialchars(trim($input['extra_info']  ?? ''));
$member_id  = !empty($_SESSION['community_member_id']) ? intval($_SESSION['community_member_id']) : null;

if (!$full_name || !$email || !$target) {
    echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields.']); exit;
}

$stmt = $conn->prepare("INSERT INTO dfy_applications (full_name,email,target_role,experience,has_cv,countries,start_when,extra_info,member_id) VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->bind_param("ssssssssi", $full_name, $email, $target, $experience, $has_cv, $countries, $start_when, $extra, $member_id);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['ok' => true]);
