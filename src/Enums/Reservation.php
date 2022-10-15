<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

enum Reservation : string {
    case NONE = '';
    case BICYCLE = 'E';
    case AVAILABLE = 'S';
    case RECOMMENDED = 'R';
    case COMPULSORY = 'A';

    public function showIcon() : string {
        return match ($this) {
            Reservation::AVAILABLE => '<img src="/images/reservation_available.png" alt="reservation available" title="Reservation available" />',
            Reservation::RECOMMENDED => '<img src="/images/reservation_recommended.png" alt="reservation recommended" title="Reservation recommended" />',
            Reservation::COMPULSORY => '<img src="/images/reservation_compulsory.png" alt="reservation compulsory" title="Reservation compulsory" />',
            default => '',
        };
    }
}