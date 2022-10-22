<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Safe\DateTimeImmutable;
use function http_build_query;

class TimetableQuery {
    use QueryTrait;

    public const TIMETABLE_URL = '/timetable.php';

    /**
     * @param bool $arrivalMode
     * @param LocationWithCrs|null $station
     * @param LocationWithCrs[] $filter
     * @param Date|null $date
     * @param bool $permanentOnly
     */
    public function __construct(
        public readonly bool $arrivalMode
        , public readonly ?LocationWithCrs $station
        , public readonly array $filter
        , public readonly ?Date $date
        , public readonly bool $permanentOnly
    ) {}

    public static function fromArray(array $query, LocationRepositoryInterface $location_repository) : static {
        return new static(
            ($query['mode'] ?? '') === 'arrivals'
            , empty($query['station']) ? null : static::getQueryStation($query['station'], $location_repository)
            , array_map(
                static fn(string $string) => static::getQueryStation($string, $location_repository)
                , array_filter((array)($query['filter'] ?? []))
            )
            , empty($query['date']) ? null : Date::fromDateTimeInterface(new DateTimeImmutable($query['date']))
            , !empty($query['permanent_only'])
        );
    }

    public function toArray() : array {
        return [
            'mode' => $this->arrivalMode ? 'arrivals' : 'departures',
            'station' => $this->station->getCrsCode(),
            'filter' => array_map(
                static fn(LocationWithCrs $location) => $location->getCrsCode()
                , $this->filter
            ),
            'date' => $this->date?->__toString() ?? '',
        ] + ($this->permanentOnly ? ['permanent_only' => '1'] : []);
    }

    public function getUrl() : string {
        return self::TIMETABLE_URL . '?' . http_build_query($this->toArray());
    }
}