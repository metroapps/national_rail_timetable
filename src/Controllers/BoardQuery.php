<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Controllers;

use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Exceptions\StationNotFound;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;

class BoardQuery {
    public const BOARD_URL = '/index.php';

    public function __construct(
        public readonly bool $arrivalMode
        , public readonly ?LocationWithCrs $station
        , public readonly ?LocationWithCrs $filter
        , public readonly ?Date $date
        , public readonly ?DateTimeImmutable $connectingTime
        , public readonly ?string $connectingToc
        , public readonly bool $permanentOnly
    ) {}

    public static function fromArray(array $query, LocationRepositoryInterface $location_repository) : static {
        return new static(
            ($query['mode'] ?? '') === 'arrivals'
            , empty($query['station']) ? null :
                $location_repository->getLocationByCrs($query['station'])
                ?? $location_repository->getLocationByName($query['station'])
                ?? throw new StationNotFound($query['station'])
            , empty($query['filter']) ? null :
                $location_repository->getLocationByCrs($query['filter'])
                ?? $location_repository->getLocationByName($query['filter'])
                ?? throw new StationNotFound($query['filter'])
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
            'filter' => $this->filter?->getCrsCode() ?? '',
            'date' => $this->date?->__toString() ?? '',
            'connecting_time' => substr($this->connectingTime?->format('c') ?? '', 0, 16),
            'connecting_toc' => $this->connectingToc ?? '',
        ] + ($this->permanentOnly ? ['permanent_only' => '1'] : []);
    }

    public function getUrl() : string {
        return static::BOARD_URL . '?' . http_build_query($this->toArray());
    }
}