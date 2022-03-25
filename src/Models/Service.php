<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

use Miklcct\NationalRailJourneyPlanner\Enums\AssociationCategory;
use Miklcct\NationalRailJourneyPlanner\Enums\BankHoliday;
use Miklcct\NationalRailJourneyPlanner\Enums\ShortTermPlanning;
use RuntimeException;
use function substr;
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
                ) && $point->location === $association->location
            ) {
                /** @noinspection PhpPossiblePolymorphicInvocationInspection */
                return match ($association->category) {
                    AssociationCategory::NEXT, AssociationCategory::DIVIDE => $secondary
                        ? $point->getPublicDeparture() ?? $point->workingDeparture
                        : $point->getPublicArrival() ?? $point->workingArrival,
                    AssociationCategory::JOIN => $secondary
                        ? $point->getPublicArrival() ?? $point->workingArrival
                        : $point->getPublicDeparture() ?? $point->workingDeparture,
                };
            }
        }
        throw new RuntimeException('Invalid association location');
    }
}