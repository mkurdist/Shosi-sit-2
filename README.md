# کفش‌سرا — WordPress + WooCommerce روی Render (رایگان)

1. یک دیتابیس MySQL/MariaDB خارجی و پایدار بسازید (دیسک Render پاک می‌شود).
2. این پوشه را در GitHub بگذارید (Dockerfile در ریشه).
3. Render → New → Blueprint (یا Web Service با Docker) → ریپو را وصل کنید.
4. مقدار متغیرهای `sync:false` را وارد کنید:
   - `WP_URL` = آدرس Render بدون اسلش آخر
   - `WORDPRESS_DB_HOST` = `host:port`
   - `ADMIN_USER` / `ADMIN_PASSWORD` / `ADMIN_EMAIL`
   - `CARD_NUMBER` / `CARD_HOLDER` / `CARD_BANK`
5. بعد از دیپلوی اول (۲ تا ۵ دقیقه) وارد `/wp-admin` شوید.
6. UptimeRobot روی `/health.txt` هر ۵ دقیقه.

سفارش کارت‌به‌کارت: وضعیت «در انتظار بررسی» → در صفحه سفارش، Order actions → «تأیید پرداخت کارت به کارت».
هشدار: عکس‌های آپلودشده روی دیسک موقت Render می‌مانند تا ری‌استارت بعدی؛ برای ماندگاری افزونه انتقال رسانه به S3/R2 لازم است.
