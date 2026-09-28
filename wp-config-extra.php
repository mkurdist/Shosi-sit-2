<?php
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') $_SERVER['HTTPS'] = 'on';
if (getenv('WP_URL')) { define('WP_HOME', rtrim(getenv('WP_URL'), '/')); define('WP_SITEURL', rtrim(getenv('WP_URL'), '/')); }
if (getenv('DB_SSL')) define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
define('DISABLE_WP_CRON', true);
define('WP_POST_REVISIONS', 3);
define('WP_MEMORY_LIMIT', '256M');
define('AUTOMATIC_UPDATER_DISABLED', true);
define('DISALLOW_FILE_EDIT', true);
define('EMPTY_TRASH_DAYS', 7);
