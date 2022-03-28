<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Parsers;

use DateTimeZone;
use LogicException;
use Miklcct\NationalRailJourneyPlanner\Models\FixedLink;
use Miklcct\NationalRailJourneyPlanner\Models\Station;
use Miklcct\NationalRailJourneyPlanner\Repositories\FixedLinkRepositoryInterface;
use Miklcct\NationalRailJourneyPlanner\Repositories\MemoryFixedLinkRepository;
use Miklcct\NationalRailJourneyPlanner\Models\Time;
use Miklcct\NationalRailJourneyPlanner\Repositories\LocationRepositoryInterface;
use Safe\DateTimeImmutable;
use function explode;
use function fgetcsv;

class FixedLinkParser {
    public function __construct(private readonly Helper $helper) {}

    /**
     * @param resource $file additional fixed links file (name ends with .ALF)
     * @param LocationRepositoryInterface $location_repository
     * @param FixedLinkRepositoryInterface $fixed_link_repository
     * @return void
     */
    public function parseFile(
        $file
        , LocationRepositoryInterface $location_repository
        , FixedLinkRepositoryInterface $fixed_link_repository
    ) : void {
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
                    $origin = $location_repository->getLocationByCrs($fields[1]);
                    if (!$origin instanceof Station) {
                        throw new LogicException('Fixed links must start and end with a station.');
                    }
                    break;
                case 'D':
                    $destination = $location_repository->getLocationByCrs($fields[1]);
                    if (!$destination instanceof Station) {
                        throw new LogicException('Fixed links must start and end with a station.');
                    }

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
            $fixed_links[] = new FixedLink(
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
        }
        $fixed_link_repository->insert($fixed_links);
    }
}