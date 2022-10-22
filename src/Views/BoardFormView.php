<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class BoardFormView extends PhpTemplate {

    /**
     * @param StreamFactoryInterface $streamFactory
     * @param LocationWithCrs[] $stations
     * @param string|null $errorMessage
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly array $stations
        , protected readonly ?string $errorMessage = null
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