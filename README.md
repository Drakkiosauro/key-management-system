# 🔐 Roblox Key Management System

> A **secure, scalable, and production-oriented** key management system designed for Roblox script distribution — built with a strong focus on protection against abuse, fraud, and unauthorized access.

---

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4+-blue?style=for-the-badge&logo=php">
  <img src="https://img.shields.io/badge/MySQL-5.7+-orange?style=for-the-badge&logo=mysql">
  <img src="https://img.shields.io/badge/Security-High-brightgreen?style=for-the-badge&logo=shield">
  <img src="https://img.shields.io/badge/License-MIT-lightgrey?style=for-the-badge">
</p>

---

## ✨ Overview

This project provides a **complete key management ecosystem** for Roblox scripts, including:

* Secure key validation
* Advanced user tracking
* Real-time admin control
* Built-in protection layers

Designed for developers who want **control, visibility, and security** when distributing scripts.

---

## 🚀 Features

### 🔑 Key Management

* Generate unlimited keys with expiration control
* Support for global and script-specific keys
* HWID-based locking system
* Real-time revocation and tracking

### 👤 User Control

* Ban system (User ID, HWID, IP)
* Temporary & permanent bans
* Full ban history tracking

### 📊 Admin Dashboard

* Clean and responsive interface
* Real-time analytics
* Activity logs with charts
* One-click script control

### 🛡️ Security Layer

* Rate limiting (anti-bruteforce)
* CSRF protection
* SQL injection prevention
* Path traversal protection
* Input validation & sanitization
* Secure token comparison (`hash_equals`)

### 🔗 Integrations

* Discord webhook alerts
* Script delivery system
* Executor detection
* HWID & IP tracking

---

## ⚠️ Important Security Notice

> 🚨 **Before using this in a real-world or production environment:**

This project is provided as a **strong foundation**, but:

* You should **review the entire codebase**
* Audit all **security-critical components**
* Replace or improve:

  * Authentication logic
  * Key storage methods
  * Rate limiting strategy
  * Input validation layers

> 🔐 **Security is not “set and forget”** — especially for systems handling keys and user data.

Failure to review and adapt the system may lead to:

* Key leaks
* Unauthorized access
* Abuse or bypasses

---

## ⚡ Quick Start

### Requirements

* PHP 7.4+
* MySQL 5.7+
* Apache (mod_rewrite enabled)
* cURL extension

---

### Installation

```bash
git clone https://github.com/your-username/roblox-key-system.git
cd roblox-key-system

cp .env.example .env
nano .env

mysql -u root -p your_database < sql/schema.sql

chmod 755 public logs scripts
chmod 644 public/*.php
```

---

### ⚙️ Environment Configuration

```ini
DB_HOST=localhost
DB_NAME=roblox_keys
DB_USER=app_user
DB_PASS=strong_password

ADMIN_USER=admin
ADMIN_PASS=your_admin_password
SECRET_TOKEN=generate_random_token_here

DISCORD_WEBHOOK=https://discordapp.com/api/webhooks/YOUR_ID/YOUR_TOKEN
```

---

### 🔐 Admin Panel

```
https://your-domain.com/public/login.php
```

---

## 📁 Project Structure

```
roblox-key-system/
├── public/
│   ├── index.php
│   ├── login.php
│   ├── api.php
│   ├── verify.php
│   ├── get_script.php
│   └── .htaccess
├── config/
│   └── config.php
├── includes/
│   ├── security.php
│   └── rate_limit.php
├── sql/
│   └── schema.sql
├── scripts/
├── logs/
└── .env.example
```

---

## 🔌 API Usage

### Verify Key

```lua
local response = game:HttpPost(
    "https://your-domain.com/public/verify.php",
    HttpService:JSONEncode(keyData)
)
```

---

### Get Script

```lua
local script = game:HttpGet(
    "https://your-domain.com/public/get_script.php?token=SECRET_TOKEN&file=my_script.lua"
)
loadstring(script)()
```

---

## 🔐 Security Breakdown

| Feature               | Purpose                    |
| --------------------- | -------------------------- |
| Environment Variables | Keeps secrets out of code  |
| Prepared Statements   | Prevents SQL injection     |
| Rate Limiting         | Blocks abuse attempts      |
| CSRF Tokens           | Prevents request forgery   |
| Input Sanitization    | Avoids malicious input     |
| Path Validation       | Stops file access exploits |
| Secure Comparison     | Prevents timing attacks    |

---

## 📊 Database Overview

### `keys`

Stores all key data and usage tracking

### `logs`

Full audit trail of activity

### `banned_users`

User restriction system

### `rate_limits`

Request throttling system

### `allowed_games`

Whitelist for game access

---

## ⚙️ Configuration Options

### Key Types

**Normal Keys**

* Bound to user + device
* Expirable

**Global Keys**

* Multi-game support
* User-independent

**Script Keys**

* Linked to specific scripts
* Controlled distribution

---

## 🚧 Deployment

### Apache

```apache
DocumentRoot /var/www/your-site/public
```

---

### Nginx

```nginx
root /var/www/your-site/public;
```

---

## 🧹 Maintenance

### Backup

```bash
mysqldump -u user -p database > backup.sql
```

---

### Cleanup Logs

```php
cleanupOldLogs($pdo, 90);
```

---

## ⚖️ Disclaimer

This project is intended for **legitimate use only**.

You are responsible for:

* Following Roblox Terms of Service
* Respecting privacy laws
* Using the system ethically

---

## 🤝 Contributing

* Open issues for bugs
* Suggest improvements
* Submit pull requests

---

<p align="center">
  Made with ❤️ by <strong>drakkiosauro</strong><br>
  ⭐ Star the repo if you found it useful!
</p>
