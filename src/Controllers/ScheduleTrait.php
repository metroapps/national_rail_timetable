<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use DateTimeZone;
use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\FixedLink;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\Station;
use Miklcct\NationalRailTimetable\Repositories\FixedLinkRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Safe\DateTimeImmutable as SafeDateTimeImmutable;
use Teapot\StatusCode\Http;
use function str_replace;

trait ScheduleTrait {
    abstract private function runWithoutCache(ServerRequestInterface $request, BoardQuery $query) : ResponseInterface;
    abstract private function createStationNotFoundResponse(StationNotFound $e) : ResponseInterface;
    private readonly FixedLinkRepositoryInterface $fixedLinkRepository;
    private function getFixedLinks(BoardQuery $query) : array {
        $station = $query->station;
        if (!$station instanceof Station) {
            return [];
        }
        /** @var FixedLink[] $fixed_links */
        $fixed_links = [];
        $fixed_link_departure_time = $query->getFixedLinkDepartureTime();
        $arrival_mode = $query->arrivalMode;
        $destinations = $query->filter;
        $date = $query->date ?? Date::today();
        foreach ($this->fixedLinkRepository->get($station->crsCode, null) as $fixed_link) {
            if ($fixed_link_departure_time !== null) {
                $arrival_time = $fixed_link->getArrivalTime($fixed_link_departure_time, $arrival_mode);
                $existing = $fixed_links[$fixed_link->destination->crsCode] ?? null;
                if (
                    ($destinations === []
                        || in_array(
                            $fixed_link->destination->crsCode,
                            array_map(static fn(LocationWithCrs $destination) => $destination->getCrsCode(),
                                $destinations),
                            true
                        ))
                    && $arrival_time !== null
                    && (
                        !$existing
                        || ($arrival_mode ? $arrival_time > $existing->getArrivalTime($fixed_link_departure_time)
                            : $arrival_time < $existing->getArrivalTime($fixed_link_departure_time))
                        || $arrival_time == $existing->getArrivalTime($fixed_link_departure_time)
                        && $fixed_link->priority > $existing->priority
                    )
                ) {
                    $fixed_links[$fixed_link->destination->crsCode] = $fixed_link;
                }
            } elseif ($fixed_link->isActiveOnDate($date)) {
                $fixed_links[] = $fixed_link;
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

    protected function run(ServerRequestInterface $request) : ResponseInterface {
        try {
            $query = BoardQuery::fromArray($request->getQueryParams(), $this->locationRepository);
        } catch (StationNotFound $e) {
            return $this->createStationNotFoundResponse($e);
        }

        return $this->addCacheHeader($this->runWithoutCache($request, $query), $query);
    }

    public function addCacheHeader(ResponseInterface $response, BoardQuery $query) : ResponseInterface {
        if ($response->getStatusCode() !== Http::OK) {
            return $response;
        }
        return $query->station === null
            ? $response->withAddedHeader('Cache-Control', 'public')->withAddedHeader('Cache-Control', 'max-age=604800')
            : ($query->date === null
                ? $response->withAddedHeader('Cache-Control', 'public')->withAddedHeader(
                    'Expires',
                    str_replace(
                        '+0000',
                        'GMT',
                        (new SafeDateTimeImmutable('tomorrow'))->setTimezone(new DateTimeZone('UTC'))->format('r')
                    )
                )
                : $response->withAddedHeader('Cache-Control', 'public')
                    ->withAddedHeader('Cache-Control', 'max-age=21600'));
    }
}