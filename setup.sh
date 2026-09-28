#!/bin/bash
cd /var/www/html || exit 1
wpc(){ runuser -u www-data -- wp --path=/var/www/html "$@"; }

for i in $(seq 90); do [ -f wp-config.php ] && break; sleep 1; done

until php -r '
[$h,$p]=array_pad(explode(":",getenv("WORDPRESS_DB_HOST"),2),2,3306);
$m=mysqli_init();
exit(@$m->real_connect($h,getenv("WORDPRESS_DB_USER"),getenv("WORDPRESS_DB_PASSWORD"),getenv("WORDPRESS_DB_NAME"),(int)$p,null,getenv("DB_SSL")?MYSQLI_CLIENT_SSL:0)?0:1);'
do sleep 5; done

if ! wpc core is-installed 2>/dev/null; then
  wpc core install --url="$WP_URL" --title="کفش‌سرا" --admin_user="${ADMIN_USER:-admin}" --admin_password="$ADMIN_PASSWORD" --admin_email="$ADMIN_EMAIL" --skip-email
fi

# فایل‌های زبان روی دیسک موقت‌اند؛ هر بوت دوباره دانلود می‌شوند
wpc language core install fa_IR --activate >/dev/null 2>&1
wpc language plugin install --all fa_IR >/dev/null 2>&1
wpc language theme install --all fa_IR >/dev/null 2>&1

wpc plugin activate woocommerce persian-woocommerce >/dev/null 2>&1
wpc theme activate storefront >/dev/null 2>&1
wpc eval-file /setup/seed.php

# WP-Cron واقعی هر ۵ دقیقه
while sleep 300; do wpc cron event run --due-now >/dev/null 2>&1; done
