# 🔐 Roblox Key Management System

> A production-ready, secure key management system for Roblox script distribution with built-in protection against fraud and abuse.

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4+-green.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://www.mysql.com/)
[![Security](https://img.shields.io/badge/Security-High-brightgreen.svg)](#security-features)

---

## ✨ Features

🔑 **Key Management**
- Generate unlimited keys with custom expiration
- Global keys for multiple games or scripts
- Single-use or multi-use (per HWID) keys
- Revoke and manage keys in real-time

👤 **User Management**
- Ban users by User ID, HWID, or IP
- Temporary and permanent bans
- Complete ban history tracking

📊 **Admin Dashboard**
- Beautiful, real-time analytics
- Activity logs with 7-day charts
- One-click script activation/deactivation
- Statistics and key tracking

🛡️ **Security**
- Rate limiting (prevents brute force)
- CSRF protection
- SQL injection prevention
- Path traversal protection
- Input validation & sanitization

🔗 **Integration**
- Discord webhook notifications
- Script management & upload
- Executor detection
- HWID & IP tracking

---

## 🚀 Quick Start

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache with mod_rewrite
- cURL extension

### Installation

```bash
# Clone repository
git clone https://github.com/your-username/roblox-key-system.git
cd roblox-key-system

# Setup environment
cp .env.example .env

# Edit .env with your credentials
nano .env

# Import database schema
mysql -u root -p your_database < sql/schema.sql

# Set permissions
chmod 755 public logs scripts
chmod 644 public/*.php
```

### Configure .env

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

### Access Admin Panel
```
https://your-domain.com/public/login.php
```

---

## 📁 Project Structure

```
roblox-key-system/
├── public/              # Web-accessible files
│   ├── index.php       # Admin dashboard
│   ├── login.php       # Authentication
│   ├── api.php         # Admin API
│   ├── verify.php      # Key verification endpoint
│   ├── get_script.php  # Script delivery endpoint
│   └── .htaccess       # Security headers
├── config/
│   └── config.php      # Configuration (uses .env)
├── includes/
│   ├── security.php    # Security functions
│   └── rate_limit.php  # Rate limiting
├── sql/
│   └── schema.sql      # Database schema
├── scripts/            # Lua scripts directory
├── logs/               # Application logs
└── .env.example        # Environment template
```

---

## 🔌 API Usage

### Verify Key (Roblox Client)

```lua
local HttpService = game:GetService("HttpService")

local keyData = {
    key = "your_key_here",
    userId = game.Players.LocalPlayer.UserId,
    username = game.Players.LocalPlayer.Name,
    displayName = game.Players.LocalPlayer.DisplayName,
    accountAge = game.Players.LocalPlayer.AccountAge,
    isPremium = game.Players.LocalPlayer.MembershipType == Enum.MembershipType.Premium,
    voiceChat = false,
    deviceType = "Windows",
    executor = "Synapse",
    hwid = gethwid(),
    ip = game:HttpGet("https://api.ipify.org"),
    placeId = game.PlaceId,
    jobId = game.JobId,
    gameName = "My Game"
}

local response = game:HttpPost(
    "https://your-domain.com/public/verify.php",
    HttpService:JSONEncode(keyData)
)

local result = HttpService:JSONDecode(response)

if result.success then
    print("✅ Key valid! Execution allowed.")
else
    print("❌ " .. result.message)
end
```

### Get Script

```lua
local script = game:HttpGet(
    "https://your-domain.com/public/get_script.php?token=SECRET_TOKEN&file=my_script.lua"
)
loadstring(script)()
```

---

## 🔐 Security Features

| Feature | Description |
|---------|-------------|
| 🔑 Environment Variables | All credentials in `.env`, never in code |
| 🛡️ SQL Injection Protection | Prepared statements everywhere |
| 🚫 CSRF Protection | Token-based verification |
| 🔄 Rate Limiting | IP-based throttling (prevents abuse) |
| 🧹 Input Validation | Sanitization of all user inputs |
| 🛣️ Path Traversal Protection | Realpath validation for file access |
| 🔐 Secure Token Comparison | hash_equals() to prevent timing attacks |
| 📋 HTTPOnly Cookies | Session security headers |
| 🔍 HWID Tracking | Device fingerprinting |
| ⚠️ Executor Detection | Identifies script execution method |

---

## 📊 Database Tables

### `keys`
Stores all generated keys with full tracking

| Column | Purpose |
|--------|---------|
| code | Unique key identifier |
| status | unused, used, revoked, expired |
| user_id | Roblox User ID |
| hwid | Device fingerprint |
| expires_at | Expiration timestamp |
| activated_at | First use timestamp |

### `logs`
Complete activity audit trail

### `banned_users`
Bans by User ID, HWID, or IP

### `rate_limits`
IP-based request throttling

### `allowed_games`
Whitelisted game Place IDs

---

## ⚙️ Configuration

### Key Types

**Normal Keys**
- User-specific (bound to User ID + HWID)
- Multi-use per device
- Expires after set time

**Global Game Keys**
- Work across multiple games
- User-agnostic
- For universal access

**Global Script Keys**
- Work only for specific scripts
- Universal deployment
- Script-specific distribution

### Rate Limiting

```php
get_script.php    → 20 requests per 60 seconds
verify.php        → 5 requests per 60 seconds
```

---

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| Database connection error | Check `.env` credentials and MySQL status |
| Scripts not loading | Verify file permissions (755) and script is marked active |
| Rate limiting too strict | Adjust limits in `includes/rate_limit.php` |
| Discord notifications not working | Verify webhook URL and cURL is enabled |

---

## 📦 Deployment

### Apache Setup
```apache
# Set public/ as document root
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/your-site/public
    <Directory /var/www/your-site/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx Setup
```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/your-site/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

---

## 📋 Backup & Maintenance

### Database Backup
```bash
mysqldump -u user -p database > backup.sql
```

### Cleanup Old Logs
```php
cleanupOldLogs($pdo, 90); // Removes logs older than 90 days
```

---

## 📄 License

MIT License - see LICENSE file

---

## ⚖️ Disclaimer

This system is for legitimate script distribution to Roblox games. Users are responsible for:
- Compliance with Roblox Terms of Service
- Compliance with applicable laws
- Responsible script distribution
- Data privacy and protection

---

## 🤝 Support

Found a bug? Have a feature request?
- Open an issue on GitHub
- Check existing issues first
- Provide detailed reproduction steps

---

<div align="center">

Made with ❤️ by drakkiosauro

⭐ If this helped you, consider giving it a star!

</div>
