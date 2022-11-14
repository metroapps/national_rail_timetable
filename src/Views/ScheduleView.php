<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use DateInterval;
use DateTimeImmutable;
use LogicException;
use Miklcct\NationalRailTimetable\Controllers\BoardController;
use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Controllers\TimetableController;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DepartureBoard;
use Miklcct\NationalRailTimetable\Models\FixedLink;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\ServiceCall;
use Miklcct\NationalRailTimetable\Views\Components\Board;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Miklcct\ThinPhpApp\View\View;
use Psr\Http\Message\StreamFactoryInterface;
use function array_map;
use function assert;
use function count;
use function implode;
use function Miklcct\NationalRailTimetable\get_all_tocs;
use function Miklcct\ThinPhpApp\Escaper\html;
use function sprintf;

class ScheduleView extends PhpTemplate {
    /**
     * @param StreamFactoryInterface $streamFactory
     * @param array $stations
     * @param DepartureBoard $board
     * @param Date $date
     * @param BoardQuery $query
     * @param FixedLink[]|null $fixedLinks
     * @param Date|null $generated
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly array $stations
        , protected readonly Date $date
        , protected readonly BoardQuery $query
        , protected readonly ?array $fixedLinks
        , protected readonly ?Date $generated
        , protected readonly string $siteName
        , protected readonly View $innerView
    ) {
        parent::__construct($streamFactory);
    }

    protected function getTitle() : string {
        return sprintf(
            '%s at %s %s %s %s%s - %s'
            , $this->query->arrivalMode ? 'Arrivals' : 'Departures'
            , $this->query->station->name
            , $this->query->filter !== []
                ? ($this->query->arrivalMode ? 'from ' : 'to ') . implode(
                    ', '
                    , array_map(static fn(Location $location) => $location->name, $this->query->filter)
                )
                : ''
            , $this->query->inverseFilter !== []
                ? 'not ' . implode(
                    ', '
                    , array_map(static fn(Location $location) => $location->name, $this->query->inverseFilter)
                )
                : ''
            , $this->query->date === null ? 'today' : 'on ' . $this->date
            , $this->query->permanentOnly ? ' (permanent timetable)' : ''
            , $this->siteName
        );
    }

    protected function getHeading() : string {
        $location = $this->query->station;
        assert($location instanceof Location);
        $result = ($this->query->arrivalMode ? 'Arrivals at ' : 'Departures at ') . $location->name;

        $filter = $this->query->filter;
        if ($filter !== []) {
            $result .= ' calling at ' . implode(
                    ', '
                    , array_map(
                        fn(Location $location) => $location->name
                        , $filter
                    )
                );
        }
        $inverse_filter = $this->query->inverseFilter;
        if ($inverse_filter !== []) {
            $result .= ' but not ' . implode(
                    ', '
                    , array_map(
                        fn(Location $location) => $location->name
                        , $inverse_filter
                    )
                );
        }
        $result .= ' on ' . $this->date;
        return $result;
    }

    protected function getReverseDirectionLink() : string {
        if (count($this->query->filter) !== 1) {
            throw new LogicException('Reversing is only allowed when filter count is exactly 1.');
        }
        return (new BoardQuery(
            $this->query->arrivalMode
            , $this->query->filter[0]
            , [$this->query->station]
            , $this->query->inverseFilter
            , $this->query->date
            , null
            , null
            , $this->query->permanentOnly
        ))->getUrl($this->getUrl());
    }

    protected function getViewMode() : ViewMode {
        return $this->innerView instanceof Board ? ViewMode::BOARD : ViewMode::TIMETABLE;
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/schedule.phtml';
    }

    protected function getUrl() : string {
        return match($this->getViewMode()) {
            ViewMode::TIMETABLE => TimetableController::URL,
            ViewMode::BOARD => BoardController::URL,
        };
    }
}