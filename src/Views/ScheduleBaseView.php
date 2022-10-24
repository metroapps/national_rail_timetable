<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

abstract class ScheduleBaseView extends PhpTemplate {

    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly array $stations
    ) {
        parent::__construct($streamFactory);
    }

    public function getUrl() : string {
        return $this->getViewMode() === ViewMode::TIMETABLE ? TimetableView::URL : BoardView::URL;
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/schedule.phtml';
    }

    abstract public function getViewMode() : ViewMode;

    abstract protected function getTitle() : string;
}