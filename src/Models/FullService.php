<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use LogicException;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationCategory;
use Miklcct\NationalRailJourneyPlanner\Models\Points\DestinationPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\OriginPoint;
use function array_filter;
use function array_merge;

class FullService extends DatedService {
    public function __construct(
        ServiceEntry $service
        , DateTimeImmutable $date
        , public readonly ?DatedAssociation $divideFrom
        , array $dividesJoinsEnRoute
        , public readonly ?DatedAssociation $joinTo
    ) {
        $this->dividesJoinsEnRoute = $dividesJoinsEnRoute;
        parent::__construct($service, $date);
    }

    /** @var DatedAssociation[] */
    public readonly array $dividesJoinsEnRoute;

    /**
     * @return OriginPoint[]
     */
    public function getAllOrigins() : array {
        if ($this->divideFrom === null) {
            $base = $this->service instanceof Service ? $this->service->getOrigin() : [];
            $portions = [];
        } else {
            $base = [];
            $portions = [$this->divideFrom->primaryService];
        }
        $portions = array_merge(
            $portions
            , array_map(
                static fn(DatedAssociation $association) => $association->secondaryService
                , array_values(
                    array_filter(
                        $this->dividesJoinsEnRoute
                        , static fn(DatedAssociation $association) =>
                            $association->associationEntry instanceof Association
                            && $association->associationEntry->category === AssociationCategory::JOIN
                    )
                )
            )
        );
        return array_merge(
            $base
            , array_map(
                static function(DatedService $portion) {
                    if (!$portion instanceof self) {
                        throw new LogicException('Listing all origins requires all services being full services.');
                    }
                    return $portion->getAllOrigins();
                }
                , $portions
            )
        );
    }

    /**
     * @return DestinationPoint[]
     */
    public function getAllDestinations() : array {
        if ($this->joinTo === null) {
            $base = $this->service instanceof Service ? [$this->service->getDestination()] : [];
            $portions = [];
        } else {
            $base = [];
            $portions = [$this->joinTo->primaryService];
        }
        $portions = array_merge(
            $portions
            , array_map(
                static fn(DatedAssociation $association) => $association->secondaryService
                , array_values(
                    array_filter(
                        $this->dividesJoinsEnRoute
                        , static fn(DatedAssociation $association) =>
                            $association->associationEntry instanceof Association
                            && $association->associationEntry->category === AssociationCategory::DIVIDE
                    )
                )
            )
        );
        return array_merge(
            $base
            , ...array_map(
                static function(DatedService $portion) {
                    if (!$portion instanceof self) {
                        throw new LogicException('Listing all destinations requires all services being full services.');
                    }
                    return $portion->getAllDestinations();
                }
                , $portions
            )
        );
    }
}