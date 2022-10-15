<?php
declare(strict_types = 1);

require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);
set_time_limit(300);
ini_set('memory_limit', '4G');
set_error_handler(
    function (int $severity, string $message, string $file, int $line) {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting
            return;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
);

