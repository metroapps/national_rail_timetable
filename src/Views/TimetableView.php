<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;
use function array_map;
use function implode;
use function sprintf;

class TimetableView extends PhpTemplate {

    /**
     * @param StreamFactoryInterface $streamFactory
     * @param LocationWithCrs $station
     * @param Date $date
     * @param DepartureBoard[] $boards
     * @param BoardQuery $query
     * @param LocationWithCrs[] $stations
     * @param string|null $errorMessage
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly LocationWithCrs $station
        , protected readonly Date $date
        , protected readonly array $boards
        , protected readonly BoardQuery $query
        , protected readonly array $stations
        , protected readonly ?string $errorMessage = null
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate(): string {
        return __DIR__ . '/../../resource/templates/timetable.phtml';
    }

    protected function getTitle() : string {
        return sprintf(
            '%s at %s %s %s'
            , $this->query->arrivalMode ? 'Arrivals' : 'Departures'
            , $this->query->station->name
            , $this->query->filter !== []
            ? ($this->query->arrivalMode ? ' from ' : ' to ') . implode(
                ', '
                , array_map(static fn(Location $location) => $location->name, $this->query->filter)
            )
            : ''
            , $this->query->date === null ? 'today' : 'on ' . $this->date
        );
    }
}