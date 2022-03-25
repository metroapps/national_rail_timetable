<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Enums;

use DateTimeImmutable;
use DateTimeZone;
use function Safe\file_get_contents;
use function Safe\json_decode;

enum BankHoliday : string {
    case NONE = '';
    case ENGLAND = 'X';
    case GLASGOW = 'G';

    public function isActive(DateTimeImmutable $date) : bool {
        static $data = null;
        if ($data === null) {
            $data = json_decode(
                file_get_contents(__DIR__ . '/../../resource/holidays.json')
            );
        }
        static $timezone = new DateTimeZone('Europe/London');
        $date = $date->setTimezone($timezone);
        return in_array(
            match ($this) {
                self::ENGLAND => 'EAW',
                self::GLASGOW => 'SCT',
                self::NONE => '',
            }
            , ($data->{$date->format('Y-m-d')}->regions ?? [])
            , true
        );
    }
}