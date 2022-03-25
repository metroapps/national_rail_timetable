<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Enums;

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
}