#!/usr/bin/php
<?php
declare(strict_types=1);

use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Parsers\FixedLinkParser;
use Miklcct\NationalRailTimetable\Parsers\Helper;
use Miklcct\NationalRailTimetable\Parsers\StationParser;
use Miklcct\NationalRailTimetable\Parsers\TimetableParser;
use Miklcct\NationalRailTimetable\Repositories\MongodbFixedLinkRepository;
use Miklcct\NationalRailTimetable\Repositories\MongodbLocationRepository;
use Miklcct\NationalRailTimetable\Repositories\MongodbServiceRepository;
use MongoDB\Database;

use function Miklcct\NationalRailTimetable\get_databases;

require __DIR__ . '/../initialise.php';
ini_set('memory_limit', '16G');
set_time_limit(0);

[$path, $prefix] = [$argv[1], $argv[2]];

/** @var Database */
$database = get_databases()[1];
$database->drop();

$stations = new MongodbLocationRepository($database->selectCollection('locations'));
$fixed_links = new MongodbFixedLinkRepository($database->selectCollection('fixed_links'));
(new StationParser(new Helper(), $stations))
    ->parseFile(
        fopen("$path/$prefix.MSN", 'rb')
        , fopen("$path/$prefix.TSI", 'rb')
    );
(new FixedLinkParser(new Helper, $stations, $fixed_links))
    ->parseFile(fopen("$path/$prefix.ALF", 'rb'));
$stations->addIndexes();
$fixed_links->addIndexes();
$timetable = new MongodbServiceRepository(
    $database->selectCollection('services'),
    $database->selectCollection('associations')
    , null
);
$timetable->addGeneratedIndex();
foreach (['MCA', 'ZTR'] as $suffix) {
    (new TimetableParser(new Helper(), $timetable, $stations))
        ->parseFile(
            fopen("$path/$prefix.$suffix", 'rb')
        );
}
$timetable->addIndexes();

$dat_contents = file("$path/$prefix.DAT");
$metadata = $database->selectCollection('metadata');
foreach ($dat_contents as $line) {
    if (str_contains($line, 'Generated')) {
        $date = preg_match('/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/', $line, $matches);
        sscanf($matches[0], '%d/%d/%d', $day, $month, $year);
        $timetable->setGeneratedDate(new Date($year, $month, $day));
    }
}
