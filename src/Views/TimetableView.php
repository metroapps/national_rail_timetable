<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Controllers\TimetableQuery;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class TimetableView extends PhpTemplate {

    /**
     * @param StreamFactoryInterface $streamFactory
     * @param LocationWithCrs $station
     * @param Date $date
     * @param DepartureBoard[] $boards
     * @param TimetableQuery $query
     * @param LocationWithCrs[] $stations
     * @param string|null $errorMessage
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly LocationWithCrs $station
        , protected readonly Date $date
        , protected readonly array $boards
        , protected readonly TimetableQuery $query
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
            "%s timetable for %s %s",
            $this->query->arrivalMode ? 'Arrivals' : 'Departures',
            $this->station->name,
            $this->query->date ? 'on ' . $this->query->date : 'today'
        );
    }
}