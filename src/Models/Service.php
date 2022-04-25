<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Attributes\ElementType;
use Miklcct\NationalRailJourneyPlanner\Enums\BankHoliday;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use Miklcct\NationalRailJourneyPlanner\Models\Points\CallingPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\DestinationPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\IntermediatePoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\OriginPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\PassingPoint;
use Miklcct\NationalRailJourneyPlanner\Models\Points\TimingPoint;
use RuntimeException;
use const PHP_INT_MAX;

class Service extends ServiceEntry {
    use BsonSerializeTrait;

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
    #[ElementType(TimingPoint::class)]
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
                        ? $point->getPublicOrWorkingArrival()->toHalfMinutes()
                        : PHP_INT_MAX
                    )
                ) < $time->toHalfMinutes()
            ) {
                $result = $point->servicePropertyChange;
            }
        }
        return $result;
    }

    public function getAssociationPoint(Association $association) : TimingPoint {
        $secondary = $association->secondaryUid === $this->uid;
        foreach ($this->points as $point) {
            if (
                $point->locationSuffix === (
                $secondary
                    ? $association->secondarySuffix
                    : $association->primarySuffix
                ) && $point->location->tiploc === $association->location->tiploc
            ) {
                return $point;
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

    public function hasRsid(string $rsid) : bool {
        $service_property = $this->serviceProperty;
        if (str_starts_with($service_property->rsid, $rsid)) {
            return true;
        }
        foreach ($this->points as $point) {
            if (
                $point instanceof IntermediatePoint
                && str_starts_with($point->servicePropertyChange?->rsid ?? '', $rsid)
            ) {
                return true;
            }
        }
        return false;
    }
}