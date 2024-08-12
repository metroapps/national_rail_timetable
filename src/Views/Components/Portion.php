<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Views\Components;

use DateInterval;
use DateTimeImmutable;
use Metroapps\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\DatedService;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
use Miklcct\RailOpenTimetableData\Models\Points\HasArrival;
use Miklcct\RailOpenTimetableData\Models\Points\HasDeparture;
use Miklcct\RailOpenTimetableData\Models\Points\TimingPoint;
use Miklcct\RailOpenTimetableData\Models\Service;
use Miklcct\RailOpenTimetableData\Models\Station;
use Metroapps\NationalRailTimetable\Views\ViewMode;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use function Metroapps\NationalRailTimetable\Views\show_time;

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
        $this->tiplocData = self::getTiplocData();
        foreach ($points as $point) {
            if ($point instanceof TimingPoint) {
                $tiploc = $point->location->tiploc;
                $tiploc_row = $this->tiplocData[$tiploc] ?? null;
                if ($tiploc_row !== null) {
                    $line[] = [$tiploc_row["easting"], $tiploc_row["northing"]];
                } elseif ($point->location instanceof Station) {
                    $line[] = [$point->location->easting, $point->location->northing];
                }
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
            $this->datedService->date->toDateTimeImmutable($time, $this->datedService->getAbsoluteTimeZone())
            , $this->dateFromOrigin
            , $point->location instanceof LocationWithCrs
            ? $this->getBoardLink(
                $this->datedService->date->toDateTimeImmutable($time, $this->datedService->getAbsoluteTimeZone())
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

    private static function getTiplocData() : array {
        $csv = array_map("str_getcsv", file(__DIR__ . '/../../../resource/tiplocs-merged.csv', FILE_SKIP_EMPTY_LINES));
        $keys = array_shift($csv);
        foreach ($csv as $i=>$row) {
            $combined = array_combine($keys, $row);
            settype($combined["stop_lon"], "float");
            settype($combined["stop_lat"], "float");
            settype($combined["easting"], "int");
            settype($combined["northing"], "int");
            $csv[$combined["stop_id"]] = $combined;
        }
        return $csv;
    }

    protected readonly array $tiplocData;

    /** @var int[][] */
    protected readonly array $line;
}
