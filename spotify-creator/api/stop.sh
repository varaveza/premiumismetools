#!/bin/bash

# SPO Creator API Stop Script for aaPanel

# Set working directory
cd "$(dirname "$0")"

# Stop Gunicorn processes
echo "Stopping SPO Creator API server..."

# Kill by process name
pkill -f "gunicorn.*api_app"

# Kill by PID file if it exists
if [ -f logs/gunicorn.pid ]; then
    PID=$(cat logs/gunicorn.pid)
    if kill -0 $PID 2>/dev/null; then
        echo "Stopping process $PID..."
        kill -TERM $PID
        sleep 5
        if kill -0 $PID 2>/dev/null; then
            echo "Force killing process $PID..."
            kill -KILL $PID
        fi
    fi
    rm -f logs/gunicorn.pid
fi

# Kill any remaining Python processes running api_app
pkill -f "python.*api_app"

echo "SPO Creator API server stopped."
