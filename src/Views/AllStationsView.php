<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Views;

use Miklcct\RailOpenTimetableData\Models\Station;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class AllStationsView extends PhpTemplate {

    /**
     * @param StreamFactoryInterface $streamFactory
     * @param Station[] $stations
     */
    public function __construct(StreamFactoryInterface $streamFactory, protected readonly array $stations) {
        parent::__construct($streamFactory);
    }
    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/stations.phtml';
    }
}