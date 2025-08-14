#!/bin/bash

# All-in-One Auto Cleanup Script untuk Tools
# Jalankan via cron: 0 2 * * * /path/to/tools/auto-cleanup.sh

# Set working directory
cd "$(dirname "$0")"

# Log file
LOG_FILE="cleanup-all.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

echo "[$DATE] Starting all-in-one auto cleanup..." >> "$LOG_FILE"

# Jalankan PHP cleanup script
php cleanup-all.php >> "$LOG_FILE" 2>&1

echo "[$DATE] All-in-one auto cleanup completed." >> "$LOG_FILE"

# Optional: Kirim notifikasi jika ada file yang dihapus
# echo "Cleanup completed at $DATE" | mail -s "Tools Cleanup" admin@domain.com
