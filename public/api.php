<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/security.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$rawBody = file_get_contents('php://input');
$input = $rawBody ? json_decode($rawBody, true) : [];

if (!is_array($input)) {
    $input = [];
}

$csrfMutatingActions = ['generate', 'generate_global_key', 'generate_global_script_key', 'revoke', 'delete_key', 'ban', 'unban', 'upload_script', 'delete_script', 'toggle_script', 'rename_script', 'add_allowed_game', 'remove_allowed_game'];

if (in_array($action, $csrfMutatingActions)) {
    $csrfToken = $input['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

if ($action === 'keys') {
    $stmt = $pdo->query("SELECT id, code, status, user_id, username, hwid, ip, executor, game_name, expires_at, is_global, allowed_game_id, allowed_script FROM `keys` ORDER BY id DESC LIMIT 500");
    $keys = $stmt->fetchAll();
    echo json_encode(['success' => true, 'keys' => $keys]);
} elseif ($action === 'logs') {
    $stmt = $pdo->query("SELECT id, key_code, action, user_id, username, hwid, ip, executor, game_name, timestamp FROM logs ORDER BY id DESC LIMIT 500");
    $logs = $stmt->fetchAll();
    echo json_encode(['success' => true, 'logs' => $logs]);
} elseif ($action === 'generate') {
    $quantity = (int)($input['quantity'] ?? 1);
    $quantity = min(max($quantity, 1), 20);
    $days = (int)($input['days'] ?? 30);
    $days = min(max($days, 1), 365);
    
    $keys = [];
    $stmt = $pdo->prepare("INSERT INTO `keys` (code, expires_at) VALUES (?, DATE_ADD(NOW(), INTERVAL ? DAY))");
    
    try {
        for ($i = 0; $i < $quantity; $i++) {
            $code = bin2hex(random_bytes(16));
            $stmt->execute([$code, $days]);
            $keys[] = $code;
        }
        echo json_encode(['success' => true, 'keys' => $keys]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error generating keys']);
    }
} elseif ($action === 'generate_global_key') {
    $quantity = (int)($input['quantity'] ?? 1);
    $quantity = min(max($quantity, 1), 20);
    $days = (int)($input['days'] ?? 30);
    $days = min(max($days, 1), 365);
    $game_id = sanitizeInput($input['game_id'] ?? '');
    
    if (empty($game_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid game']);
        exit;
    }
    
    $keys = [];
    $stmt = $pdo->prepare("INSERT INTO `keys` (code, is_global, allowed_game_id, expires_at) VALUES (?, 1, ?, DATE_ADD(NOW(), INTERVAL ? DAY))");
    
    try {
        for ($i = 0; $i < $quantity; $i++) {
            $code = bin2hex(random_bytes(16));
            $stmt->execute([$code, $game_id, $days]);
            $keys[] = $code;
        }
        echo json_encode(['success' => true, 'keys' => $keys]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error generating keys']);
    }
} elseif ($action === 'generate_global_script_key') {
    $quantity = (int)($input['quantity'] ?? 1);
    $quantity = min(max($quantity, 1), 20);
    $days = (int)($input['days'] ?? 30);
    $days = min(max($days, 1), 365);
    $script_name = sanitizeInput($input['script_name'] ?? '');
    
    if (empty($script_name)) {
        echo json_encode(['success' => false, 'message' => 'Invalid script']);
        exit;
    }
    
    $keys = [];
    $stmt = $pdo->prepare("INSERT INTO `keys` (code, is_global, allowed_script, expires_at) VALUES (?, 1, ?, DATE_ADD(NOW(), INTERVAL ? DAY))");
    
    try {
        for ($i = 0; $i < $quantity; $i++) {
            $code = bin2hex(random_bytes(16));
            $stmt->execute([$code, $script_name, $days]);
            $keys[] = $code;
        }
        echo json_encode(['success' => true, 'keys' => $keys]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error generating keys']);
    }
} elseif ($action === 'revoke') {
    $key = sanitizeInput($input['key'] ?? '');
    
    if (empty($key)) {
        echo json_encode(['success' => false, 'message' => 'Invalid key']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE `keys` SET status = 'revoked' WHERE code = ? AND status = 'used'");
    $stmt->execute([$key]);
    echo json_encode(['success' => true]);
} elseif ($action === 'delete_key') {
    $key = sanitizeInput($input['key'] ?? '');
    
    if (empty($key)) {
        echo json_encode(['success' => false, 'message' => 'Invalid key']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM `keys` WHERE code = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Key not found']);
        exit;
    }
    
    $logStmt = $pdo->prepare("INSERT INTO logs (key_code, action) VALUES (?, 'key_deleted')");
    $logStmt->execute([$key]);
    
    $delStmt = $pdo->prepare("DELETE FROM `keys` WHERE code = ?");
    $delStmt->execute([$key]);
    
    echo json_encode(['success' => true]);
} elseif ($action === 'bans') {
    $stmt = $pdo->query("SELECT id, user_id, hwid, ip, reason, banned_at FROM banned_users WHERE unbanned_at IS NULL ORDER BY id DESC LIMIT 500");
    $bans = $stmt->fetchAll();
    echo json_encode(['success' => true, 'bans' => $bans]);
} elseif ($action === 'ban') {
    $userId = sanitizeInput($input['userId'] ?? '');
    $hwid = sanitizeInput($input['hwid'] ?? '');
    $ip = sanitizeInput($input['ip'] ?? '');
    $reason = sanitizeInput($input['reason'] ?? '');
    
    if (empty($userId) && empty($hwid) && empty($ip)) {
        echo json_encode(['success' => false, 'message' => 'At least one identifier required']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO banned_users (user_id, hwid, ip, reason) VALUES (?, ?, ?, ?)");
    $stmt->execute([empty($userId) ? null : $userId, empty($hwid) ? null : $hwid, empty($ip) ? null : $ip, empty($reason) ? null : $reason]);
    echo json_encode(['success' => true]);
} elseif ($action === 'unban') {
    $id = (int)($input['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE banned_users SET unbanned_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} elseif ($action === 'list_scripts') {
    $scripts = [];
    $scriptPath = SCRIPT_PATH;
    
    if (!is_dir($scriptPath)) {
        echo json_encode(['success' => true, 'scripts' => []]);
        exit;
    }
    
    foreach (glob($scriptPath . '*.lua') as $file) {
        $filename = basename($file);
        if (sanitizeFileName($filename) !== false) {
            $statusFile = $scriptPath . 'active_' . $filename . '.txt';
            $scripts[] = [
                'name' => $filename,
                'size' => filesize($file),
                'modified' => filemtime($file),
                'active' => file_exists($statusFile)
            ];
        }
    }
    
    echo json_encode(['success' => true, 'scripts' => $scripts]);
} elseif ($action === 'upload_script') {
    $name = sanitizeFileName($input['name'] ?? '');
    $content = $input['content'] ?? '';
    
    if ($name === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid filename']);
        exit;
    }
    
    if (strlen($content) > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large']);
        exit;
    }
    
    $scriptPath = SCRIPT_PATH . $name;
    $dir = dirname($scriptPath);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (file_put_contents($scriptPath, $content) === false) {
        echo json_encode(['success' => false, 'message' => 'Error saving file']);
        exit;
    }
    
    echo json_encode(['success' => true]);
} elseif ($action === 'delete_script') {
    $name = sanitizeFileName($input['file'] ?? '');
    
    if ($name === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid filename']);
        exit;
    }
    
    $scriptPath = SCRIPT_PATH . $name;
    $statusFile = SCRIPT_PATH . 'active_' . $name . '.txt';
    
    if (realpath($scriptPath) === false || strpos(realpath($scriptPath), realpath(SCRIPT_PATH)) !== 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    if (file_exists($statusFile)) {
        unlink($statusFile);
    }
    
    if (file_exists($scriptPath)) {
        unlink($scriptPath);
    }
    
    echo json_encode(['success' => true]);
} elseif ($action === 'toggle_script') {
    $name = sanitizeFileName($input['file'] ?? '');
    
    if ($name === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid filename']);
        exit;
    }
    
    $scriptPath = SCRIPT_PATH . $name;
    
    if (!file_exists($scriptPath)) {
        echo json_encode(['success' => false, 'message' => 'Script not found']);
        exit;
    }
    
    $realPath = realpath($scriptPath);
    $realBase = realpath(SCRIPT_PATH);
    if ($realPath === false || $realBase === false || strpos($realPath, $realBase) !== 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $statusFile = SCRIPT_PATH . 'active_' . $name . '.txt';
    
    if (file_exists($statusFile)) {
        unlink($statusFile);
        echo json_encode(['success' => true, 'active' => false]);
    } else {
        file_put_contents($statusFile, '1');
        echo json_encode(['success' => true, 'active' => true]);
    }
} elseif ($action === 'get_script_content') {
    $name = sanitizeFileName($input['file'] ?? '');
    
    if ($name === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid filename']);
        exit;
    }
    
    $scriptPath = SCRIPT_PATH . $name;
    
    if (!file_exists($scriptPath)) {
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit;
    }
    
    $realPath = realpath($scriptPath);
    $realBase = realpath(SCRIPT_PATH);
    if ($realPath === false || $realBase === false || strpos($realPath, $realBase) !== 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $content = file_get_contents($scriptPath);
    
    if ($content === false) {
        echo json_encode(['success' => false, 'message' => 'Error reading file']);
        exit;
    }
    
    echo json_encode(['success' => true, 'content' => $content]);
} elseif ($action === 'rename_script') {
    $old_name = sanitizeFileName($input['old_name'] ?? '');
    $new_name = sanitizeFileName($input['new_name'] ?? '');
    
    if ($old_name === false || $new_name === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid filename']);
        exit;
    }
    
    $old_path = SCRIPT_PATH . $old_name;
    $new_path = SCRIPT_PATH . $new_name;
    
    if (!file_exists($old_path)) {
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit;
    }
    
    $realOld = realpath($old_path);
    $realNew = realpath(dirname($new_path)) . '/' . basename($new_path);
    $realBase = realpath(SCRIPT_PATH);
    if ($realOld === false || $realBase === false || strpos($realOld, $realBase) !== 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    if (file_exists($new_path)) {
        echo json_encode(['success' => false, 'message' => 'Destination exists']);
        exit;
    }
    
    if (rename($old_path, $new_path)) {
        $old_status = SCRIPT_PATH . 'active_' . $old_name . '.txt';
        $new_status = SCRIPT_PATH . 'active_' . $new_name . '.txt';
        if (file_exists($old_status)) {
            rename($old_status, $new_status);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rename failed']);
    }
} elseif ($action === 'add_allowed_game') {
    $game_id = sanitizeInput($input['game_id'] ?? '');
    $game_name = sanitizeInput($input['game_name'] ?? '');
    
    if (empty($game_id) || empty($game_name)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO allowed_games (game_id, game_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE game_name = ?, active = 1");
    $stmt->execute([$game_id, $game_name, $game_name]);
    echo json_encode(['success' => true]);
} elseif ($action === 'list_allowed_games') {
    $stmt = $pdo->query("SELECT id, game_id, game_name, active FROM allowed_games ORDER BY id DESC LIMIT 500");
    $games = $stmt->fetchAll();
    echo json_encode(['success' => true, 'games' => $games]);
} elseif ($action === 'remove_allowed_game') {
    $id = (int)($input['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM allowed_games WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
