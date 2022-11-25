<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Views\Components;

use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class Footer extends PhpTemplate {
    public function __construct(StreamFactoryInterface $streamFactory, protected readonly Date $generated) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../../resource/templates/footer.phtml';
    }
}