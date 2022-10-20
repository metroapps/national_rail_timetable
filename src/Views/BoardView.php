<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use DateInterval;
use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Models\Date;
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
        , protected readonly Date $boardDate
        , protected readonly ?DateTimeImmutable $connectingTime
        , protected readonly ?string $connectingToc
        , protected readonly Location $station
        , protected readonly ?Location $destination
        , protected readonly ?array $fixedLinks
        , protected readonly ?DateTimeImmutable $fixedLinkDepartureTime
        , protected readonly bool $permanentOnly
        , protected readonly bool $now
        , protected readonly bool $arrivalMode
        , protected readonly ?Date $generated
    ) {
        parent::__construct($streamFactory, $boardUrl, $stations);
    }

    protected function getTitle() : string {
        return sprintf(
            '%s at %s %s %s'
            , $this->arrivalMode ? 'Arrivals' : 'Departures'
            , $this->station->name 
            , $this->destination !== null 
                ? ' to ' . $this->destination->name
                : ''
            , $this->now ? 'today' : 'on ' . $this->boardDate
        );
    }

    protected function getHeading() : string {
        $result = ($this->arrivalMode ? 'Arrivals at ' : 'Departures at ') . $this->getNameAndCrs($this->station);

        if ($this->destination !== null) {
            $result .= ' calling at ' . $this->getNameAndCrs($this->destination);
        }
        $result .= ' ';
        $result .= $this->now ? 'today' : 'on ' . $this->boardDate;
        return $result;
    }

    protected function getNameAndCrs(Location $location) : string {
        if ($location->crsCode === null) {
            return $location->name;
        }
        return sprintf('%s (%s)', $location->name, $location->crsCode);
    }

    protected function getFixedLinkUrl(FixedLink $fixed_link, ?DateTimeImmutable $departure_time) {
        return $this->boardUrl . '?' . http_build_query(
            [
                'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
                'station' => $fixed_link->destination->crsCode,
                'filter' => '',
                'date' => ($this->connectingTime !== null ? Date::fromDateTimeInterface($this->connectingTime->sub(new DateInterval($this->arrivalMode ? 'PT4H30M' : 'P0D'))) : $this->boardDate)->__toString(),
                'connecting_time' => $departure_time === null ? '' : substr($fixed_link->getArrivalTime($departure_time, $this->arrivalMode)->format('c'), 0, 16),
                'connecting_toc' => '',
            ] + ($this->permanentOnly ? ['permanent_only' => '1'] : [])
        );
    }

    protected function getFormData(): array {
        return [
            'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
            'station' => $this->station->crsCode,
            'filter' => $this->destination?->crsCode ?? '',
            'date' => $this->now ? '' : $this->boardDate->__toString(),
            'connecting_time' => substr($this->connectingTime?->format('c') ?? '', 0, 16),
            'connecting_toc' => $this->connectingToc,
        ] + ($this->permanentOnly ? ['permanent_only' => '1'] : []);
    }

    protected function getArrivalLink(ServiceCall $service_call) : ?string {
        if ($service_call->call->location->crsCode === null) {
            return null;
        }
        return $this->boardUrl . '?' . http_build_query(
            [
                'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
                'station' => $service_call->call->location->crsCode,
                'filter' => '',
                'date' => $service_call->timestamp->sub(new DateInterval($this->arrivalMode ? 'PT4H30M' : 'P0D'))->format('Y-m-d'),
                'connecting_time' => substr($service_call->timestamp->format('c'), 0, 16),
                'connecting_toc' => $service_call->toc
            ] + ($this->permanentOnly ? ['permanent_only' => '1'] : [])
        );
    }

    protected function getDayOffsetLink(int $days) : string {
        return $this->boardUrl . '?' . http_build_query(
            [
                'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
                'station' => $this->station->crsCode,
                'filter' => $this->destination?->crsCode ?? '',
                'date' => $this->boardDate->addDays($days)->__toString(), 
                'connecting_time' => substr($this->connectingTime?->format('c') ?? '', 0, 16),
                'connecting_toc' => $this->connectingToc,
            ] + ($this->permanentOnly ? ['permanent_only' => '1'] : [])
        );
    }

    protected function getServiceLink(ServiceCall $service_call) {
         return '/service.php?' . http_build_query(
            [
                'uid' => $service_call->uid,
                'date' => $service_call->date->__toString(),
            ] + ($this->permanentOnly ? ['permanent_only' => '1'] : [])
        );
    }

    protected function showToc(string $toc) : string {
        return sprintf('<abbr title="%s">%s</abbr>', html(get_all_tocs()[$toc] ?? ''), html($toc));
    }

    protected function showFacilities(ServiceCall $service_call) : string {
        return $service_call->mode->showIcon() . $service_call->serviceProperty->showIcons();
    }

    protected function getReverseDirectionLink() : string {
        return $this->boardUrl . '?' . http_build_query(
            [
                'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
                'station' => $this->destination->crsCode,
                'filter' => $this->station->crsCode,
                'date' => $this->now ? '' : $this->boardDate->__toString(), 
                'connecting_time' => '',
                'connecting_toc' => '',
            ] + ($this->permanentOnly ? ['permanent_only' => '1'] : [])
        );
    }
}