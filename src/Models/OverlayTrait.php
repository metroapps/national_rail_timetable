<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Models;

use Miklcct\NationalRailTimetable\Enums\ShortTermPlanning;

trait OverlayTrait {
    public function isSuperior(?self $compare, bool $permanent_only = false) : bool {
        return $permanent_only
            ? $this->shortTermPlanning === ShortTermPlanning::PERMANENT
            : $compare === null || $this->shortTermPlanning !== ShortTermPlanning::PERMANENT;
    }
}