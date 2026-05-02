<?php
function checkRateLimit($pdo, $ip, $limit = 5, $timeWindow = 60) {
    $stmt = $pdo->prepare("SELECT attempts FROM rate_limits WHERE ip = ? AND expires_at > NOW()");
    $stmt->execute([$ip]);
    $row = $stmt->fetch();
    
    if ($row && $row['attempts'] >= $limit) {
        return false;
    }
    return true;
}

function incrementRateLimit($pdo, $ip) {
    $stmt = $pdo->prepare("INSERT INTO rate_limits (ip, attempts, expires_at) VALUES (?, 1, DATE_ADD(NOW(), INTERVAL 60 SECOND)) ON DUPLICATE KEY UPDATE attempts = attempts + 1, expires_at = DATE_ADD(NOW(), INTERVAL 60 SECOND)");
    $stmt->execute([$ip]);
}

function cleanupExpiredRateLimits($pdo) {
    $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE expires_at < NOW()");
    $stmt->execute();
}

function cleanupOldLogs($pdo, $daysOld = 90) {
    $stmt = $pdo->prepare("DELETE FROM logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->execute([$daysOld]);
}
