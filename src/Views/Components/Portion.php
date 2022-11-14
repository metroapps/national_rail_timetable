<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views\Components;

use DateInterval;
use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DatedService;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\Points\HasArrival;
use Miklcct\NationalRailTimetable\Models\Points\HasDeparture;
use Miklcct\NationalRailTimetable\Models\Points\TimingPoint;
use Miklcct\NationalRailTimetable\Models\Service;
use Miklcct\NationalRailTimetable\Models\Station;
use Miklcct\NationalRailTimetable\Views\ViewMode;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use function Miklcct\NationalRailTimetable\Views\show_time;

class Portion extends PhpTemplate {

    /**
     * @param StreamFactoryInterface $streamFactory
     * @param Date $dateFromOrigin
     * @param DatedService $datedService
     * @param TimingPoint[] $points
     * @param bool $permanentOnly
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly Date $dateFromOrigin
        , protected readonly DatedService $datedService
        , protected readonly array $points
        , protected readonly bool $permanentOnly
        , protected readonly ViewMode $fromViewMode
    ) {
        parent::__construct($streamFactory);
        $line = [];
        foreach ($points as $point) {
            if ($point instanceof TimingPoint && $point->location instanceof Station) {
                $line[] = [$point->location->easting, $point->location->northing];
            }
        }
        $this->line = $line;
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../../resource/templates/portion.phtml';
    }

    protected function showTime(TimingPoint $point, bool $departure_to_arrival_board) : string {
        $time = $departure_to_arrival_board
            ? ($point instanceof HasDeparture ? $point->getPublicDeparture() : null)
            : ($point instanceof HasArrival ? $point->getPublicArrival() : null);
        if ($time === null) {
            return '';
        }
        return show_time(
            $this->datedService->date->toDateTimeImmutable($time)
            , $this->dateFromOrigin
            , $point->location instanceof LocationWithCrs
            ? $this->getBoardLink(
                $this->datedService->date->toDateTimeImmutable($time)
                , $point->location
                , $departure_to_arrival_board
            )
            : null
        );
    }

    private function getBoardLink(DateTimeImmutable $timestamp, LocationWithCrs $location, bool $arrival_mode) : ?string {
        $service = $this->datedService->service;
        if (!$service instanceof Service) {
            throw new RuntimeException('Service must be a service.');
        }
        return (
            new BoardQuery(
                $arrival_mode
                , $location
                , []
                , []
                , Date::fromDateTimeInterface($timestamp->sub(new DateInterval($arrival_mode ? 'PT4H30M' : 'P0D')))
                , $timestamp
                , $service->toc
                , $this->permanentOnly
            )
        )->getUrl($this->fromViewMode->getUrl());
    }

    /** @var int[][] */
    protected readonly array $line;
}