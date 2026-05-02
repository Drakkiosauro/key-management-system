<?php
function validateRequest($pdo) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("SELECT attempts, expires_at FROM rate_limits WHERE ip = ?");
    $stmt->execute([$ip]);
    $rate = $stmt->fetch();
    $now = new DateTime();
    
    if ($rate) {
        $expires = new DateTime($rate['expires_at']);
        if ($now < $expires) {
            if ($rate['attempts'] >= 10) {
                http_response_code(429);
                die(json_encode(['error' => 'Request limit exceeded']));
            }
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1 WHERE ip = ?");
            $stmt->execute([$ip]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip = ?");
            $stmt->execute([$ip]);
            $stmt = $pdo->prepare("INSERT INTO rate_limits (ip, attempts, expires_at) VALUES (?, 1, DATE_ADD(NOW(), INTERVAL 60 SECOND))");
            $stmt->execute([$ip]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip, attempts, expires_at) VALUES (?, 1, DATE_ADD(NOW(), INTERVAL 60 SECOND))");
        $stmt->execute([$ip]);
    }
}

function sanitizeInput($input) {
    $trimmed = is_string($input) ? trim($input) : $input;
    return htmlspecialchars(strip_tags($trimmed), ENT_QUOTES, 'UTF-8');
}

function sanitizeFileName($filename) {
    $filename = basename($filename);
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.lua$/', $filename)) {
        return false;
    }
    return $filename;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP);
}

function validateUserID($userID) {
    return preg_match('/^[0-9]{1,20}$/', $userID);
}

function validateHWID($hwid) {
    return preg_match('/^[a-f0-9\-]{32,}$/i', $hwid);
}
