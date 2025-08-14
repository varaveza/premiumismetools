#!/bin/bash

# Script untuk install dan setup cron di VPS
# Jalankan: sudo ./install-cron.sh

echo "🔧 Installing and setting up cron for Tools Cleanup..."

# Detect OS
if [ -f /etc/debian_version ]; then
    echo "📦 Detected Debian/Ubuntu system"
    # Install cron
    sudo apt update
    sudo apt install -y cron
    
    # Start dan enable cron
    sudo systemctl start cron
    sudo systemctl enable cron
    
elif [ -f /etc/redhat-release ]; then
    echo "📦 Detected CentOS/RHEL/Rocky system"
    # Install cronie
    if command -v dnf &> /dev/null; then
        sudo dnf install -y cronie
    else
        sudo yum install -y cronie
    fi
    
    # Start dan enable crond
    sudo systemctl start crond
    sudo systemctl enable crond
    
else
    echo "❌ Unsupported OS. Please install cron manually."
    exit 1
fi

# Check cron service status
if sudo systemctl is-active --quiet cron || sudo systemctl is-active --quiet crond; then
    echo "✅ Cron service is running"
else
    echo "❌ Cron service failed to start"
    exit 1
fi

# Set permission untuk cleanup script
if [ -f "auto-cleanup.sh" ]; then
    chmod +x auto-cleanup.sh
    echo "✅ Set permission for auto-cleanup.sh"
fi

# Setup cron job (sesuaikan dengan path yang benar)
CRON_JOB="0 2 * * * /var/www/shortisme.com/public_html/auto-cleanup.sh"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "auto-cleanup.sh"; then
    echo "⚠️  Cron job already exists"
else
    # Add cron job
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "✅ Added cron job: $CRON_JOB"
fi

# Show current cron jobs
echo "📋 Current cron jobs:"
crontab -l

# Test cleanup script
echo "🧪 Testing cleanup script..."
./auto-cleanup.sh

echo "🎉 Cron setup completed!"
echo "📝 Cleanup will run daily at 2:00 AM"
echo "📊 Check logs: tail -f cleanup-all.log"
