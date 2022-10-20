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
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

require __DIR__ . '/../initialise.php';
ini_set('memory_limit', '16G');
set_time_limit(0);

$path = $argv[1];
$file_names = glob("$path/*.DAT");
if ($file_names === []) {
    throw new RuntimeException('No data exist!');
}

rsort($file_names);
$prefix = basename($file_names[0], '.DAT');

$dat_contents = file("$path/$prefix.DAT");
foreach ($dat_contents as $line) {
    if (str_contains($line, 'Generated')) {
        $date = preg_match('/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/', $line, $matches);
        sscanf($matches[0], '%d/%d/%d', $day, $month, $year);
        $date = new Date($year, $month, $day);
    }
}

fprintf(STDERR, "Loading dataset %s generated at %s.\n", $prefix, $date);
fputs(STDERR, "Dropping old database.\n");
/** @var Database */
$database = get_databases()[1];
$database->drop();

fputs(STDERR, "Loading station data.\n");
$time = microtime(true);
$stations = new MongodbLocationRepository($database);
$fixed_links = new MongodbFixedLinkRepository($database);
(new StationParser(new Helper(), $stations))
    ->parseFile(
        fopen("$path/$prefix.MSN", 'rb')
        , fopen("$path/$prefix.TSI", 'rb')
    );
(new FixedLinkParser(new Helper, $stations, $fixed_links))
    ->parseFile(fopen("$path/$prefix.ALF", 'rb'));
$stations->addIndexes();
$fixed_links->addIndexes();
fprintf(STDERR, "Time used: %.3f s\n", microtime(true) - $time);

fputs(STDERR, "Loading timetable data.\n");
$time = microtime(true);
$timetable = new MongodbServiceRepository($database, null);
foreach (['MCA', 'ZTR'] as $suffix) {
    (new TimetableParser(new Helper(), $timetable, $stations))
        ->parseFile(
            fopen("$path/$prefix.$suffix", 'rb')
        );
}
$timetable->addIndexes();
fprintf(STDERR, "Time used: %.3f s\n", microtime(true) - $time);

/** @var CacheInterface */
$cache = get_container()->get(CacheInterface::class);
$timetable->setGeneratedDate($date);
$cache->clear();
fputs(STDERR, "Done!\n");