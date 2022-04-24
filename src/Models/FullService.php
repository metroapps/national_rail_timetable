<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use DateTimeImmutable;
use LogicException;
use Miklcct\NationalRailJourneyPlanner\Enums\AssociationCategory;
use Miklcct\NationalRailJourneyPlanner\Enums\CallType;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\Points\CallingPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\DestinationPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\OriginPoint;
use UnexpectedValueException;
use function array_filter;
use function array_merge;
use function assert;

/**
 * @property-read Service $service
 */
class FullService extends DatedService {
    public function __construct(
        Service $service
        , Date $date
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
    public function getAllOrigins(?Time $time = null) : array {
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
                        , function (DatedAssociation $association) use ($time) {
                            if (!$association->associationEntry instanceof Association) {
                                return false;
                            }
                            if ($association->associationEntry->category !== AssociationCategory::JOIN) {
                                return false;
                            }
                            assert($this->service instanceof Service);
                            $association_point = $this->service->getAssociationPoint($association->associationEntry);
                            assert($association_point instanceof CallingPoint);
                            return $time === null
                                || $association_point->getPublicOrWorkingDeparture()->toHalfMinutes() <= $time->toHalfMinutes();
                        }
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
    public function getAllDestinations(?Time $time = null) : array {
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
                        , function (DatedAssociation $association) use ($time) {
                            if (!$association->associationEntry instanceof Association) {
                                return false;
                            }
                            if ($association->associationEntry->category !== AssociationCategory::DIVIDE) {
                                return false;
                            }
                            assert($this->service instanceof Service);
                            $association_point = $this->service->getAssociationPoint($association->associationEntry);
                            assert($association_point instanceof CallingPoint);
                            return $time === null
                                || $association_point->getPublicOrWorkingArrival()->toHalfMinutes() >= $time->toHalfMinutes();
                        }
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

    public function getCallsAt(
        string $crs
        , CallType $call_type
        , TimeType $time_type
        , DateTimeImmutable $from = null
        , DateTimeImmutable $to = null
    ) : array {
        $this_portion = parent::getCallsAt($crs, $call_type, $time_type, $from, $to);
        if ($this->joinTo === null) {
            $join_portion = [];
        } else {
            $association = $this->joinTo->associationEntry;
            assert($association instanceof Association);
            $primary_service = $this->joinTo->primaryService;
            assert($primary_service->service instanceof Service);
            $association_point = $primary_service->service->getAssociationPoint($association);
            assert($association_point instanceof CallingPoint);
            $association_timestamp = $primary_service->date->toDateTimeImmutable(
                $association_point->getPublicOrWorkingDeparture()
            );
            $join_portion = $primary_service->getCallsAt(
                $crs
                , $call_type
                , $time_type
                , $from !== null && $from > $association_timestamp ? $from : $association_timestamp
                , $to
            );
        }
        if ($this->divideFrom === null) {
            $divide_portion = [];
        } else {
            $association = $this->divideFrom->associationEntry;
            assert($association instanceof Association);
            $primary_service = $this->divideFrom->primaryService;
            assert($primary_service->service instanceof Service);
            $association_point = $primary_service->service->getAssociationPoint($association);
            assert($association_point instanceof CallingPoint);
            $association_timestamp = $primary_service->date->toDateTimeImmutable(
                $association_point->getPublicOrWorkingArrival()
            );
            $divide_portion = $primary_service->getCallsAt(
                $crs
                , $call_type
                , $time_type
                , $from
                , $to !== null && $to < $association_timestamp ? $to : $association_timestamp
            );
        }
        $other_portions = array_merge(
            ...array_map(
                function (DatedAssociation $dated_association) use ($time_type, $call_type, $crs, $to, $from) {
                    $association = $dated_association->associationEntry;
                    assert($association instanceof Association);
                    $secondary_service = $dated_association->secondaryService;
                    assert($secondary_service->service instanceof Service);
                    if ($association->category === AssociationCategory::DIVIDE) {
                        $divide_timestamp = $secondary_service->date->toDateTimeImmutable(
                            $secondary_service->service->getOrigin()->getPublicOrWorkingDeparture()
                        );
                        return $divide_timestamp < $from
                            ? []
                            : $secondary_service->getCallsAt(
                                $crs
                                , $call_type
                                , $time_type
                                , $divide_timestamp
                                , $to
                            );
                    }
                    if ($association->category === AssociationCategory::JOIN) {
                        $join_timestamp = $secondary_service->date->toDateTimeImmutable(
                            $secondary_service->service->getDestination()->getPublicOrWorkingArrival()
                        );
                        return $join_timestamp > $to
                            ? []
                            : $secondary_service->getCallsAt(
                                $crs
                                , $call_type
                                , $time_type
                                , $from
                                , $join_timestamp
                            );
                    }
                    throw new UnexpectedValueException('Unknown association type');
                }
                , $this->dividesJoinsEnRoute
            )
        );
        return array_merge($divide_portion, $this_portion, $other_portions, $join_portion);
    }
}