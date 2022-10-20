<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\HttpException;

class BoardFormView extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly string $boardUrl
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