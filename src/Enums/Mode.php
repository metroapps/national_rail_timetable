<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

enum Mode : string {
    case TRAIN = '';
    case BUS = 'B';
    case SHIP = 'S';

    public function showIcon() : string {
        return match($this) {
            self::BUS => '<img class="mode" src="/images/bus.png" alt="bus" title="Bus service" /><br/>',
            self::SHIP => '<img class="mode" src="/images/ship.png" alt="ship" title="Ferry service" /><br/>',
            default => '',
        };
    }
}