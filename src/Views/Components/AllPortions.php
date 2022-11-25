<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Views\Components;

use Miklcct\RailOpenTimetableData\Models\Date;
use Metroapps\NationalRailTimetable\Views\ViewMode;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class AllPortions extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly Date $dateFromOrigin
        , protected readonly array $portions
        , protected readonly bool $permanentOnly
        , protected readonly ViewMode $fromViewMode
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../../resource/templates/all_portions.phtml';
    }
}