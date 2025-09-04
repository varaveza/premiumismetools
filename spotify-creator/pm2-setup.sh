#!/bin/bash

# PM2 Setup Script for SPO Creator API
# Run this script on VPS to setup PM2 for the API

echo "🚀 Setting up PM2 for SPO Creator API..."

# Install PM2 globally if not already installed
if ! command -v pm2 &> /dev/null; then
    echo "📦 Installing PM2..."
    npm install -g pm2
else
    echo "✅ PM2 already installed"
fi

# Navigate to API directory
cd /var/www/premiumisme.co/api

# Create logs directory
mkdir -p logs

# Stop any existing PM2 processes
echo "🛑 Stopping existing processes..."
pm2 stop spo-creator-api 2>/dev/null || true
pm2 delete spo-creator-api 2>/dev/null || true

# Install Python dependencies
echo "📦 Installing Python dependencies..."
pip3 install -r requirements.txt

# Start API with PM2
echo "🚀 Starting API with PM2..."
pm2 start /var/www/premiumisme.co/html/tools/spotify-creator/ecosystem.config.js

# Save PM2 configuration
echo "💾 Saving PM2 configuration..."
pm2 save

# Setup PM2 startup script
echo "⚡ Setting up PM2 startup..."
pm2 startup

echo "✅ PM2 setup completed!"
echo ""
echo "📊 PM2 Status:"
pm2 status

echo ""
echo "📝 Useful PM2 commands:"
echo "  pm2 status                    - Check status"
echo "  pm2 logs spo-creator-api      - View logs"
echo "  pm2 restart spo-creator-api   - Restart API"
echo "  pm2 stop spo-creator-api      - Stop API"
echo "  pm2 monit                     - Monitor resources"
echo ""
echo "🌐 API should be running on: http://localhost:5112"
