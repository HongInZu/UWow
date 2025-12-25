#!/bin/bash

# 複製 .env 如果不存在
if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || echo "APP_ENV=production" > .env
fi

# 產生 APP_KEY
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

# 執行 migration
php artisan migrate --force

# 建立 storage link
php artisan storage:link

# 清除並快取設定
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 啟動 supervisor
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
