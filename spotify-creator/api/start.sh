#!/bin/bash

# SPO Creator API Startup Script for aaPanel
# This script sets up and starts the Gunicorn server

# Set working directory
cd "$(dirname "$0")"

# Create logs directory if it doesn't exist
mkdir -p logs

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << EOF
# SPO Creator API Configuration

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
EOF
    echo "Please edit .env file with your configuration before starting the server."
    exit 1
fi

# Load environment variables
export $(cat .env | grep -v '^#' | xargs)

# Check if required environment variables are set
if [ -z "$API_KEY" ] || [ "$API_KEY" = "your-secret-api-key-here" ]; then
    echo "Error: Please set a proper API_KEY in .env file"
    exit 1
fi

if [ -z "$CAPSOLVER_API_KEY" ] || [ "$CAPSOLVER_API_KEY" = "your-capsolver-api-key" ]; then
    echo "Error: Please set CAPSOLVER_API_KEY in .env file"
    exit 1
fi

# Create necessary directories
mkdir -p logs
mkdir -p cookies
mkdir -p verification_html

# Initialize database
python3 -c "
import sqlite3
import os
from dotenv import load_dotenv

load_dotenv()
DB_PATH = os.path.join(os.path.dirname(__file__), 'spo_creator.db')

conn = sqlite3.connect(DB_PATH)
try:
    conn.execute('''
        CREATE TABLE IF NOT EXISTS web_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            email TEXT,
            is_student INTEGER DEFAULT 0,
            success INTEGER NOT NULL,
            created_at TEXT NOT NULL
        );
    ''')
    conn.commit()
    print('Database initialized successfully')
finally:
    conn.close()
"

# Start Gunicorn server
echo "Starting SPO Creator API server..."
echo "API Key: ${API_KEY:0:8}..."
echo "IP Whitelist: $IP_WHITELIST"
echo "Port: $PORT"
echo "Workers: $(python3 -c 'import multiprocessing; print(multiprocessing.cpu_count() * 2 + 1)')"

exec gunicorn --config gunicorn.conf.py api_app:APP
