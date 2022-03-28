<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Enums\AssociationCategory;
use Miklcct\NationalRailJourneyPlanner\Enums\BankHoliday;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use Miklcct\NationalRailJourneyPlanner\Models\Points\CallingPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\DestinationPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\IntermediatePoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\OriginPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\PassingPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;
use RuntimeException;
use function assert;
use const PHP_INT_MAX;

class Service extends ServiceEntry {
    public function __construct(
        string $uid
        , Period $period
        , BankHoliday $excludeBankHoliday
        , public readonly string $toc
        , public readonly ServiceProperty $serviceProperty
        , array $timingPoints
        , ShortTermPlanning $shortTermPlanning
    ) {
        parent::__construct(
            $uid
            , $period
            , $excludeBankHoliday
            , $shortTermPlanning
        );
        $this->points = $timingPoints;
    }

    /** @var TimingPoint[] */
    public readonly array $points;

    public function getServicePropertyAtTime(Time $time) : ServiceProperty {
        $result = $this->serviceProperty;
        foreach ($this->points as $point) {
            if (
                $point instanceof IntermediatePoint
                && $point->servicePropertyChange !== null
                && ($point instanceof PassingPoint
                    ? $point->pass->toHalfMinutes()
                    : ($point instanceof CallingPoint
                        ? $point->workingArrival->toHalfMinutes()
                        : PHP_INT_MAX
                    )
                ) < $time->toHalfMinutes()
            ) {
                $result = $point->servicePropertyChange;
            }
        }
        return $result;
    }

    public function getAssociationTime(Association $association) : Time {
        $secondary = $association->secondaryUid === $this->uid;
        foreach ($this->points as $point) {
            if (
                $point->locationSuffix === (
                $secondary
                    ? $association->secondarySuffix
                    : $association->primarySuffix
                ) && $point->location->tiploc === $association->location->tiploc
            ) {
                if ($secondary) {
                    switch ($association->category) {
                    case AssociationCategory::NEXT:
                    case AssociationCategory::DIVIDE:
                        assert($point instanceof OriginPoint);
                        return $point->getPublicOrWorkingDeparture();
                    case AssociationCategory::JOIN:
                        assert($point instanceof DestinationPoint);
                        return $point->getPublicOrWorkingArrival();
                    }
                } else {
                    switch ($association->category) {
                    case AssociationCategory::NEXT:
                        assert($point instanceof DestinationPoint);
                        return $point->getPublicOrWorkingArrival();
                    case AssociationCategory::DIVIDE:
                        assert($point instanceof CallingPoint);
                        return $point->getPublicOrWorkingArrival();
                    case AssociationCategory::JOIN:
                        assert($point instanceof CallingPoint);
                        return $point->getPublicOrWorkingDeparture();
                    }
                }
            }
        }
        throw new RuntimeException('Invalid association location');
    }

    public function getOrigin() : OriginPoint {
        return $this->points[0];
    }

    public function getDestination() : DestinationPoint {
        return $this->points[count($this->points) - 1];
    }
}