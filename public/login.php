<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/security.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } elseif (hash_equals(ADMIN_USER, $username) && hash_equals(ADMIN_PASS, $password)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_user'] = $username;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 20% 30%, #050505, #000000);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .glass {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            border: 1px solid rgba(255,255,255,0.1);
            padding: 40px;
            width: 400px;
        }
        h1 { font-size: 2.5rem; font-weight: 800; text-align: center; background: linear-gradient(135deg, #fff, #aaa); -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 10px; }
        .sub { text-align: center; color: #888; margin-bottom: 30px; }
        input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(0,0,0,0.6);
            border: 1px solid #2a2a2a;
            border-radius: 16px;
            color: white;
            font-size: 1rem;
            outline: none;
            margin-bottom: 16px;
        }
        input:focus { border-color: #9b59b6; }
        button {
            width: 100%;
            padding: 14px;
            background: white;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            color: black;
            cursor: pointer;
            transition: all 0.2s;
        }
        button:hover { background: #e0e0e0; transform: translateY(-2px); }
        .error { background: rgba(255,50,50,0.2); border-left: 3px solid #ff4444; padding: 12px; border-radius: 12px; margin-bottom: 20px; color: #ffaaaa; }
    </style>
</head>
<body>
    <div class="glass">
        <h1>ADMIN</h1>
        <div class="sub">Secure Access</div>
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" autofocus required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign In</button>
        </form>
    </div>
</body>
</html>
