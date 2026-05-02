<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/rate_limit.php';

header('Content-Type: text/plain');
header('Content-Disposition: inline');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');

$ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$token = sanitizeInput($_GET['token'] ?? '');
$script_name = isset($_GET['file']) ? sanitizeFileName($_GET['file']) : '';

if ($script_name === false) {
    http_response_code(400);
    die('Invalid filename');
}

if (!checkRateLimit($pdo, $ip, 20, 60)) {
    http_response_code(429);
    die('Request limit exceeded');
}

incrementRateLimit($pdo, $ip);

$is_roblox = false;

if (stripos($user_agent, 'Roblox') !== false) {
    $is_roblox = true;
}

$executors = ['Synapse', 'Krnl', 'ScriptWare', 'Fluxus', 'Electron', 'Oxygen', 'Valyse', 'Coco', 'Comet', 'Evo', 'Vega', 'Astolfo', 'Azul', 'JJSploit', 'Xeno', 'Velocity', 'Delta', 'SirHurt', 'Hydrogen', 'Codex', 'Arceus', 'Calamari', 'Nihon', 'Kiwi', 'Celery', 'Owl', 'Rogue', 'Mirage', 'Rbx', 'Exec', 'Lunar', 'Nova'];

foreach ($executors as $exec) {
    if (stripos($user_agent, $exec) !== false) {
        $is_roblox = true;
        break;
    }
}

if (!$is_roblox) {
    $stmt = $pdo->prepare("INSERT INTO logs (key_code, action, ip, game_name) VALUES (?, 'unauthorized_access', ?, ?)");
    $stmt->execute(['', $ip, substr($user_agent, 0, 255)]);
    http_response_code(403);
    die('Access denied');
}

if (!hash_equals(SECRET_TOKEN, $token)) {
    $stmt = $pdo->prepare("INSERT INTO logs (key_code, action, ip, game_name) VALUES (?, 'invalid_token', ?, ?)");
    $stmt->execute(['', $ip, substr($user_agent, 0, 255)]);
    http_response_code(403);
    die('Invalid token');
}

if (empty($script_name)) {
    http_response_code(400);
    die('Invalid filename');
}

$script_path = SCRIPT_PATH . $script_name;

if (realpath($script_path) === false || strpos(realpath($script_path), realpath(SCRIPT_PATH)) !== 0) {
    http_response_code(403);
    die('Access denied');
}

if (!file_exists($script_path)) {
    http_response_code(404);
    die('Script not found');
}

$status_file = SCRIPT_PATH . 'active_' . $script_name . '.txt';
if (!file_exists($status_file)) {
    http_response_code(403);
    die('Script is not active');
}

$content = file_get_contents($script_path);

if ($content === false) {
    http_response_code(500);
    die('Error reading script');
}

if (strlen($content) > 5 * 1024 * 1024) {
    http_response_code(413);
    die('Script too large');
}

$injected_code = "
local __axiss_script_name = \"" . addslashes($script_name) . "\"

local _axiss_original_getUserData = getUserData
if _axiss_original_getUserData then
    getUserData = function()
        local data = _axiss_original_getUserData()
        data.scriptName = __axiss_script_name
        return data
    end
end
";

echo $injected_code . $content;
