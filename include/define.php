<?php

define('TIME_NOW', time());

define('INCL_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('ROOT_DIR', dirname(INCL_DIR, 1) . DIRECTORY_SEPARATOR);
define('ADMIN_DIR', ROOT_DIR . 'admin' . DIRECTORY_SEPARATOR);
define('BIN_DIR', ROOT_DIR . 'bin' . DIRECTORY_SEPARATOR);
define('SCRIPTS_DIR', ROOT_DIR . 'scripts' . DIRECTORY_SEPARATOR);
define('FORUM_DIR', ROOT_DIR . 'forums' . DIRECTORY_SEPARATOR);
define('CHAT_DIR', ROOT_DIR . 'chat' . DIRECTORY_SEPARATOR);
define('PM_DIR', ROOT_DIR . 'messages' . DIRECTORY_SEPARATOR);
define('CACHE_DIR', ROOT_DIR . 'cache' . DIRECTORY_SEPARATOR);
define('LANG_DIR', ROOT_DIR . 'lang' . DIRECTORY_SEPARATOR);
define('TEMPLATE_DIR', ROOT_DIR . 'templates' . DIRECTORY_SEPARATOR);
define('BLOCK_DIR', ROOT_DIR . 'blocks' . DIRECTORY_SEPARATOR);
define('CLASS_DIR', INCL_DIR . 'class' . DIRECTORY_SEPARATOR);
define('CLEAN_DIR', ROOT_DIR . 'cleanup' . DIRECTORY_SEPARATOR);
define('PUBLIC_DIR', ROOT_DIR . 'public' . DIRECTORY_SEPARATOR);
define('IMAGES_DIR', PUBLIC_DIR . 'images' . DIRECTORY_SEPARATOR);
define('PROXY_IMAGES_DIR', IMAGES_DIR . 'proxy' . DIRECTORY_SEPARATOR);
define('VENDOR_DIR', ROOT_DIR . 'vendor' . DIRECTORY_SEPARATOR);
define('DATABASE_DIR', ROOT_DIR . 'database' . DIRECTORY_SEPARATOR);
define('BITBUCKET_DIR', ROOT_DIR . 'bucket' . DIRECTORY_SEPARATOR);
define('AVATAR_DIR', BITBUCKET_DIR . 'avatar' . DIRECTORY_SEPARATOR);
define('SQLERROR_LOGS_DIR', ROOT_DIR . 'sqlerr_logs' . DIRECTORY_SEPARATOR);
define('PLUGINS_DIR', ROOT_DIR . 'plugins' . DIRECTORY_SEPARATOR);
define('IMDB_CACHE_DIR', DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'shm' . DIRECTORY_SEPARATOR . 'imdb' . DIRECTORY_SEPARATOR);
define('PARTIALS_DIR', ROOT_DIR . 'partials' . DIRECTORY_SEPARATOR);
define('TORRENTS_DIR', ROOT_DIR . 'torrents' . DIRECTORY_SEPARATOR);
define('USER_TORRENTS_DIR', TORRENTS_DIR . 'users' . DIRECTORY_SEPARATOR);
define('BACKUPS_DIR', ROOT_DIR . 'backups' . DIRECTORY_SEPARATOR);
define('AJAX_CHAT_PATH', ROOT_DIR . 'chat' . DIRECTORY_SEPARATOR);

define('SQL_DEBUG', true);
define('IP_LOGGING', true);
define('XBT_TRACKER', false);
define('REQUIRE_CONNECTABLE', false);
define('SOCKET', true);
define('NFO_SIZE', 1048576);

define('PM_DELETED', 0);
define('PM_INBOX', 1);
define('PM_SENTBOX', -1);
define('PM_DRAFTS', -2);

define('INTERVAL_1_MIN', 0);
define('INTERVAL_5_MIN', 1);
define('INTERVAL_15_MIN', 2);
define('DEFAULT_AVG', INTERVAL_15_MIN);

define('CRAZY_HOUR', false);
define('HAPPY_HOUR', false);
define('RATIO_FREE', false);

require_once CACHE_DIR . 'class_config.php';
define('MIN_TO_PLAY', UC_POWER_USER);
