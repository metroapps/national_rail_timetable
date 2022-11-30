<?php
declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use Metroapps\NationalRailTimetable\Controllers\StationIndexController;
use function Http\Response\send;

require_once __DIR__ . '/../initialise.php';

send(get_container()->get(StationIndexController::class)->handle(ServerRequest::fromGlobals()));