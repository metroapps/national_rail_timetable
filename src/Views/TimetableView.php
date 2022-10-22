<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class TimetableView extends PhpTemplate {

    /**
     * @param StreamFactoryInterface $streamFactory
     * @param Location $station
     * @param Date $date
     * @param DepartureBoard $boards
     * @param array $filterCrs
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly Location $station
        , protected readonly Date $date
        , protected readonly array $boards
        , protected readonly array $filterCrs
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate(): string {
        return __DIR__ . '/../../resource/templates/timetable.phtml';
    }
}