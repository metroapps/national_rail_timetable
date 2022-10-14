<?php
declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use Miklcct\ThinPhpApp\Response\ViewResponseFactory;
use Http\Factory\Guzzle\ResponseFactory;
use Miklcct\NationalRailTimetable\Controllers\BoardController;
use Miklcct\NationalRailTimetable\Repositories\MongodbFixedLinkRepository;
use Miklcct\NationalRailTimetable\Repositories\MongodbLocationRepository;
use Miklcct\NationalRailTimetable\Repositories\MongodbServiceRepository;
use MongoDB\Client;
use function Http\Response\send;

require_once __DIR__ . '/../vendor/autoload.php';

set_time_limit(300);
ini_set('memory_limit', '4G');
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler(
    function (int $severity, string $message, string $file, int $line) {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting
            return;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
);

$client = new Client(driverOptions: ['typeMap' => ['array' => 'array']]);
$database = $client->selectDatabase('national_rail');

send(
    (
        new BoardController(
            new ViewResponseFactory(new ResponseFactory())
            , new MongodbLocationRepository($database->selectCollection('locations'))
            , new MongodbServiceRepository($database->selectCollection('services'), $database->selectCollection('associations'))
            , new MongodbFixedLinkRepository($database->selectCollection('fixed_links'))
        )
    )->run(ServerRequest::fromGlobals())
);