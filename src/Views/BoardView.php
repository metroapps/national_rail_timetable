<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Enums\Catering;
use Miklcct\NationalRailTimetable\Enums\Mode;
use Miklcct\NationalRailTimetable\Enums\Reservation;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\FixedLink;
use Miklcct\NationalRailTimetable\Models\ServiceCall;
use Psr\Http\Message\StreamFactoryInterface;

use function Miklcct\NationalRailTimetable\get_all_tocs;
use function Miklcct\ThinPhpApp\Escaper\html;

class BoardView extends BoardFormView {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , string $boardUrl
        , array $stations
        , protected readonly DepartureBoard $board
        , protected readonly DateTimeImmutable $boardTime
        , protected readonly ?DateTimeImmutable $connectingTime
        , protected readonly ?string $connectingToc
        , protected readonly Location $station
        , protected readonly ?Location $destination
        , protected readonly ?array $fixedLinks
        , protected readonly ?DateTimeImmutable $fixedLinkDepartureTime
        , protected readonly bool $permanentOnly
        , protected readonly bool $now
        , protected readonly bool $arrivalMode
    ) {
        parent::__construct($streamFactory, $boardUrl, $stations);
    }

    public function getTitle() : string {
        return sprintf(
            '%s at %s %s %s'
            , $this->arrivalMode ? 'Arrivals' : 'Departures'
            , $this->station->name 
            , $this->destination !== null 
                ? ' to ' . $this->destination->name
                : ''
            , $this->now ? 'now' : 'from ' . $this->boardTime->format('Y-m-d H:i')
        );
    }

    public function getHeading() : string {
        $result = ($this->arrivalMode ? 'Arrivals at ' : 'Departures at ') . $this->getNameAndCrs($this->station);

        if ($this->destination !== null) {
            $result .= ' calling at ' . $this->getNameAndCrs($this->destination);
        }
        $result .= ' ';
        $result .= $this->now ? 'now' : 'from ' . $this->boardTime->format('Y-m-d H:i');
        return $result;
    }

    public function getNameAndCrs(Location $location) : string {
        if ($location->crsCode === null) {
            return $location->name;
        }
        return sprintf('%s (%s)', $location->name, $location->crsCode);
    }

    public function getFixedLinkUrl(FixedLink $fixed_link, DateTimeImmutable $departure_time) {
        return $this->boardUrl . '?' . http_build_query(
            [
                'station' => $fixed_link->destination->crsCode,
                'from' => ($this->connectingTime ?? $this->boardTime)->format('c'),
                'connecting_time' => $fixed_link->getArrivalTime($departure_time, $this->arrivalMode)->format('c'),
                'permanent_only' => (string)$this->permanentOnly,
                'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
            ]
        );
    }

    public function getFormData(): array {
        return [
            'station' => $this->station->crsCode,
            'filter' => $this->destination?->crsCode,
            'from' => $this->now ? '' : substr($this->boardTime->format('c') ?? '', 0, 16),
            'connecting_time' => substr($this->connectingTime?->format('c') ?? '', 0, 16),
            'connecting_toc' => $this->connectingToc,
            'permanent_only' => (string)$this->permanentOnly,
            'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
        ];
    }

    public function getArrivalLink(ServiceCall $service_call) : ?string {
        if ($service_call->call->location->crsCode === null) {
            return null;
        }
        return $this->boardUrl . '?' . http_build_query(
            [
                'station' => $service_call->call->location->crsCode,
                'from' => $service_call->timestamp->format('c'),
                'connecting_time' => $service_call->timestamp->format('c'),
                'connecting_toc' => $service_call->toc,
                'permanent_only' => $this->permanentOnly ?? '',
                'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
            ]
        );
    }

    public function getServiceLink(ServiceCall $service_call) {
         return '/service.php?' . http_build_query(
            [
                'uid' => $service_call->uid,
                'date' => $service_call->date->__toString(),
                'permanent_only' => (string)$this->permanentOnly,
            ]
        );
    }

    public function showToc(string $toc) : string {
        return sprintf('<abbr title="%s">%s</abbr>', html(get_all_tocs()[$toc] ?? ''), html($toc));
    }

    public function showFacilities(ServiceCall $service_call) : string {
        return $service_call->mode->showIcon() . $service_call->serviceProperty->showIcons();
    }
}