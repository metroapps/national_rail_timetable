<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views\Components;

use Miklcct\NationalRailTimetable\Models\Date;
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