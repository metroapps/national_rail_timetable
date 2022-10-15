<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

// Currently, only scheduled passenger trains in National Rail system
// are listed here.

enum TrainCategory : string {
    case NONE = '';
    case METRO = 'OL';
    case ORDINARY = 'OO';
    case CHANNEL_TUNNEL = 'XC';
    case EXPRESS = 'XX';
    case SLEEPER = 'XZ';
    case REPLACEMENT_BUS = 'BR';
    case BUS = 'BS';
    case SHIP = 'SS';

    public function isPassengerTrain() : bool {
        return true;
    }

    public function getDescription() : string {
        return match ($this) {
            TrainCategory::NONE => '',
            TrainCategory::METRO => 'Metro Train',
            TrainCategory::ORDINARY => 'Local Train',
            TrainCategory::CHANNEL_TUNNEL => 'Channel Tunnel Train',
            TrainCategory::EXPRESS => 'Express Train',
            TrainCategory::SLEEPER => 'Sleeper',
            TrainCategory::REPLACEMENT_BUS => 'Rail Replacement Bus',
            TrainCategory::BUS => 'Service Bus',
            TrainCategory::SHIP => 'Ship',  
        };
    }
}
