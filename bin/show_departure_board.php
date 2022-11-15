#!/usr/bin/php
<?php
declare(strict_types=1);

use Miklcct\RailOpenTimetableData\Enums\TimeType;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\Points\TimingPoint;
use Miklcct\RailOpenTimetableData\Models\Time;
use Miklcct\RailOpenTimetableData\Repositories\MongodbServiceRepository;
use Safe\DateTimeImmutable;
use function Safe\getopt;

require __DIR__ . '/../initialise.php';
ini_set('memory_limit', '16G');
set_time_limit(0);

$options = getopt('', ['arrivals'], $rest_index);
$argv = array_slice($argv, $rest_index);
if (count($argv) !== 2) {
    throw new RuntimeException('A date and CRS code must be specified to generate the board.');
}

if (strlen($argv[1]) !== 3) {
    throw new RuntimeException('Invalid CRS code');
}

$arrival_mode = isset($options['arrivals']);
$date = Date::fromDateTimeInterface(
    (new DateTimeImmutable($argv[0]))->sub(new DateInterval($arrival_mode ? 'PT4H30S' : 'P0D'))
);

/** @var MongodbServiceRepository $repository */
$repository = get_container()->get(MongodbServiceRepository::class);
$board = $repository->getDepartureBoard(
    strtoupper($argv[1])
    , $date->toDateTimeImmutable()
    , $date->toDateTimeImmutable(new Time(28, 30))
    , $arrival_mode ? TimeType::PUBLIC_ARRIVAL : TimeType::PUBLIC_DEPARTURE
);

foreach ($board->calls as $call) {
    printf(
        "%s\t%s\t%s\n"
        , $call->timestamp->format('H:i')
        , substr($call->serviceProperty->rsid ?? '', 0, 6)
        , implode(
            ' and '
            , array_map(
                static fn(TimingPoint $point) => $point->location->getShortName()
                , $arrival_mode ? $call->origins : $call->destinations
            )
        )
    );
}