<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views\Components;

use DateInterval;
use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\FixedLink as Model;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class FixedLink extends PhpTemplate {
    /**
     * @param StreamFactoryInterface $streamFactory
     * @param Model[] $fixedLinks
     * @param BoardQuery $query
     * @param Date $date
     * @param string $baseUrl
     */
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly array $fixedLinks
        , protected readonly BoardQuery $query
        , protected readonly Date $date
        , protected readonly string $baseUrl
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../../resource/templates/fixed_link.phtml';
    }

    protected function getUrl(Model $fixed_link, ?DateTimeImmutable $departure_time) : string {
        return (
        new BoardQuery(
            $this->query->arrivalMode
            , $this->query->arrivalMode ? $fixed_link->origin : $fixed_link->destination
            , []
            , []
            , $this->query->connectingTime !== null
                ? Date::fromDateTimeInterface(
                    $this->query->connectingTime->sub(new DateInterval($this->query->arrivalMode ? 'PT4H30M' : 'P0D'))
                )
                : $this->query->date ?? Date::today()
            , $departure_time === null
                ? null
                : $fixed_link->getArrivalTime($departure_time, $this->query->arrivalMode)
            , null
            , $this->query->permanentOnly
        )
        )->getUrl($this->baseUrl);
    }
}