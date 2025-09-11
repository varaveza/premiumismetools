module.exports = {
  apps: [{
    name: 'surfshark-creator',
    script: 'app.js',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 7070,
      MAX_GLOBAL_PER_DAY: 300,
      MAX_PER_IP_PER_DAY: 25,
      LOCAL_ONLY: 'true'
    },
    env_development: {
      NODE_ENV: 'development',
      PORT: 7070,
      MAX_GLOBAL_PER_DAY: 1000,
      MAX_PER_IP_PER_DAY: 100,
      LOCAL_ONLY: 'false'
    }
  }]
};
