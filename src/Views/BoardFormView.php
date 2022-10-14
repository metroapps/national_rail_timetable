<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Views;

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

    public function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/board.phtml';
    }

    public function getTitle() : string {
        return 'Departure board';
    }

    public function getFormData() : array {
        return [];
    }
}