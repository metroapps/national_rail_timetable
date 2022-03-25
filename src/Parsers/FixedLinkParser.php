<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Parsers;

use DateTimeZone;
use Miklcct\NationalRailJourneyPlanner\Models\FixedLink;
use Miklcct\NationalRailJourneyPlanner\Models\FixedLinks;
use Miklcct\NationalRailJourneyPlanner\Models\Stations;
use Miklcct\NationalRailJourneyPlanner\Models\Time;
use Safe\DateTimeImmutable;
use function explode;
use function fgetcsv;

class FixedLinkParser {
    public function __construct(private readonly Helper $helper) {}

    /**
     * @param resource $file additional fixed links file (name ends with .ALF)
     * @param Stations $stations
     * @return FixedLinks
     */
    public function parseFile($file, Stations $stations) : FixedLinks {
        /** @var array<string, array<string, FixedLink[]>> */
        $fixedLinksByOriginCrs = [];
        /** @var array<string, array<string, FixedLink[]>> */
        $fixedLinksByDestinationCrs = [];
        while (($columns = fgetcsv($file)) !== false) {
            $mode = null;
            $origin = null;
            $destination = null;
            $transferTime = null;
            $startTime = null;
            $endTime = null;
            $priority = null;
            $startDate = null;
            $endDate = null;
            $weekdays = null;
            foreach ($columns as $column) {
                $fields = explode('=', $column);
                switch ($fields[0]) {
                case 'M':
                    $mode = $fields[1];
                    break;
                case 'O':
                    $origin = $stations->stationsByCrs[$fields[1]];
                    break;
                case 'D':
                    $destination = $stations->stationsByCrs[$fields[1]];
                    break;
                case 'T':
                    $transferTime = (int)$fields[1];
                    break;
                case 'S':
                    $startTime = Time::fromHhmm($fields[1]);
                    break;
                case 'E':
                    $endTime = Time::fromHhmm($fields[1]);
                    break;
                case 'P':
                    $priority = (int)$fields[1];
                    break;
                case 'F':
                    $startDate = DateTimeImmutable::createFromFormat(
                        'd/m/Y'
                        , $fields[1]
                        , new DateTimeZone('Europe/London')
                    )->setTime(0, 0);
                    break;
                case 'U':
                    $endDate = DateTimeImmutable::createFromFormat(
                        'd/m/Y'
                        , $fields[1]
                        , new DateTimeZone('Europe/London')
                    )->setTime(0, 0);
                    break;
                case 'R':
                    $weekdays = $this->helper->parseWeekdays($fields[1]);
                    break;
                }
            }
            $fixed_link = new FixedLink(
                $mode
                , $origin
                , $destination
                , $transferTime
                , $startTime
                , $endTime
                , $priority
                , $startDate
                , $endDate
                , $weekdays
            );
            $fixedLinksByOriginCrs[$origin->crsCode][$destination->crsCode][] = $fixed_link;
            $fixedLinksByDestinationCrs[$destination->crsCode][$origin->crsCode][] = $fixed_link;
        }
        return new FixedLinks($fixedLinksByOriginCrs, $fixedLinksByDestinationCrs);
    }
}