module.exports = {
  apps: [
    {
      name: 'surfshark-creator',
      script: 'server.js',
      instances: 1,
      exec_mode: 'fork',
      watch: false,
      autorestart: true,
      max_memory_restart: '300M',
      env: {
        NODE_ENV: 'production'
        // OPTIONALLY override below (server.js sudah baca dari ../config.json):
        // PORT: 7070,
        // ALLOW_EXTERNAL: 'true',
        // DEFAULT_PASSWORD: 'Premium@123',
        // DEFAULT_DOMAIN: '@yotomail.com'
      }
    }
  ]
};


