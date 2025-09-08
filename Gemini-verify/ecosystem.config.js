module.exports = {
  apps: [
    {
      name: 'gemini-creator-php',
      script: 'php',
      args: '-S 0.0.0.0:6000',
      cwd: __dirname,
      interpreter: null,
      watch: false,
      autorestart: true,
      env: {
        PHP_CLI_SERVER_WORKERS: '4'
      }
    }
  ]
};
