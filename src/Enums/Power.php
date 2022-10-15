<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Enums;

use PhpParser\Node\Expr\AssignOp\Pow;

enum Power : string {
    case NONE = '';
    case DIESEL = 'D';
    case DEMU = 'DEM';
    case DMU = 'DMU';
    case ELECTRIC = 'E';
    case ELECTRO_DIESEL = 'ED';
    case EMU_LOCOMOTIVE = 'EML';
    case EMU = 'EMU';
    case HST = 'HST';

    public function getDescription() : string {
        return match($this) {
            Power::NONE => '',
            Power::DIESEL => 'Diesel Locomotive',
            Power::DEMU => 'Diesel-Electric Multiple Unit',
            Power::DMU => 'Diesel Multiple Unit',
            Power::ELECTRIC => 'Electric Locomotive',
            Power::ELECTRO_DIESEL => 'Diesel-Electric Locomotive',
            Power::EMU_LOCOMOTIVE => 'EMU Locomotive',
            Power::EMU => 'Electric Multiple Unit',
            Power::HST => 'High Speed Train',
        };
    }
}