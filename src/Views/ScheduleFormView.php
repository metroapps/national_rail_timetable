<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Psr\Http\Message\StreamFactoryInterface;

class ScheduleFormView extends ScheduleBaseView {
    /**
     * @param StreamFactoryInterface $streamFactory
     * @param LocationWithCrs[] $stations
     * @param string|null $errorMessage
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , array $stations
        , protected readonly ViewMode $viewMode
        , protected readonly ?string $errorMessage = null
    ) {
        parent::__construct($streamFactory, $stations);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/schedule.phtml';
    }

    protected function getTitle() : string {
        return ($this->viewMode === ViewMode::TIMETABLE ? 'Timetable' : 'Departure board')
            . ' - GBTT.uk';
    }

    protected function getStylesheets() : array {
        return [$this->viewMode === ViewMode::TIMETABLE ? '/timetable.css' : '/board.css'];
    }

    public function getViewMode() : ViewMode {
        return $this->viewMode;
    }
}