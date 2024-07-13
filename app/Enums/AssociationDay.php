<?php
declare(strict_types=1);

namespace App\Enums;

enum AssociationDay : string {
    case YESTERDAY = 'P';
    case TODAY = 'S';
    case TOMORROW = 'N';
}
