<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use DateInterval;
use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\Station;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use function array_filter;
use function array_map;

class BoardQuery {
    use QueryTrait;

    /**
     * @param bool $arrivalMode
     * @param LocationWithCrs|null $station
     * @param LocationWithCrs[] $filter
     * @param Date|null $date
     * @param DateTimeImmutable|null $connectingTime
     * @param string|null $connectingToc
     * @param bool $permanentOnly
     */
    public function __construct(
        public readonly bool $arrivalMode = false
        , public readonly ?LocationWithCrs $station = null
        , public readonly array $filter = []
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
            , empty($query['date']) ? null : Date::fromDateTimeInterface(new \Safe\DateTimeImmutable($query['date']))
            , empty($query['connecting_time']) ? null : new \Safe\DateTimeImmutable($query['connecting_time'])
            , ($query['connecting_toc'] ?? '') ?: null
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
}