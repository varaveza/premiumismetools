#!/bin/bash

echo "🚀 Setting up Surfshark Creator with PM2..."

# Install PM2 globally if not installed
if ! command -v pm2 &> /dev/null; then
    echo "📦 Installing PM2..."
    npm install -g pm2
fi

# Install dependencies
echo "📦 Installing dependencies..."
npm install

# Create logs directory
echo "📁 Creating logs directory..."
mkdir -p logs

# Start with PM2
echo "🚀 Starting Surfshark Creator with PM2..."
pm2 start ecosystem.config.js

# Save PM2 configuration
echo "💾 Saving PM2 configuration..."
pm2 save

# Setup PM2 startup
echo "🔄 Setting up PM2 startup..."
pm2 startup

echo "✅ Setup complete!"
echo ""
echo "PM2 Commands:"
echo "  pm2 status                    # Check status"
echo "  pm2 logs surfshark-creator   # View logs"
echo "  pm2 restart surfshark-creator # Restart"
echo "  pm2 stop surfshark-creator   # Stop"
echo ""
echo "Access the frontend at: index.php"
