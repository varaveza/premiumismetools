# SPO Creator API - aaPanel Setup Guide

## 1. Upload Files
Upload all files to your aaPanel website directory (e.g., `/www/wwwroot/yourdomain.com/`)

## 2. Install Dependencies
```bash
cd /www/wwwroot/yourdomain.com/
pip3 install -r requirements.txt
```

## 3. Configure Environment
Create `.env` file with your configuration:

```bash
# API Security
API_KEY=your-secret-api-key-here
IP_WHITELIST=127.0.0.1,192.168.1.0/24,10.0.0.0/8

# Spotify Configuration
DOMAIN=example.com
PASSWORD=YourPassword123
NAME=Default Name

# Proxy Configuration (optional)
USE_PROXY=False
PROXY=user:pass@proxy.com:8080

# Captcha Service
CAPSOLVER_API_KEY=your-capsolver-api-key

# Server Configuration
PORT=5111
FLASK_ENV=production

# Process Configuration
PROCESS=1
CAPTCHA_MAX_ATTEMPTS=10
CAPS_WAIT_ATTEMPTS=30
CAPS_WAIT_INTERVAL=3
CLI_JSON=0
```

## 4. Set Permissions
```bash
chmod +x start.sh
chmod +x stop.sh
chmod 755 logs/
```

## 5. Start Service
```bash
./start.sh
```

## 6. aaPanel Process Management
In aaPanel, go to:
- **Software Store** → **Process Manager**
- Add new process:
  - **Name**: SPO Creator API
  - **Command**: `/www/wwwroot/yourdomain.com/start.sh`
  - **Working Directory**: `/www/wwwroot/yourdomain.com/`
  - **Auto Start**: Yes

## 7. API Endpoints

### Health Check
```
GET /
```

### Create Account
```
POST /api/create
Headers:
  X-API-Key: your-secret-api-key-here
  Content-Type: application/json

Body:
{
  "domain": "example.com",
  "password": "YourPassword123",
  "trial_link": "https://www.spotify.com/student/...verificationId=..."
}
```

### Get Status
```
GET /api/status
Headers:
  X-API-Key: your-secret-api-key-here
```

## 8. IP Whitelist Configuration

### Single IPs
```
IP_WHITELIST=192.168.1.100,10.0.0.50
```

### CIDR Ranges
```
IP_WHITELIST=192.168.1.0/24,10.0.0.0/8
```

### Mixed
```
IP_WHITELIST=127.0.0.1,192.168.1.0/24,10.0.0.50
```

## 9. Logs
- Access logs: `logs/access.log`
- Error logs: `logs/error.log`
- Gunicorn PID: `logs/gunicorn.pid`

## 10. Monitoring
Check if service is running:
```bash
ps aux | grep gunicorn
netstat -tlnp | grep 5111
```

## 11. Troubleshooting

### Service won't start
1. Check logs: `tail -f logs/error.log`
2. Verify .env configuration
3. Check port availability: `netstat -tlnp | grep 5111`

### API returns 403
1. Check IP whitelist in .env
2. Verify client IP is in whitelist

### API returns 401
1. Check API key in request headers
2. Verify API_KEY in .env matches request

### Account creation fails
1. Check CAPSOLVER_API_KEY
2. Verify domain and password
3. Check proxy settings if using proxy
