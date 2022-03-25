<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Enums;

// Currently, only scheduled passenger trains in National Rail system
// are listed here.
enum TrainCategory : string {
    case NONE = '';
    case METRO = 'OL';
    case ORDINARY = 'OO';
    case EXPRESS = 'XX';
    case SLEEPER = 'XZ';
    case REPLACEMENT_BUS = 'BR';
    case BUS = 'BS';
    case SHIP = 'SS';

    public function isPassengerTrain() : bool {
        return true;
    }
}