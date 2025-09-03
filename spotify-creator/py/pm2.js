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
    }
  ]
};


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
    }
  ]
};


