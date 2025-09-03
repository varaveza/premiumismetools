module.exports = {
  apps: [
    {
      name: 'spo',
      cwd: __dirname,
      script: 'bash',
      args: ['-lc', 'gunicorn -w 2 -b 127.0.0.1:5111 app:APP'],
      env: {
        BACKEND_API_KEY: 'pablocc@222',
        USE_PROXY: 'true',
        PYTHONUNBUFFERED: '1'
      },
      autorestart: true,
      watch: false,
      max_memory_restart: '300M',
      time: true
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


