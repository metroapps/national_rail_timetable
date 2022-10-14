<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use DateTimeImmutable;
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
    ) {
        parent::__construct($streamFactory, $boardUrl, $stations);
    }

    public function getTitle() : string {
        return sprintf(
            'Departures at %s %s %s'
            , $this->station->name 
            , $this->destination !== null 
                ? ' to ' . $this->destination->name
                : ''
            , $this->now ? 'now' : 'from ' . $this->boardTime->format('Y-m-d H:i')
        );
    }

    public function getHeading() : string {
        $result = 'Departures at ' . $this->getNameAndCrs($this->station);

        if ($this->destination !== null) {
            $result .= ' calling at ' . $this->getNameAndCrs($this->destination);
        }
        $result .= ' ';
        $result .= $this->now ? 'now' : $this->boardTime->format('Y-m-d H:i');
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
                'connecting_time' => $fixed_link->getArrivalTime($this->fixedLinkDepartureTime)->format('c'),
                'permanent_only' => $this->permanentOnly,
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
        ];
    }

    public function getArrivalLink(ServiceCall $service_call) {
        return $this->boardUrl . '?' . http_build_query(
            [
                'station' => $service_call->call->location->crsCode,
                'from' => $service_call->timestamp->format('c'),
                'connecting_time' => $service_call->timestamp->format('c'),
                'connecting_toc' => $service_call->toc,
                'permanent_only' => $this->permanentOnly ?? ''
            ]
        );
    }

    public function getTocSpan(string $toc) : string {
        return sprintf('<abbr title="%s">%s</abbr>', html(get_all_tocs()[$toc] ?? ''), html($toc));
    }
}