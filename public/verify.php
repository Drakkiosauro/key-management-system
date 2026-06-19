<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/rate_limit.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function isBanned($pdo, $userId, $hwid, $ip) {
    $stmt = $pdo->prepare("SELECT * FROM banned_users WHERE (user_id = ? OR hwid = ? OR ip = ?) AND (unbanned_at IS NULL OR unbanned_at > NOW()) LIMIT 1");
    $stmt->execute([$userId, $hwid, $ip]);
    return $stmt->fetch();
}

function sendDiscordEmbed($title, $description, $color) {
    $webhook = getenv('DISCORD_WEBHOOK');
    if (empty($webhook)) {
        return;
    }
    
    $data = [
        'embeds' => [[
            'title' => $title,
            'description' => $description,
            'color' => $color,
            'timestamp' => date('c')
        ]]
    ];
    
    $ch = curl_init($webhook);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    @curl_exec($ch);
    curl_close($ch);
}

function logAction($pdo, $key, $action, $data) {
    $stmt = $pdo->prepare("INSERT INTO logs (key_code, action, user_id, username, display_name, account_age, is_premium, voice_chat, device_type, executor, hwid, ip, place_id, job_id, game_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $key, $action,
        $data['userId'], $data['username'], $data['displayName'],
        $data['accountAge'], $data['isPremium'], $data['voiceChat'],
        $data['deviceType'], $data['executor'], $data['hwid'], $data['ip'],
        $data['placeId'], $data['jobId'], $data['gameName']
    ]);
}

function updateExpiredKeys($pdo) {
    $stmt = $pdo->prepare("UPDATE `keys` SET status = 'expired' WHERE expires_at < NOW() AND status IN ('used', 'unused')");
    $stmt->execute();
}

updateExpiredKeys($pdo);

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$key = sanitizeInput($input['key'] ?? '');
$userId = sanitizeInput($input['userId'] ?? '');
$username = sanitizeInput($input['username'] ?? '');
$displayName = sanitizeInput($input['displayName'] ?? '');
$accountAge = (int)($input['accountAge'] ?? 0);
$isPremium = (int)($input['isPremium'] ?? 0);
$voiceChat = (int)($input['voiceChat'] ?? 0);
$deviceType = sanitizeInput($input['deviceType'] ?? '');
$executor = sanitizeInput($input['executor'] ?? '');
$hwid = sanitizeInput($input['hwid'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'];
$reportedIp = isset($input['ip']) ? sanitizeInput($input['ip']) : '';
$placeId = sanitizeInput($input['placeId'] ?? '');
$jobId = sanitizeInput($input['jobId'] ?? '');
$gameName = sanitizeInput($input['gameName'] ?? '');
$scriptName = sanitizeInput($input['scriptName'] ?? '');

if (empty($key) || empty($userId) || empty($hwid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!validateHWID($hwid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!checkAndIncrementRateLimit($pdo, $ip, 5, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}

$userData = compact('userId', 'username', 'displayName', 'accountAge', 'isPremium', 'voiceChat', 'deviceType', 'executor', 'hwid', 'ip', 'placeId', 'jobId', 'gameName');

$banned = isBanned($pdo, $userId, $hwid, $ip);
if ($banned) {
    logAction($pdo, $key, 'banned_user', $userData);
    sendDiscordEmbed('Banned User Detected', "User **$userId** attempted execution", 0xFF5555);
    echo json_encode(['success' => false, 'message' => 'Access denied', 'banned' => true]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM `keys` WHERE code = ?");
$stmt->execute([$key]);
$row = $stmt->fetch();

if (!$row) {
    logAction($pdo, $key, 'invalid_key', $userData);
    sendDiscordEmbed('Invalid Key', "Key: `$key` | User: $userId", 0xFF5555);
    echo json_encode(['success' => false, 'message' => 'Invalid key']);
    exit;
}

if ($row['status'] === 'revoked') {
    logAction($pdo, $key, 'revoked_key', $userData);
    sendDiscordEmbed('Revoked Key', "Key attempted: `$key`", 0xFFAA00);
    echo json_encode(['success' => false, 'message' => 'Key has been revoked']);
    exit;
}

if ($row['status'] === 'expired' || ($row['expires_at'] && new DateTime($row['expires_at']) < new DateTime())) {
    $updateStatus = $pdo->prepare("UPDATE `keys` SET status = 'expired' WHERE code = ?");
    $updateStatus->execute([$key]);
    logAction($pdo, $key, 'expired_key', $userData);
    sendDiscordEmbed('Expired Key', "Key: `$key`", 0xFFAA00);
    echo json_encode(['success' => false, 'message' => 'Key has expired']);
    exit;
}

if ($row['is_global'] == 1) {
    if (!empty($row['allowed_script']) && $row['allowed_script'] !== $scriptName) {
        logAction($pdo, $key, 'wrong_script', $userData);
        sendDiscordEmbed('Wrong Script', "Key: `$key` | Script: $scriptName", 0xFFAA00);
        echo json_encode(['success' => false, 'message' => 'Key not valid for this script']);
        exit;
    }
    
    if ($row['status'] === 'unused') {
        $expires = $row['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+30 days'));
        $update = $pdo->prepare("UPDATE `keys` SET status = 'used', activated_at = NOW(), expires_at = ? WHERE code = ?");
        $update->execute([$expires, $key]);
        logAction($pdo, $key, 'first_use_global_script', $userData);
        sendDiscordEmbed('Global Key Activated', "Key: `$key` | User: $userId", 0x00FF88);
        echo json_encode(['success' => true, 'message' => 'Key activated', 'global' => true]);
    } else {
        logAction($pdo, $key, 'valid_use_global_script', $userData);
        sendDiscordEmbed('Global Key Used', "Key: `$key` | User: $userId", 0x88AAFF);
        echo json_encode(['success' => true, 'message' => 'Key valid', 'global' => true]);
    }
    exit;
}

if ($row['status'] === 'unused') {
    $expires = $row['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+30 days'));
    $update = $pdo->prepare("UPDATE `keys` SET status = 'used', user_id = ?, username = ?, display_name = ?, account_age = ?, is_premium = ?, voice_chat = ?, device_type = ?, executor = ?, hwid = ?, ip = ?, place_id = ?, job_id = ?, game_name = ?, activated_at = NOW(), expires_at = ? WHERE code = ?");
    $update->execute([$userId, $username, $displayName, $accountAge, $isPremium, $voiceChat, $deviceType, $executor, $hwid, $ip, $placeId, $jobId, $gameName, $expires, $key]);
    logAction($pdo, $key, 'first_use', $userData);
    sendDiscordEmbed('Key Activated', "Key: `$key` | User: $userId", 0x00FF88);
    echo json_encode(['success' => true, 'message' => 'Key activated successfully', 'firstUse' => true]);
} else {
    if ($row['user_id'] === $userId && $row['hwid'] === $hwid) {
        logAction($pdo, $key, 'valid_use', $userData);
        sendDiscordEmbed('Key Used', "Key: `$key` | User: $userId", 0x88AAFF);
        echo json_encode(['success' => true, 'message' => 'Key valid', 'firstUse' => false]);
    } else {
        logAction($pdo, $key, 'mismatch', $userData);
        sendDiscordEmbed('Unauthorized Use', "Key: `$key` | Attempted by: $userId | Original: {$row['user_id']}", 0xFFAA00);
        echo json_encode(['success' => false, 'message' => 'Key already in use']);
    }
}
