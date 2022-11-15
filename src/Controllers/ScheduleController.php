<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Config\Config;
use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Middlewares\CacheMiddleware;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\FixedLink;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
use Miklcct\RailOpenTimetableData\Models\Station;
use Miklcct\RailOpenTimetableData\Repositories\FixedLinkRepositoryInterface;
use Miklcct\RailOpenTimetableData\Repositories\LocationRepositoryInterface;
use Miklcct\RailOpenTimetableData\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\NationalRailTimetable\Views\ScheduleFormView;
use Miklcct\NationalRailTimetable\Views\ScheduleView;
use Miklcct\NationalRailTimetable\Views\ViewMode;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Miklcct\ThinPhpApp\View\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\StatusCode\Http;
use Teapot\StatusCode\WebDAV;
use function array_map;
use function in_array;
use function usort;

abstract class ScheduleController extends Application {
    public function __construct(
        protected readonly ViewResponseFactoryInterface $viewResponseFactory
        , protected readonly StreamFactoryInterface $streamFactory
        , private readonly CacheMiddleware $cacheMiddleware
        , protected readonly ServiceRepositoryFactoryInterface $serviceRepositoryFactory
        , protected readonly LocationRepositoryInterface $locationRepository
        , protected readonly FixedLinkRepositoryInterface $fixedLinkRepository
        , private readonly Config $config
    ) {}
    abstract protected function getInnerView() : View;
    abstract protected function getViewMode() : ViewMode;

    public function getQuery() : BoardQuery {
        return $this->query;
    }

    protected function getMiddlewares() : array {
        return array_merge(parent::getMiddlewares(), [$this->cacheMiddleware]);
    }

    protected function run(ServerRequestInterface $request) : ResponseInterface {
        try {
            $this->query = BoardQuery::fromArray($request->getQueryParams(), $this->locationRepository);
        } catch (StationNotFound $e) {
            return $this->createEmptyFormResponse($e);
        }

        $this->cacheMiddleware->query = $this->query;
        if ($this->query->station === null) {
            return $this->createEmptyFormResponse(null);
        }


        $date = $query->date ?? Date::today();
        $service_repository = ($this->serviceRepositoryFactory)($this->query->permanentOnly);
        return ($this->viewResponseFactory)(
            new ScheduleView(
                $this->streamFactory
                , $this->locationRepository->getAllStations()
                , $date
                , $this->query
                , $this->getFixedLinks()
                , $service_repository->getGeneratedDate()
                , $this->config->siteName
                , $this->getInnerView()
            )
        );
    }

    protected function getFixedLinks() : array {
        $station = $this->query->station;
        if (!$station instanceof Station) {
            return [];
        }
        /** @var FixedLink[] $fixed_links */
        $fixed_links = [];
        $fixed_link_departure_time = $this->query->getFixedLinkDepartureTime();
        $arrival_mode = $this->query->arrivalMode;
        $destinations = $this->query->filter;
        $date = $this->query->date ?? Date::today();
        foreach ($this->fixedLinkRepository->get($arrival_mode ? null : $station->crsCode, $arrival_mode ? $station->crsCode : null) as $fixed_link) {
            if (
                $destinations === [] || in_array(
                    $arrival_mode ? $fixed_link->origin->crsCode : $fixed_link->destination->crsCode
                    , array_map(
                        static fn(LocationWithCrs $destination) => $destination->getCrsCode()
                        , $destinations
                    )
                    , true
                )
            ) {
                if ($fixed_link_departure_time !== null) {
                    $arrival_time = $fixed_link->getArrivalTime($fixed_link_departure_time, $arrival_mode);
                    $existing = $fixed_links[$arrival_mode ? $fixed_link->origin->crsCode : $fixed_link->destination->crsCode] ?? null;
                    if (
                        $arrival_time !== null
                        && (
                            !$existing
                            || ($arrival_mode ? $arrival_time > $existing->getArrivalTime($fixed_link_departure_time, true)
                                : $arrival_time < $existing->getArrivalTime($fixed_link_departure_time))
                            || $arrival_time == $existing->getArrivalTime($fixed_link_departure_time, $arrival_mode)
                            && $fixed_link->priority > $existing->priority
                        )
                    ) {
                        $fixed_links[$arrival_mode ? $fixed_link->origin->crsCode : $fixed_link->destination->crsCode] = $fixed_link;
                    }
                } elseif ($fixed_link->isActiveOnDate($date)) {
                    $fixed_links[] = $fixed_link;
                }
            }
        }

        usort(
            $fixed_links
            , static fn(FixedLink $a, FixedLink $b) => $a->origin->crsCode === $b->origin->crsCode
                ? $a->destination->crsCode === $b->destination->crsCode
                    ? $a->startTime->toHalfMinutes() <=> $b->startTime->toHalfMinutes()
                    : $a->destination->crsCode <=> $b->destination->crsCode
                : $a->origin->crsCode <=> $b->origin->crsCode
        );
        return $fixed_links;
    }

    private function createEmptyFormResponse(?StationNotFound $e) : ResponseInterface {
        return ($this->viewResponseFactory)(
            new ScheduleFormView(
                $this->streamFactory
                , $this->locationRepository->getAllStations()
                , $this->getViewMode()
                , $this->config->siteName
                , ($this->serviceRepositoryFactory)()->getGeneratedDate()
                , $e?->getMessage()
            )
        )->withStatus($e ? WebDAV::UNPROCESSABLE_ENTITY : Http::OK);
    }

    private BoardQuery $query;
}