<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Views\Components;
use Metroapps\NationalRailTimetable\Controllers\BoardQuery;
use Metroapps\NationalRailTimetable\Views\ViewMode;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class Form extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , public readonly array $stations
        , public readonly ViewMode $viewMode
        , public readonly ?BoardQuery $query
        , public readonly ?string $errorMessage
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../../resource/templates/form.phtml';
    }
}