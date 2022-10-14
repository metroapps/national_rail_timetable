<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Enums\Catering;
use Miklcct\NationalRailTimetable\Enums\Mode;
use Miklcct\NationalRailTimetable\Enums\Reservation;
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

    public function getFixedLinkUrl(FixedLink $fixed_link) {
        return $this->boardUrl . '?' . http_build_query(
            [
                'station' => $fixed_link->destination->crsCode,
                'from' => ($this->connectingTime ?? $this->boardTime)->format('c'),
                'connecting_time' => $fixed_link->getArrivalTime($this->fixedLinkDepartureTime, $this->arrivalMode)->format('c'),
                'permanent_only' => $this->permanentOnly,
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
            'permanent_only' => $this->permanentOnly,
            'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
        ];
    }

    public function getArrivalLink(ServiceCall $service_call) {
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

    public function showToc(string $toc) : string {
        return sprintf('<abbr title="%s">%s</abbr>', html(get_all_tocs()[$toc] ?? ''), html($toc));
    }

    public function showFacilities(ServiceCall $service_call) : string {
        $result = match($service_call->mode) {
            Mode::BUS => '<img src="/images/bus.png" alt="bus" title="Bus service" /><br/>',
            Mode::SHIP => '<img src="/images/ship.png" alt="ship" title="Ferry service" /><br/>',
            default => '',
        };
        foreach ($service_call->serviceProperty->caterings as $catering) {
            $result .= match($catering) {
                Catering::BUFFET => '<img src="/images/buffer.png" alt="buffet" title="Buffet" />',
                Catering::FIRST_CLASS_RESTAURANT => '<img src="/images/first_class_restaurant.png" alt="first class restaurant" title="Restaurant for first class passengers" />',
                Catering::HOT_FOOD => '<img src="/images/first_class_restaurant.png" alt="hot food" title="Hot food" />',
                Catering::RESTAURANT => '<img src="/images/restaurant.png" alt="restaurant" title="Restaurant" />',
                Catering::TROLLEY => '<img src="/images/trolley.png" alt="restaurant" title="Trolley" />',
                default => '',
            };
        }
        $result .= match ($service_call->serviceProperty->reservation) {
            Reservation::AVAILABLE => '<img src="/images/reservation_available.png" alt="reservation available" title="Reservation available" />',
            Reservation::RECOMMENDED => '<img src="/images/reservation_recommended.png" alt="reservation recommended" title="Reservation recommended" />',
            Reservation::COMPULSORY => '<img src="/images/reservation_compulsory.png" alt="reservation compulsory" title="Reservation compulsory" />',
            default => '',
        };
        if ($service_call->serviceProperty->seatingClasses[1]) {
            $result .= '<img src="/images/first_class.png" alt="first class" title="First class available" />';
        }
        if (array_filter($service_call->serviceProperty->sleeperClasses)) {
            $result .= '<img src="/images/sleeper.png" alt="sleeper" title="Sleeper available" />';
        }
        return $result;
    }
}