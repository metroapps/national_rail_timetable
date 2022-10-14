<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models\Points;

use Miklcct\NationalRailTimetable\Models\Time;

interface HasDeparture {
    public function getWorkingDeparture() : Time;
    public function getPublicDeparture() : ?Time;
    public function getPublicOrWorkingDeparture() : Time;
}