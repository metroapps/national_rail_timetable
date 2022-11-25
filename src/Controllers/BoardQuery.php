<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Controllers;

use DateInterval;
use DateTimeImmutable;
use Metroapps\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
use Miklcct\RailOpenTimetableData\Models\Station;
use Miklcct\RailOpenTimetableData\Repositories\LocationRepositoryInterface;
use function array_filter;
use function array_map;

class BoardQuery {
    /**
     * @param bool $arrivalMode
     * @param LocationWithCrs|null $station
     * @param LocationWithCrs[] $filter
     * @param LocationWithCrs[] $inverseFilter
     * @param Date|null $date
     * @param DateTimeImmutable|null $connectingTime
     * @param string|null $connectingToc
     * @param bool $permanentOnly
     */
    final public function __construct(
        public readonly bool $arrivalMode = false
        , public readonly ?LocationWithCrs $station = null
        , public readonly array $filter = []
        , public readonly array $inverseFilter = []
        , public readonly ?Date $date = null
        , public readonly ?DateTimeImmutable $connectingTime = null
        , public readonly ?string $connectingToc = null
        , public readonly bool $permanentOnly = false
    ) {}

    public static function fromArray(array $query, LocationRepositoryInterface $location_repository) : static {
        return new static(
            ($query['mode'] ?? '') === 'arrivals'
            , empty($query['station']) ? null : static::getQueryStation($query['station'], $location_repository)
            , array_map(
                static fn(string $string) => static::getQueryStation($string, $location_repository)
                , array_values(array_filter((array)($query['filter'] ?? [])))
            )
            , array_map(
                static fn(string $string) => static::getQueryStation($string, $location_repository)
                , array_values(array_filter((array)($query['inverse_filter'] ?? [])))
            )
            , empty($query['date']) ? null : Date::fromDateTimeInterface(new \Safe\DateTimeImmutable($query['date']))
            , empty($query['connecting_time']) ? null : new \Safe\DateTimeImmutable($query['connecting_time'])
            , $query['connecting_toc'] ?? '' ?: null
            , !empty($query['permanent_only'])
        );
    }

    public function toArray() : array {
        return [
            'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
            'station' => $this->station?->getCrsCode(),
            'filter' => array_map(
                static fn(LocationWithCrs $location) => $location->getCrsCode()
                , $this->filter
            ),
            'inverse_filter' => array_map(
                static fn(LocationWithCrs $location) => $location->getCrsCode()
                , $this->inverseFilter
            ),
            'date' => $this->date?->__toString() ?? '',
            'connecting_time' => substr($this->connectingTime?->format('c') ?? '', 0, 16),
            'connecting_toc' => $this->connectingToc ?? '',
        ] + ($this->permanentOnly ? ['permanent_only' => '1'] : []);
    }

    public function getUrl(string $base_url) : string {
        return $base_url . '?' . http_build_query($this->toArray());
    }

    public function getFixedLinkDepartureTime() : ?DateTimeImmutable {
        return isset($this->connectingTime) && $this->station instanceof Station
            ? $this->arrivalMode
                ? $this->connectingTime->sub(
                    new DateInterval(sprintf('PT%dM', $this->station->minimumConnectionTime))
                )
                : $this->connectingTime->add(
                    new DateInterval(sprintf('PT%dM', $this->station->minimumConnectionTime))
                )
            : null;
    }

    private static function getQueryStation(string $name_or_crs, LocationRepositoryInterface $location_repository) : ?LocationWithCrs {
        if ($name_or_crs === '') {
            return null;
        }
        $station = $location_repository->getLocationByCrs($name_or_crs)
            ?? $location_repository->getLocationByName($name_or_crs);
        if (!$station instanceof LocationWithCrs) {
            throw new StationNotFound($name_or_crs);
        }
        return $station;
    }
}