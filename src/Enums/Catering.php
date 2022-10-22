<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

enum Catering : string {
    case BUFFET = 'C';
    case FIRST_CLASS_RESTAURANT = 'F';
    case HOT_FOOD = 'H';
    case FIRST_CLASS_MEAL = 'M';
    case WHEELCHAIR_ONLY = 'P';
    case RESTAURANT = 'R';
    case TROLLEY = 'T';

    public function showIcon() : string {
        return match($this) {
            self::BUFFET => '<img src="/images/buffet.png" alt="buffet" title="Buffet" />',
            self::FIRST_CLASS_RESTAURANT => '<img src="/images/first_class_restaurant.png" alt="first class restaurant" title="Restaurant for first class passengers" />',
            self::HOT_FOOD => '<img src="/images/first_class_restaurant.png" alt="hot food" title="Hot food" />',
            self::RESTAURANT => '<img src="/images/restaurant.png" alt="restaurant" title="Restaurant" />',
            self::TROLLEY => '<img src="/images/trolley.png" alt="restaurant" title="Trolley" />',
            default => '',
        };
    }
}