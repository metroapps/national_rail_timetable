<?php
declare(strict_types = 1);

use Teapot\HttpException;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

require_once __DIR__ . '/vendor/autoload.php';

$whoops = new Run;
$pretty_page_handler = new PrettyPageHandler;
$pretty_page_handler->setEditor(PrettyPageHandler::EDITOR_VSCODE);
$whoops->pushHandler($pretty_page_handler);
$whoops->pushHandler(
    new class extends Handler {
        public function handle() {
            $exception = $this->getException();
            if ($exception instanceof HttpException) {
                $this->getRun()->sendHttpCode($exception->getCode());
            }
        }
    }
);
$whoops->register();

error_reporting(E_ALL);
set_time_limit(300);
ini_set('memory_limit', '4G');
date_default_timezone_set('Europe/London');