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
      PORT: 5112,
      CAPSOLVER_API_KEY: 'CAP-6E976677CE849E6F8CE434D5E64DA95D4F527360EEBDB0E849DA3F008A3E59BE'
    },
    env_production: {
      NODE_ENV: 'production',
      FLASK_ENV: 'production',
      PORT: 5112,
      CAPSOLVER_API_KEY: 'CAP-6E976677CE849E6F8CE434D5E64DA95D4F527360EEBDB0E849DA3F008A3E59BE'
    },
    log_file: '/var/www/premiumisme.co/api/logs/combined.log',
    out_file: '/var/www/premiumisme.co/api/logs/out.log',
    error_file: '/var/www/premiumisme.co/api/logs/error.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    time: true
  }]
};
