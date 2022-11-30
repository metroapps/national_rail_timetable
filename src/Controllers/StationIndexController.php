<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Controllers;

use Metroapps\NationalRailTimetable\Views\AllStationsView;
use Miklcct\RailOpenTimetableData\Models\Station;
use Miklcct\RailOpenTimetableData\Repositories\LocationRepositoryInterface;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class StationIndexController extends Application {
    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponseFactory
        , private readonly LocationRepositoryInterface $locationRepository
        , private readonly StreamFactoryInterface $streamFactory
    ) {
    }

    protected function run(ServerRequestInterface $request) : ResponseInterface {
        /** @var Station[] $stations */
        $stations = array_filter(
            $this->locationRepository->getAllStations()
            , static fn($item) => $item instanceof Station && $item->crsCode === $item->minorCrsCode
        );

        usort($stations, static fn(Station $a, Station $b) => $a->name <=> $b->name);

        return ($this->viewResponseFactory)(new AllStationsView($this->streamFactory, $stations));
    }
}