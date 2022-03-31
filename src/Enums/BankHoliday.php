<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Enums;

use Miklcct\NationalRailJourneyPlanner\Models\Date;
use function Safe\file_get_contents;
use function Safe\json_decode;

enum BankHoliday : string {
    case NONE = '';
    case ENGLAND = 'X';
    case GLASGOW = 'G';

    public function isActive(Date $date) : bool {
        static $data = null;
        if ($data === null) {
            $data = json_decode(
                file_get_contents(__DIR__ . '/../../resource/holidays.json')
            );
        }
        return in_array(
            match ($this) {
                self::ENGLAND => 'EAW',
                self::GLASGOW => 'SCT',
                self::NONE => '',
            }
            , $data->{$date->__toString()}->regions ?? []
            , true
        );
    }
}