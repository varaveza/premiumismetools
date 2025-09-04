# Gunicorn configuration file for aaPanel deployment

import os
import multiprocessing

# Server socket
bind = "0.0.0.0:5111"
backlog = 2048

# Worker processes
workers = multiprocessing.cpu_count() * 2 + 1
worker_class = "sync"
worker_connections = 1000
timeout = 300
keepalive = 2

# Restart workers after this many requests, to help prevent memory leaks
max_requests = 1000
max_requests_jitter = 50

# Logging
accesslog = "logs/access.log"
errorlog = "logs/error.log"
loglevel = "info"
access_log_format = '%(h)s %(l)s %(u)s %(t)s "%(r)s" %(s)s %(b)s "%(f)s" "%(a)s" %(D)s'

# Process naming
proc_name = "spo-creator-api"

# Server mechanics
daemon = False
pidfile = "logs/gunicorn.pid"
user = None
group = None
tmp_upload_dir = None

# SSL (uncomment if using HTTPS)
# keyfile = "ssl/private.key"
# certfile = "ssl/certificate.crt"

# Preload app for better performance
preload_app = True

# Worker timeout for long-running requests
timeout = 300

# Graceful timeout
graceful_timeout = 30

# Environment variables
raw_env = [
    'FLASK_ENV=production',
]

def when_ready(server):
    """Called just after the server is started."""
    server.log.info("SPO Creator API server is ready. Workers: %s", server.cfg.workers)

def worker_int(worker):
    """Called just after a worker exited on SIGINT or SIGQUIT."""
    worker.log.info("worker received INT or QUIT signal")

def pre_fork(server, worker):
    """Called just before a worker is forked."""
    server.log.info("Worker spawned (pid: %s)", worker.pid)

def post_fork(server, worker):
    """Called just after a worker has been forked."""
    server.log.info("Worker spawned (pid: %s)", worker.pid)

def worker_abort(worker):
    """Called when a worker received the SIGABRT signal."""
    worker.log.info("worker received SIGABRT signal")
