<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Views;

use DateInterval;
use DateTimeImmutable;
use LogicException;
use Metroapps\NationalRailTimetable\Controllers\BoardController;
use Metroapps\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\RailOpenTimetableData\Enums\AssociationCategory;
use Miklcct\RailOpenTimetableData\Exceptions\UnreachableException;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\DatedService;
use Miklcct\RailOpenTimetableData\Models\FullService;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
use Miklcct\RailOpenTimetableData\Models\Points\DestinationPoint;
use Miklcct\RailOpenTimetableData\Models\Service;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;
use function http_build_query;

class ServiceView extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly FullService $datedService
        , protected readonly bool $permanentOnly
        , protected readonly ?Date $generated
        , protected readonly ViewMode $fromViewMode
    ) {
        parent::__construct($streamFactory);
    }

    public static function getServiceUrl(
        string $uid
        , Date $date
        , bool $permanent_only
        , ViewMode $view_mode
    ) : string {
        return rtrim(
            "/service/$uid/$date?" . http_build_query(
                ($view_mode === ViewMode::BOARD ? ['from' => 'board'] : [])
                + ($permanent_only ? ['permanent_only' => '1'] : [])
            )
            , '?'
        );
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/service.phtml';
    }

    protected function getOriginPortion() : DatedService {
        $origin_portion = $this->datedService;
        while ($origin_portion->divideFrom !== null) {
            $origin_portion = $origin_portion->divideFrom->primaryService;
        }
        return $origin_portion;
    }

    protected function getTitle() : string {
        $service = $this->datedService->service;
        if (!$service instanceof Service) {
            throw new LogicException('The service does not run on the day.');
        }
        $origin_portion = $this->getOriginPortion();
        assert($origin_portion instanceof FullService);
        $service = $origin_portion->service;
        return sprintf(
            '%s %s %s %s to %s'
            , $origin_portion->date
            , substr($service->getOrigin()->serviceProperty->rsid, 0, 6)
            , $service->getOrigin()->getPublicOrWorkingDeparture()
            , $service->getOrigin()->location->getShortName()
            , implode(' and ', array_map(static fn(DestinationPoint $point) => $point->location->getShortName(), $origin_portion->getDestinations()))
        );
    }

    protected function getBoardLink(DateTimeImmutable $timestamp, LocationWithCrs $location, bool $arrival_mode) : ?string {
        return (
            new BoardQuery(
                $arrival_mode
                , $location
                , []
                , []
                , Date::fromDateTimeInterface($timestamp->sub(new DateInterval($arrival_mode ? 'PT4H30M' : 'P0D')))
                , $timestamp
                , $this->datedService->service->toc
                , $this->permanentOnly
            )
        )->getUrl(BoardController::URL);
    }

    protected function splitIntoPortions(DatedService $dated_service, bool $recursed = false) : array {
        if (!$recursed && $dated_service instanceof FullService) {
            // assume that a train won't be split from a previous service and merge into an afterward service
            if ($dated_service->divideFrom !== null) {
                return $this->splitIntoPortions($dated_service->divideFrom->primaryService);
            }
            if ($dated_service->joinTo !== null) {
                return $this->splitIntoPortions($dated_service->joinTo->primaryService);
            }
        }
        $result = [];
        $index = 0;
        $result[0][0]['dated_service'] = $dated_service;
        foreach ($dated_service->service->points as $point) {
            $result[$index][0][] = $point;
            $new_portion = false;
            if ($dated_service instanceof FullService) {
                foreach ($dated_service->dividesJoinsEnRoute as $dated_association) {
                    $association_point = $dated_service->service->getAssociationPoint($dated_association->association);
                    if (
                        $point->location->tiploc === $association_point->location->tiploc
                        && $point->locationSuffix
                        === $association_point->locationSuffix
                    ) {
                        if (!$new_portion) {
                            $result[++$index][0][0] = $point;
                            $result[$index][0]['dated_service'] = $dated_service;
                            $new_portion = true;
                        }
                        $other_portion = $this->splitIntoPortions($dated_association->secondaryService, true);
                        switch ($dated_association->association->category) {
                        case AssociationCategory::DIVIDE:
                            $result[$index][] = $other_portion;
                            break;
                        case AssociationCategory::JOIN:
                            $result[$index - 1][] = $other_portion;
                            break;
                        default:
                            throw new UnreachableException();
                        }
                    }
                }
            }
        }
        return $result;
    }
}