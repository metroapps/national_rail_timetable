<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views\Components;

use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class AllPortions extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly Date $dateFromOrigin
        , protected readonly array $portions
        , protected readonly bool $permanentOnly
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../../resource/templates/all_portions.phtml';
    }
}