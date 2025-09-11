#!/bin/bash

echo "🚀 Setting up Surfshark Creator..."

# Install dependencies
echo "📦 Installing dependencies..."
npm install

# Create logs directory
echo "📁 Creating logs directory..."
mkdir -p logs

# Set permissions
echo "🔐 Setting permissions..."
chmod +x setup.sh

echo "✅ Setup complete!"
echo ""
echo "To start the backend:"
echo "  npm run dev"
echo ""
echo "To start with PM2:"
echo "  pm2 start ecosystem.config.js"
echo ""
echo "Access the frontend at: index.php"
