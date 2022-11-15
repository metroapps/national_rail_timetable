#!/usr/bin/php
<?php
declare(strict_types=1);

use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Parsers\FixedLinkParser;
use Miklcct\RailOpenTimetableData\Parsers\Helper;
use Miklcct\RailOpenTimetableData\Parsers\StationParser;
use Miklcct\RailOpenTimetableData\Parsers\TimetableParser;
use Miklcct\RailOpenTimetableData\Repositories\MongodbFixedLinkRepository;
use Miklcct\RailOpenTimetableData\Repositories\MongodbLocationRepository;
use Miklcct\RailOpenTimetableData\Repositories\MongodbServiceRepository;
use Psr\SimpleCache\CacheInterface;
use function Miklcct\RailOpenTimetableData\get_generated;
use function Safe\glob;

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
$date = null;
foreach ($dat_contents as $line) {
    if (str_contains($line, 'Generated')) {
        $date = preg_match('/\d{2}\/\d{2}\/\d{4}/', $line, $matches);
        sscanf($matches[0], '%d/%d/%d', $day, $month, $year);
        $date = new Date($year, $month, $day);
    }
}

if ($date === null) {
    throw new RuntimeException('Cannot get date generated.');
}

if ($date->__toString() === get_generated(get_databases()[0])?->__toString()) {
    fwrite(STDERR, "Database is up to date. Exiting.\n");
    die;
}

fprintf(STDERR, "Loading dataset %s generated at %s.\n", $prefix, $date);
fwrite(STDERR, "Dropping old database.\n");
$database = get_databases()[1];
$database->drop();

fwrite(STDERR, "Loading station data.\n");
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

fwrite(STDERR, "Loading timetable data.\n");
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

/** @var CacheInterface $cache */
$cache = get_container()->get(CacheInterface::class);
$timetable->setGeneratedDate($date);
if (!$cache->clear()) {
    throw new RuntimeException("Can't clear cache. Please delete var/cache manually!");
}
fwrite(STDERR, "Done!\n");