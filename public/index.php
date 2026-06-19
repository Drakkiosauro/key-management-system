<?php
require_once dirname(__DIR__) . '/config/config.php';

if (!isset($_SESSION['admin_logged'])) {
    header('Location: login.php');
    exit;
}

$totalKeys = $pdo->query("SELECT COUNT(*) FROM `keys`")->fetchColumn();
$usedKeys = $pdo->query("SELECT COUNT(*) FROM `keys` WHERE status = 'used'")->fetchColumn();
$revokedKeys = $pdo->query("SELECT COUNT(*) FROM `keys` WHERE status = 'revoked'")->fetchColumn();
$expiredKeys = $pdo->query("SELECT COUNT(*) FROM `keys` WHERE status = 'expired'")->fetchColumn();
$unusedKeys = $totalKeys - $usedKeys - $revokedKeys - $expiredKeys;

$daily = $pdo->query("SELECT DATE(activated_at) as date, COUNT(*) as count FROM `keys` WHERE activated_at IS NOT NULL AND activated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(activated_at) ORDER BY date ASC")->fetchAll();
$dates = [];
$counts = [];
foreach ($daily as $d) {
    $dates[] = $d['date'];
    $counts[] = $d['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="csrf-token" content="<?= escapeHtml($_SESSION['csrf_token'] ?? '') ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #000000;
            color: #ffffff;
            background-image: radial-gradient(circle at 10% 20%, rgba(255,255,255,0.02) 1%, transparent 1%);
            background-size: 40px 40px;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #111; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
        .logo h1 { font-size: 2rem; font-weight: 800; background: linear-gradient(135deg, #fff, #aaa); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .logout { background: rgba(255,255,255,0.05); padding: 10px 20px; border-radius: 40px; text-decoration: none; color: #ccc; font-weight: 500; }
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: rgba(15,15,15,0.7); backdrop-filter: blur(12px); border-radius: 24px; border: 1px solid rgba(255,255,255,0.08); padding: 20px; text-align: center; }
        .stat-number { font-size: 2.5rem; font-weight: 800; margin-bottom: 8px; }
        .stat-label { color: #aaa; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }
        .chart-container { background: rgba(15,15,15,0.7); backdrop-filter: blur(12px); border-radius: 24px; border: 1px solid rgba(255,255,255,0.08); padding: 20px; margin-bottom: 40px; }
        canvas { max-height: 300px; width: 100%; }
        .tabs { display: flex; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 32px; flex-wrap: wrap; }
        .tab-btn { background: transparent; border: none; padding: 12px 28px; font-size: 1rem; font-weight: 600; color: #888; cursor: pointer; border-radius: 40px; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .tab-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .tab-active { color: white; background: rgba(255,255,255,0.1); }
        .card { background: rgba(15,15,15,0.7); backdrop-filter: blur(12px); border-radius: 32px; border: 1px solid rgba(255,255,255,0.08); padding: 28px; }
        .card h2 { font-size: 1.6rem; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 14px 12px; font-weight: 500; color: #aaa; border-bottom: 1px solid rgba(255,255,255,0.1); }
        td { padding: 14px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; vertical-align: top; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 40px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-used { background: rgba(0,200,0,0.2); color: #0f0; border: 1px solid rgba(0,200,0,0.3); }
        .status-unused { background: rgba(100,100,100,0.2); color: #ccc; border: 1px solid rgba(255,255,255,0.2); }
        .status-revoked { background: rgba(200,0,0,0.2); color: #f44; border: 1px solid rgba(200,0,0,0.3); }
        .status-expired { background: rgba(255,150,0,0.2); color: #fa0; border: 1px solid rgba(255,150,0,0.3); }
        .generate-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .generate-box { background: rgba(0,0,0,0.5); border-radius: 24px; padding: 20px; }
        input, textarea, select { background: #0a0a0a; border: 1px solid #2a2a2a; border-radius: 16px; padding: 12px 16px; color: white; width: 100%; font-family: monospace; font-size: 0.9rem; outline: none; margin-bottom: 10px; }
        button { background: white; color: black; border: none; padding: 12px 24px; border-radius: 40px; font-weight: 600; cursor: pointer; margin-top: 12px; }
        .small-btn { padding: 4px 12px; font-size: 0.75rem; margin: 2px; display: inline-block; background: rgba(255,50,50,0.2); color: #ff8888; border: 1px solid rgba(255,50,50,0.3); }
        .break-all { word-break: break-all; }
        @media (max-width: 800px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .generate-grid { grid-template-columns: 1fr; } .container { padding: 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <h1>KEY SYSTEM</h1>
            </div>
            <a href="login.php?logout=1" class="logout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?= $totalKeys ?></div><div class="stat-label">Total Keys</div></div>
            <div class="stat-card"><div class="stat-number"><?= $usedKeys ?></div><div class="stat-label">Active</div></div>
            <div class="stat-card"><div class="stat-number"><?= $unusedKeys ?></div><div class="stat-label">Unused</div></div>
            <div class="stat-card"><div class="stat-number"><?= $revokedKeys ?></div><div class="stat-label">Revoked</div></div>
            <div class="stat-card"><div class="stat-number"><?= $expiredKeys ?></div><div class="stat-label">Expired</div></div>
        </div>

        <div class="chart-container">
            <h2 style="margin-bottom: 20px;"><i class="fas fa-chart-line"></i> Activations (Last 7 Days)</h2>
            <canvas id="activationsChart"></canvas>
        </div>

        <div class="tabs">
            <button class="tab-btn tab-active" data-tab="keys"><i class="fas fa-key"></i> Keys</button>
            <button class="tab-btn" data-tab="logs"><i class="fas fa-history"></i> Logs</button>
            <button class="tab-btn" data-tab="generate"><i class="fas fa-plus-circle"></i> Generate</button>
            <button class="tab-btn" data-tab="bans"><i class="fas fa-gavel"></i> Bans</button>
            <button class="tab-btn" data-tab="scripts"><i class="fas fa-file-code"></i> Scripts</button>
            <button class="tab-btn" data-tab="global_keys"><i class="fas fa-globe"></i> Global Keys</button>
        </div>

        <div id="keys" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-database"></i> Registered Keys</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Code</th><th>Status</th><th>User</th><th>HWID</th><th>IP</th><th>Executor</th><th>Expires</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="keys-list"><tr><td colspan="8">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="logs" class="tab-content" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-clipboard-list"></i> Activity Logs</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Key</th><th>Action</th><th>User</th><th>HWID</th><th>IP</th><th>Executor</th><th>Date</th></tr>
                        </thead>
                        <tbody id="logs-list"><tr><td colspan="7">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="generate" class="tab-content" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-magic"></i> Generate New Key</h2>
                <div class="generate-grid">
                    <div class="generate-box">
                        <label>Quantity (1-20)</label>
                        <input type="number" id="key-quantity" value="1" min="1" max="20">
                        <label>Days Valid</label>
                        <input type="number" id="key-days" value="30" min="1" max="365">
                        <button id="generate-btn"><i class="fas fa-cogs"></i> Generate</button>
                    </div>
                    <div class="generate-box">
                        <label>Generated Keys</label>
                        <textarea id="generated-keys" rows="5" readonly placeholder="Keys will appear here..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div id="bans" class="tab-content" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-ban"></i> Ban User</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <input type="text" id="ban-userid" placeholder="User ID">
                    <input type="text" id="ban-hwid" placeholder="HWID">
                    <input type="text" id="ban-ip" placeholder="IP">
                    <textarea id="ban-reason" placeholder="Reason" rows="2"></textarea>
                </div>
                <button id="ban-btn"><i class="fas fa-gavel"></i> Ban</button>
                <h2 style="margin-top: 30px; margin-bottom: 20px;"><i class="fas fa-users-slash"></i> Banned Users</h2>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>ID</th><th>User ID</th><th>HWID</th><th>IP</th><th>Reason</th><th>Banned At</th><th>Actions</th></tr></thead>
                        <tbody id="bans-list"><tr><td colspan="7">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="scripts" class="tab-content" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-file-code"></i> Manage Scripts</h2>
                <div class="generate-grid" style="margin-bottom: 30px;">
                    <div class="generate-box">
                        <label>Upload Script</label>
                        <input type="file" id="script-file" accept=".lua">
                        <label>Filename</label>
                        <input type="text" id="script-name" placeholder="my_script.lua">
                        <button id="upload-btn"><i class="fas fa-upload"></i> Upload</button>
                    </div>
                    <div class="generate-box">
                        <label>Info</label>
                        <div style="background: #0a0a0a; padding: 12px; border-radius: 16px; font-size: 0.8rem;">
                            <i class="fas fa-check-circle" style="color:#0f0;"></i> Active scripts can be loaded by executors
                        </div>
                    </div>
                </div>
                <h2 style="margin-bottom: 20px;"><i class="fas fa-list"></i> Available Scripts</h2>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Name</th><th>Size</th><th>Modified</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="scripts-list"><tr><td colspan="5">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="global_keys" class="tab-content" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-globe"></i> Global Keys</h2>
                <div class="generate-grid" style="margin-bottom: 30px;">
                    <div class="generate-box">
                        <label>Quantity (1-20)</label>
                        <input type="number" id="global-key-quantity" value="1" min="1" max="20">
                        <label>Days Valid</label>
                        <input type="number" id="global-key-days" value="30" min="1" max="365">
                        <label>Allowed Game</label>
                        <select id="global-key-game">
                            <option value="">Select a game</option>
                        </select>
                        <button id="generate-global-btn"><i class="fas fa-cogs"></i> Generate</button>
                    </div>
                    <div class="generate-box">
                        <label>Generated Keys</label>
                        <textarea id="generated-global-keys" rows="5" readonly placeholder="Keys will appear here..."></textarea>
                    </div>
                </div>
                
                <h2 style="margin-bottom: 20px;"><i class="fas fa-gamepad"></i> Allowed Games</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <input type="text" id="game-id" placeholder="Place ID">
                    <input type="text" id="game-name" placeholder="Game Name">
                </div>
                <button id="add-game-btn"><i class="fas fa-plus"></i> Add Game</button>
                <div class="table-wrapper" style="margin-top: 20px;">
                    <table>
                        <thead><tr><th>ID</th><th>Game ID</th><th>Name</th><th>Active</th><th>Actions</th></tr></thead>
                        <tbody id="games-list"><tr><td colspan="5">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function esc(str) {
            if (!str) return '-';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(str).replace(/[&<>"']/g, c => map[c]);
        }

        function trunc(str, len) {
            if (!str) return '-';
            return str.length > len ? esc(str.substring(0, len)) + '...' : esc(str);
        }

        async function apiCall(endpoint, data = null) {
            const options = { method: data ? 'POST' : 'GET', headers: { 'Content-Type': 'application/json' } };
            if (data) {
                data.csrf_token = CSRF_TOKEN;
                options.body = JSON.stringify(data);
            }
            const res = await fetch(endpoint, options);
            return await res.json();
        }

        async function loadKeys() {
            const data = await apiCall('api.php?action=keys');
            const tbody = document.getElementById('keys-list');
            if (data.success && data.keys.length) {
                tbody.innerHTML = data.keys.map(k => `
                    <tr>
                        <td style="font-family:monospace; font-size:0.8rem;">${esc(k.code)}</td>
                        <td><span class="status-badge status-${esc(k.status)}">${esc(k.status)}</span></td>
                        <td>${esc(k.username)}</td>
                        <td class="break-all" style="font-family:monospace; font-size:0.7rem; max-width:150px;">${trunc(k.hwid, 16)}</td>
                        <td>${esc(k.ip)}</td>
                        <td>${esc(k.executor)}</td>
                        <td>${k.expires_at ? esc(k.expires_at.substring(0, 10)) : '-'}</td>
                        <td>
                            ${k.status === 'used' ? `<button class="small-btn revoke-btn" data-key="${esc(k.code)}">Revoke</button>` : ''}
                            <button class="small-btn delete-btn" data-key="${esc(k.code)}">Delete</button>
                        </td>
                    </tr>
                `).join('');
                attachKeyEvents();
            } else {
                tbody.innerHTML = '<tr><td colspan="8">No keys found.</td></tr>';
            }
        }

        function attachKeyEvents() {
            document.querySelectorAll('.revoke-btn').forEach(btn => btn.addEventListener('click', async () => {
                const key = btn.getAttribute('data-key');
                if (confirm('Revoke this key?')) {
                    const res = await apiCall('api.php?action=revoke', { key });
                    if (res.success) { alert('Key revoked'); loadKeys(); loadLogs(); }
                    else alert('Error: ' + res.message);
                }
            }));
            document.querySelectorAll('.delete-btn').forEach(btn => btn.addEventListener('click', async () => {
                const key = btn.getAttribute('data-key');
                if (confirm('DELETE this key permanently?')) {
                    const res = await apiCall('api.php?action=delete_key', { key });
                    if (res.success) { alert('Key deleted'); loadKeys(); loadLogs(); }
                    else alert('Error: ' + res.message);
                }
            }));
        }

        async function loadLogs() {
            const data = await apiCall('api.php?action=logs');
            const tbody = document.getElementById('logs-list');
            if (data.success && data.logs.length) {
                tbody.innerHTML = data.logs.map(l => `
                    <tr>
                        <td style="font-family:monospace; font-size:0.8rem;">${esc(l.key_code)}</td>
                        <td><span class="status-badge status-used">${esc(l.action)}</span></td>
                        <td>${esc(l.username)}</td>
                        <td class="break-all" style="font-family:monospace; font-size:0.7rem; max-width:150px;">${trunc(l.hwid, 16)}</td>
                        <td>${esc(l.ip)}</td>
                        <td>${esc(l.executor)}</td>
                        <td>${l.timestamp ? new Date(l.timestamp).toLocaleString() : '-'}</td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="7">No logs found.</td></tr>';
            }
        }

        document.getElementById('generate-btn').addEventListener('click', async () => {
            const quantity = parseInt(document.getElementById('key-quantity').value);
            const days = parseInt(document.getElementById('key-days').value);
            const data = await apiCall('api.php?action=generate', { quantity, days });
            if (data.success) {
                document.getElementById('generated-keys').value = data.keys.join('\n');
                alert(quantity + ' key(s) generated!');
                loadKeys();
            } else alert('Error: ' + data.message);
        });

        document.getElementById('generate-global-btn').addEventListener('click', async () => {
            const quantity = parseInt(document.getElementById('global-key-quantity').value);
            const days = parseInt(document.getElementById('global-key-days').value);
            const game_id = document.getElementById('global-key-game').value;
            if (!game_id) { alert('Select a game'); return; }
            const data = await apiCall('api.php?action=generate_global_key', { quantity, days, game_id });
            if (data.success) {
                document.getElementById('generated-global-keys').value = data.keys.join('\n');
                alert(quantity + ' global key(s) generated!');
                loadGlobalKeys();
            } else alert('Error: ' + data.message);
        });

        async function loadBans() {
            const data = await apiCall('api.php?action=bans');
            const tbody = document.getElementById('bans-list');
            if (data.success && data.bans.length) {
                tbody.innerHTML = data.bans.map(b => `
                    <tr>
                        <td>${esc(b.id)}</td>
                        <td>${esc(b.user_id)}</td>
                        <td class="break-all" style="font-family:monospace; font-size:0.7rem; max-width:150px;">${trunc(b.hwid, 16)}</td>
                        <td>${esc(b.ip)}</td>
                        <td>${esc(b.reason)}</td>
                        <td>${b.banned_at ? new Date(b.banned_at).toLocaleDateString() : '-'}</td>
                        <td><button class="small-btn unban-btn" data-id="${esc(b.id)}">Unban</button></td>
                    </tr>
                `).join('');
                document.querySelectorAll('.unban-btn').forEach(btn => btn.addEventListener('click', async () => {
                    const id = btn.getAttribute('data-id');
                    if (confirm('Unban this user?')) {
                        const res = await apiCall('api.php?action=unban', { id });
                        if (res.success) { alert('User unbanned'); loadBans(); }
                        else alert('Error: ' + res.message);
                    }
                }));
            } else {
                tbody.innerHTML = '<tr><td colspan="7">No bans found.</td></tr>';
            }
        }

        document.getElementById('ban-btn').addEventListener('click', async () => {
            const userId = document.getElementById('ban-userid').value.trim();
            const hwid = document.getElementById('ban-hwid').value.trim();
            const ip = document.getElementById('ban-ip').value.trim();
            const reason = document.getElementById('ban-reason').value.trim();
            if (!userId && !hwid && !ip) { alert('Fill at least one field'); return; }
            const res = await apiCall('api.php?action=ban', { userId, hwid, ip, reason });
            if (res.success) {
                alert('User banned');
                document.getElementById('ban-userid').value = '';
                document.getElementById('ban-hwid').value = '';
                document.getElementById('ban-ip').value = '';
                document.getElementById('ban-reason').value = '';
                loadBans();
            } else alert('Error: ' + res.message);
        });

        async function loadScripts() {
            const data = await apiCall('api.php?action=list_scripts');
            const tbody = document.getElementById('scripts-list');
            if (data.success && data.scripts.length) {
                tbody.innerHTML = data.scripts.map(s => {
                    const activeText = s.active ? 'Deactivate' : 'Activate';
                    return `
                        <tr>
                            <td style="font-family:monospace;">${esc(s.name)}</td>
                            <td>${(s.size / 1024).toFixed(2)} KB</td>
                            <td>${new Date(s.modified * 1000).toLocaleDateString()}</td>
                            <td><span class="status-badge ${s.active ? 'status-used' : 'status-unused'}">${s.active ? 'ACTIVE' : 'INACTIVE'}</span></td>
                            <td>
                                <button class="small-btn" data-file="${esc(s.name)}" onclick="toggleScript(this)">${activeText}</button>
                                <button class="small-btn" data-file="${esc(s.name)}" onclick="deleteScript(this)">Delete</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5">No scripts found.</td></tr>';
            }
        }

        function toggleScript(btn) {
            const file = btn.getAttribute('data-file');
            apiCall('api.php?action=toggle_script', { file }).then(res => {
                if (res.success) { alert(res.active ? 'Script activated!' : 'Script deactivated!'); loadScripts(); }
                else alert('Error: ' + res.message);
            });
        }

        function deleteScript(btn) {
            const file = btn.getAttribute('data-file');
            if (confirm('Delete ' + file + '?')) {
                apiCall('api.php?action=delete_script', { file }).then(res => {
                    if (res.success) { alert('Script deleted'); loadScripts(); }
                    else alert('Error: ' + res.message);
                });
            }
        }

        document.getElementById('upload-btn').addEventListener('click', async () => {
            const fileInput = document.getElementById('script-file');
            const fileName = document.getElementById('script-name').value.trim();
            if (!fileInput.files.length) { alert('Select a file'); return; }
            if (!fileName) { alert('Enter a filename'); return; }
            if (!fileName.endsWith('.lua')) { alert('Filename must end with .lua'); return; }
            const reader = new FileReader();
            reader.onload = async (e) => {
                const content = e.target.result;
                const res = await apiCall('api.php?action=upload_script', { name: fileName, content });
                if (res.success) { alert('Script uploaded!'); loadScripts(); }
                else alert('Error: ' + res.message);
            };
            reader.readAsText(fileInput.files[0]);
        });

        async function loadAllowedGames() {
            const data = await apiCall('api.php?action=list_allowed_games');
            const tbody = document.getElementById('games-list');
            const select = document.getElementById('global-key-game');
            if (data.success && data.games.length) {
                tbody.innerHTML = data.games.map(g => `
                    <tr>
                        <td>${esc(g.id)}</td>
                        <td>${esc(g.game_id)}</td>
                        <td>${esc(g.game_name)}</td>
                        <td>${g.active ? 'Yes' : 'No'}</td>
                        <td><button class="small-btn" data-id="${esc(g.id)}" onclick="removeGame(this)">Remove</button></td>
                    </tr>
                `).join('');
                select.innerHTML = '<option value="">Select a game</option>' + data.games.map(g => `<option value="${esc(g.game_id)}">${esc(g.game_name)}</option>`).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5">No games added.</td></tr>';
            }
        }

        function removeGame(btn) {
            const id = btn.getAttribute('data-id');
            if (confirm('Remove this game?')) {
                apiCall('api.php?action=remove_allowed_game', { id }).then(res => {
                    if (res.success) { alert('Game removed'); loadAllowedGames(); }
                    else alert('Error: ' + res.message);
                });
            }
        }

        document.getElementById('add-game-btn').addEventListener('click', async () => {
            const game_id = document.getElementById('game-id').value.trim();
            const game_name = document.getElementById('game-name').value.trim();
            if (!game_id || !game_name) { alert('Fill all fields'); return; }
            const res = await apiCall('api.php?action=add_allowed_game', { game_id, game_name });
            if (res.success) {
                alert('Game added!');
                document.getElementById('game-id').value = '';
                document.getElementById('game-name').value = '';
                loadAllowedGames();
            } else alert('Error: ' + res.message);
        });

        const tabs = document.querySelectorAll('.tab-btn');
        const contents = { keys: document.getElementById('keys'), logs: document.getElementById('logs'), generate: document.getElementById('generate'), bans: document.getElementById('bans'), scripts: document.getElementById('scripts'), global_keys: document.getElementById('global_keys') };
        tabs.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                Object.values(contents).forEach(c => c.style.display = 'none');
                contents[tabId].style.display = 'block';
                tabs.forEach(b => b.classList.remove('tab-active'));
                btn.classList.add('tab-active');
                if (tabId === 'keys') loadKeys();
                if (tabId === 'logs') loadLogs();
                if (tabId === 'bans') loadBans();
                if (tabId === 'scripts') loadScripts();
                if (tabId === 'global_keys') { loadAllowedGames(); }
            });
        });

        const ctx = document.getElementById('activationsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [{ label: 'Activations', data: <?= json_encode($counts) ?>, borderColor: 'white', backgroundColor: 'rgba(255,255,255,0.1)', tension: 0.2, fill: true }]
            },
            options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' } }, x: { grid: { color: 'rgba(255,255,255,0.1)' } } }, plugins: { legend: { labels: { color: 'white' } } } }
        });

        loadKeys();
        loadAllowedGames();
    </script>
</body>
</html>
