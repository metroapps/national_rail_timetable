<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views\Components;
use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Views\ViewMode;
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