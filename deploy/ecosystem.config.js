module.exports = {
  apps: [{
    name: 'aisim-webhook',
    script: 'deploy/webhook-server.js',
    cwd: __dirname + '/..',
    env: {
      NODE_ENV: 'production',
      WEBHOOK_PORT: 9012,
      WEBHOOK_SECRET: process.env.WEBHOOK_SECRET || 'aisim-demo-secret-2026',
      TARGET_BRANCH: 'main'
    },
    autorestart: true,
    watch: false,
    max_memory_restart: '150M'
  }]
};
