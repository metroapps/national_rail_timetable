<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use DateInterval;
use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Miklcct\NationalRailTimetable\Models\FixedLink;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\ServiceCall;
use Psr\Http\Message\StreamFactoryInterface;
use function Miklcct\NationalRailTimetable\get_all_tocs;
use function Miklcct\ThinPhpApp\Escaper\html;

class BoardView extends BoardFormView {
    /**
     * @param StreamFactoryInterface $streamFactory
     * @param array $stations
     * @param DepartureBoard $board
     * @param Date $boardDate
     * @param BoardQuery $query
     * @param FixedLink[]|null $fixedLinks
     * @param DateTimeImmutable|null $fixedLinkDepartureTime
     * @param Date|null $generated
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , array $stations
        , protected readonly DepartureBoard $board
        , protected readonly Date $boardDate
        , protected readonly BoardQuery $query
        , protected readonly ?array $fixedLinks
        , protected readonly ?DateTimeImmutable $fixedLinkDepartureTime
        , protected readonly ?Date $generated
    ) {
        parent::__construct($streamFactory, $stations);
    }

    protected function getTitle() : string {
        return sprintf(
            '%s at %s %s %s%s'
            , $this->query->arrivalMode ? 'Arrivals' : 'Departures'
            , $this->query->station->name
            , $this->query->filter !== null
                ? ($this->query->arrivalMode ? ' from ' : ' to ') . $this->query->filter->name
                : ''
            , $this->query->date === null ? 'today' : 'on ' . $this->boardDate
            , $this->query->permanentOnly ? ' (permanent timetable)' : ''
        );
    }

    protected function getHeading() : string {
        $location = $this->query->station;
        assert($location instanceof Location);
        $result = ($this->query->arrivalMode ? 'Arrivals at ' : 'Departures at ') . $this->getNameAndCrs($location);

        $filter = $this->query->filter;
        if ($filter instanceof Location) {
            $result .= ' calling at ' . $this->getNameAndCrs($filter);
        }
        $result .= ' ';
        $result .= $this->query->date === null ? 'today' : 'on ' . $this->boardDate;
        return $result;
    }

    protected function getNameAndCrs(Location $location) : string {
        if (!$location instanceof LocationWithCrs) {
            return $location->name;
        }
        return sprintf('%s (%s)', $location->name, $location->getCrsCode());
    }

    protected function getFixedLinkUrl(FixedLink $fixed_link, ?DateTimeImmutable $departure_time) : string {
        return (
            new BoardQuery(
                $this->query->arrivalMode
                , $fixed_link->destination
                , null
                , $this->query->connectingTime !== null
                    ? Date::fromDateTimeInterface($this->query->connectingTime->sub(new DateInterval($this->query->arrivalMode ? 'PT4H30M' : 'P0D')))
                    : $this->boardDate
                , $departure_time === null
                    ? null
                    : $fixed_link->getArrivalTime($departure_time, $this->query->arrivalMode)
                , null
                , $this->query->permanentOnly
            )
        )->getUrl();
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
                , null
                , Date::fromDateTimeInterface($service_call->timestamp->sub(new DateInterval($this->query->arrivalMode ? 'PT4H30M' : 'P0D')))
                , $service_call->timestamp
                , $service_call->toc
                , $this->query->permanentOnly
            )
        )->getUrl();
    }

    protected function getDayOffsetLink(int $days) : string {
        return (new BoardQuery(
            $this->query->arrivalMode
            , $this->query->station
            , $this->query->filter
            , $this->boardDate->addDays($days)
            , $this->query->connectingTime
            , $this->query->connectingToc
            , $this->query->permanentOnly
        ))->getUrl();
    }

    protected function getServiceLink(ServiceCall $service_call) : string {
         return '/service.php?' . http_build_query(
            [
                'uid' => $service_call->uid,
                'date' => $service_call->date->__toString(),
            ] + ($this->query->permanentOnly ? ['permanent_only' => '1'] : [])
        );
    }

    protected function showToc(string $toc) : string {
        return sprintf('<abbr title="%s">%s</abbr>', html(get_all_tocs()[$toc] ?? ''), html($toc));
    }

    protected function showFacilities(ServiceCall $service_call) : string {
        return $service_call->mode->showIcon() . $service_call->serviceProperty->showIcons();
    }

    protected function getReverseDirectionLink() : string {
        return (new BoardQuery(
            $this->query->arrivalMode
            , $this->query->filter
            , $this->query->station
            , $this->query->date
            , null
            , null
            , $this->query->permanentOnly
        ))->getUrl();
    }
}