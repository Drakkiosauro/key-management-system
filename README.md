# Roblox Key Management System

> A **secure, production-ready** key management system for Roblox script distribution, with built-in protection against abuse, fraud, and unauthorized access.

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4+-blue?style=for-the-badge&logo=php">
  <img src="https://img.shields.io/badge/MySQL-5.7+-orange?style=for-the-badge&logo=mysql">
  <img src="https://img.shields.io/badge/Security-Critical-brightgreen?style=for-the-badge&logo=shield">
  <img src="https://img.shields.io/badge/License-MIT-lightgrey?style=for-the-badge">
</p>

---

## Security Changelog — June 2026

### Critical Fixes Applied

| Vulnerability | Severity | Fix |
| :--- | :---: | :--- |
| `SECRET_TOKEN` exposed via `list_scripts` API  | 🔴 Critical | Token removed from JSON response. Admin panel now uses session-authenticated endpoints. |
| Client-controlled IP in `verify.php` | 🔴 Critical | `$ip` now exclusively comes from `$_SERVER['REMOTE_ADDR']`. Client-provided IP is logged separately as `reportedIp`. |
| No CSRF protection anywhere | 🔴 Critical | `verifyCSRFToken()` enforced on all mutating API actions. Token injected via `<meta>` tag and auto-sent on every POST request. |
| Stored XSS in admin panel | 🔴 Critical | `esc()` JS function escapes `&<>"'` before DOM insertion. All template literals sanitized. |

### Medium Fixes Applied

| Vulnerability | Severity | Fix |
| :--- | :---: | :--- |
| Empty `SECRET_TOKEN` when env var unset | 🟠 Medium | Auto-generated 64-char token via `random_bytes(32)` if env var is missing. |
| Race condition in rate limiting | 🟠 Medium | `checkAndIncrementRateLimit()` created as atomic MySQL transaction, eliminating TOCTOU. |
| `sanitizeInput()` corrupting stored data | 🟡 Low | Removed `strip_tags()` and `htmlspecialchars()`. New `escapeHtml()` for HTML output only. |
| Missing path traversal checks on script ops | 🟡 Low | `realpath()` validation added to `toggle_script`, `get_script_content`, and `rename_script`. |

---

## Features

### Key Management
- Unlimited key generation with expiration control
- Global and script-specific keys
- HWID-based locking
- Real-time revocation and tracking

### User Control
- Ban system (User ID, HWID, IP)
- Temporary & permanent bans
- Full ban history

### Admin Dashboard
- Clean dark-mode interface
- Real-time analytics with Chart.js
- Activity logs
- Script management (upload, toggle, delete)

### Integrations
- Discord webhook alerts
- Script delivery system
- Roblox executor detection
- HWID & IP tracking

---

## Quick Start

### Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache (mod_rewrite) or Nginx
- PHP cURL extension
- PHP PDO MySQL extension

### Installation

```bash
git clone https://github.com/your-username/roblox-key-system.git
cd roblox-key-system

# Configure environment variables
cp .env.example .env
nano .env

# Import database schema
mysql -u root -p your_database < sql/schema.sql

# Set permissions
chmod 755 public logs scripts
chmod 644 public/*.php includes/*.php config/*.php
```

### Environment Configuration

```ini
DB_HOST=localhost
DB_NAME=roblox_keys
DB_USER=app_user
DB_PASS=strong_password

ADMIN_USER=admin
ADMIN_PASS=your_admin_password

# Optional: if not set, a random 64-char token is auto-generated
SECRET_TOKEN=your_random_token_here

# Discord webhook for alerts (optional)
DISCORD_WEBHOOK=https://discord.com/api/webhooks/YOUR_ID/YOUR_TOKEN
```

### Admin Panel

```
https://your-domain.com/public/login.php
```

---

## Project Structure

```
key-management-system/
├── public/
│   ├── index.php              # Admin dashboard
│   ├── login.php              # Login page
│   ├── api.php                # Admin API
│   ├── verify.php             # Key verification (executors)
│   └── get_script.php         # Script delivery (executors)
├── config/
│   └── config.php             # Bootstrap & configuration
├── includes/
│   ├── security.php           # Security functions
│   └── rate_limit.php         # Rate limiting
├── sql/
│   └── schema.sql             # MySQL schema
├── scripts/                   # .lua script files
├── logs/                      # PHP error logs
├── .env.example               # Environment template
├── LICENSE
└── README.md
```

---

## API Usage

### Verify Key (from Executor)

```lua
local response = game:HttpPost(
    "https://your-domain.com/public/verify.php",
    HttpService:JSONEncode({
        key = "YOUR_KEY",
        userId = "123456789",
        username = "User",
        displayName = "User",
        hwid = "YOUR_HWID"
    })
)
```

### Get Script (from Executor)

```lua
local script = game:HttpGet(
    "https://your-domain.com/public/get_script.php?token=YOUR_TOKEN&file=my_script.lua"
)
loadstring(script)()
```

---

## Security Stack

| Layer | Protection |
| :--- | :--- |
| Environment | All secrets via env vars, zero hardcoded credentials |
| Database | PDO prepared statements with `EMULATE_PREPARES=false` |
| Session | `use_strict_mode`, HttpOnly cookies, SameSite Strict |
| Rate Limit | Atomic MySQL transactions prevent race conditions |
| CSRF | Per-session token validated on every mutating action |
| XSS | HTML escaping in JS (`esc()`) + PHP (`escapeHtml()`) |
| Files | `sanitizeFileName()` regex + `realpath()` path validation |
| Timing | `hash_equals()` for all token/comparison operations |
| Input | `sanitizeInput()` trim + prepared statements |
| Audit | Full logging of all suspicious actions |

---

## Database Overview

### `keys`
Key data, status, HWID/User binding, and expiration control.

### `logs`
Full audit trail of all system activity.

### `banned_users`
User restriction system by ID, HWID, or IP.

### `rate_limits`
Request throttling with automatic expiration.

### `allowed_games`
Game whitelist for global keys.

---

## Key Types

| Type | Behavior |
| :--- | :--- |
| **Normal** | Bound to the first user + HWID that activates it |
| **Global** | Multi-game, linked to a specific `game_id` |
| **Script** | Linked to a specific `.lua` script |

---

## Disclaimer

This project is intended **for legitimate use only**. You are responsible for:
- Complying with Roblox Terms of Service
- Respecting privacy laws (GDPR, LGPD, etc.)
- Using the system ethically

---

<p align="center">
  Made by <strong>drakkiosauro</strong><br>
  ⭐ Star the repo if you found it useful!
</p>
