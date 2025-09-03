module.exports = {
  apps: [
    {
      name: 'spo-cli',
      cwd: __dirname,
      script: 'bash',
      args: ['-lc', 'while true; do sleep 1; done'],
      env: {
        BACKEND_API_KEY: 'pablocc@222',
        USE_PROXY: 'true',
        PYTHONUNBUFFERED: '1'
      },
      autorestart: true,
      watch: false,
      max_memory_restart: '300M',
      time: true,
      instances: 3,
      exec_mode: 'cluster'
    },
    {
      name: 'spo-cookie-cleaner',
      cwd: __dirname,
      script: 'node',
      args: ['cleanup_cookies.js'],
      autorestart: false,
      watch: false,
      time: true,
      cron_restart: '0 * * * *' // run hourly
    }
  ]
};


