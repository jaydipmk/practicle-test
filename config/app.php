<?php

define('BASE_URL',       'http://localhost/incident-system');
define('UPLOAD_DIR',     __DIR__ . '/../uploads/');
define('UPLOAD_URL',     BASE_URL . '/uploads/');
define('MAX_FILE_SIZE',  5 * 1024 * 1024);
define('ALLOWED_TYPES',  ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);
define('APP_NAME',       'Incident Reporting System');