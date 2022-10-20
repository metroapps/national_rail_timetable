<?php
declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use Miklcct\NationalRailTimetable\Controllers\ServiceController;

use function Http\Response\send;

require_once __DIR__ . '/../initialise.php';

send(get_container()->get(ServiceController::class)->handle(ServerRequest::fromGlobals()));