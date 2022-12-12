<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Views;

use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
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
        , string $siteName
        , protected readonly Date $generated
        , protected readonly ?string $errorMessage = null
    ) {
        parent::__construct($streamFactory, $stations, $siteName);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/schedule_form.phtml';
    }

    protected function getTitle() : string {
        return $this->siteName;
    }

    public function getViewMode() : ViewMode {
        return $this->viewMode;
    }
}