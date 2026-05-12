<?php
declare(strict_types=1);

define('DB_DSN', 'mysql:host=127.0.0.1;dbname=cloud_api;charset=utf8mb4');
define('DB_USER', 'root');
define('DB_PASS', '');
define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('ALLOWED_EXTENSIONS', ['doc', 'pdf', 'docx', 'zip', 'jpeg', 'jpg', 'png']);
define('MAX_FILE_SIZE', 2097152);