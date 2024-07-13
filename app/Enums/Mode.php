<?php
declare(strict_types=1);

namespace App\Enums;

enum Mode : string {
    case TRAIN = '';
    case BUS = 'B';
    case SHIP = 'S';
}
