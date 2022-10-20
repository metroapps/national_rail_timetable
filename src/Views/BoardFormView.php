<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class BoardFormView extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly string $boardUrl
        , protected readonly array $stations
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/board.phtml';
    }

    protected function getTitle() : string {
        return 'Departure board';
    }

    protected function getFormData() : array {
        return [];
    }
}