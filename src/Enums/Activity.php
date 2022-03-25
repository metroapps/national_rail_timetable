<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Enums;

// This list is not complete.
// Only activities relevant to passenger operations are listed here.
enum Activity : string {
    case DETACH = '-D';
    case ATTACH_DETACH = '-T';
    case ATTACH = '-U';
    case SET_DOWN = 'D';
    case PASSENGER_CALL = 'T';
    case TRAIN_BEGINS = 'TB';
    case TRAIN_FINISHES = 'TF';
    case REQUEST_STOP = 'R';
    case PICK_UP = 'U';
}