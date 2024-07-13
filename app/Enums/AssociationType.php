<?php
declare(strict_types=1);

namespace App\Enums;

enum AssociationType : string {
    case PASSENGER = 'P';
    case OPERATING = 'O';
}
