<?php
declare(strict_types=1);

namespace App\Enums;

enum TrainClass : string {
    case BOTH = 'B';
    case FIRST = 'F';
    case STANDARD = 'S';

    public function hasFirstClass() : bool {
        return $this === self::BOTH || $this === self::FIRST;
    }

    public function hasStandardClass() : bool {
        return $this === self::BOTH || $this === self::STANDARD;
    }
}
