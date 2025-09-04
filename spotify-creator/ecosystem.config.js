module.exports = {
  apps: [{
    name: 'spo-creator-api',
    script: 'api_app.py',
    interpreter: 'python3',
    cwd: '/var/www/premiumisme.co/api',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      FLASK_ENV: 'production',
      PORT: 5112
    },
    env_production: {
      NODE_ENV: 'production',
      FLASK_ENV: 'production',
      PORT: 5112
    },
    log_file: '/var/www/premiumisme.co/api/logs/combined.log',
    out_file: '/var/www/premiumisme.co/api/logs/out.log',
    error_file: '/var/www/premiumisme.co/api/logs/error.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    time: true
  }]
};
