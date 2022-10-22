<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use DateInterval;
use LogicException;
use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Miklcct\NationalRailTimetable\Models\FixedLink;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\ServiceCall;
use Psr\Http\Message\StreamFactoryInterface;
use function array_map;
use function implode;
use function Miklcct\NationalRailTimetable\get_all_tocs;
use function Miklcct\ThinPhpApp\Escaper\html;

class BoardView extends BoardFormView {
    use ScheduleTrait;

    public const URL = '/index.php';
    /**
     * @param StreamFactoryInterface $streamFactory
     * @param array $stations
     * @param DepartureBoard $board
     * @param Date $date
     * @param BoardQuery $query
     * @param FixedLink[]|null $fixedLinks
     * @param Date|null $generated
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , array $stations
        , protected readonly DepartureBoard $board
        , protected readonly Date $date
        , BoardQuery $query
        , protected readonly ?array $fixedLinks
        , protected readonly ?Date $generated
    ) {
        parent::__construct($streamFactory, $query, $stations);
    }

    protected function getTitle() : string {
        return sprintf(
            '%s at %s %s %s%s'
            , $this->query->arrivalMode ? 'Arrivals' : 'Departures'
            , $this->query->station->name
            , $this->query->filter !== []
                ? ($this->query->arrivalMode ? ' from ' : ' to ') . implode(
                    ', '
                    , array_map(static fn(Location $location) => $location->name, $this->query->filter)
                )
                : ''
            , $this->query->date === null ? 'today' : 'on ' . $this->date
            , $this->query->permanentOnly ? ' (permanent timetable)' : ''
        );
    }

    protected function getHeading() : string {
        $location = $this->query->station;
        assert($location instanceof Location);
        $result = ($this->query->arrivalMode ? 'Arrivals at ' : 'Departures at ') . $this->getNameAndCrs($location);

        $filter = $this->query->filter;
        if ($filter !== []) {
            $result .= ' calling at ' . implode(
                ', '
                , array_map(
                    fn(Location $location) => $this->getNameAndCrs($location)
                    , $filter
                )
            );
        }
        $result .= ' ';
        $result .= $this->query->date === null ? 'today' : 'on ' . $this->date;
        return $result;
    }

    protected function getNameAndCrs(Location $location) : string {
        if (!$location instanceof LocationWithCrs) {
            return $location->name;
        }
        return sprintf('%s (%s)', $location->name, $location->getCrsCode());
    }

    protected function getFormData(): array {
        return $this->query->toArray();
    }

    protected function getArrivalLink(ServiceCall $service_call) : ?string {
        $location = $service_call->call->location;
        if (!$location instanceof LocationWithCrs) {
            return null;
        }
        return (
            new BoardQuery(
                $this->query->arrivalMode
                , $location
                , []
                , Date::fromDateTimeInterface($service_call->timestamp->sub(new DateInterval($this->query->arrivalMode ? 'PT4H30M' : 'P0D')))
                , $service_call->timestamp
                , $service_call->toc
                , $this->query->permanentOnly
            )
        )->getUrl(BoardQuery::BOARD_URL);
    }

    protected function getDayOffsetLink(int $days) : string {
        return (new BoardQuery(
            $this->query->arrivalMode
            , $this->query->station
            , $this->query->filter
            , $this->date->addDays($days)
            , $this->query->connectingTime
            , $this->query->connectingToc
            , $this->query->permanentOnly
        ))->getUrl(BoardQuery::BOARD_URL);
    }

    protected function showToc(string $toc) : string {
        return sprintf('<abbr title="%s">%s</abbr>', html(get_all_tocs()[$toc] ?? ''), html($toc));
    }

    protected function showFacilities(ServiceCall $service_call) : string {
        return $service_call->mode->showIcon() . $service_call->serviceProperty->showIcons();
    }

    protected function getReverseDirectionLink() : string {
        if (count($this->query->filter) !== 1) {
            throw new LogicException('Reversing is only allowed when filter count is exactly 1.');
        }
        return (new BoardQuery(
            $this->query->arrivalMode
            , $this->query->filter[0]
            , [$this->query->station]
            , $this->query->date
            , null
            , null
            , $this->query->permanentOnly
        ))->getUrl(BoardQuery::BOARD_URL);
    }
}