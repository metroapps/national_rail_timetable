<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ZStopTime extends BaseStopTime {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'z_stop_time';

    public function scheduleModel() : BelongsTo {
        return $this->belongsTo(ZSchedule::class, 'schedule');
    }

    public function physicalStation() : BelongsTo {
        return $this->belongsTo(PhysicalStation::class, 'location', 'crs_code')->primary();
    }
}
